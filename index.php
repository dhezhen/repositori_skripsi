<?php
declare(strict_types=1);

const DATA_FILE = __DIR__ . '/data/theses.json';
const DB_FILE = __DIR__ . '/data/repository.sqlite';

function database(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $pdo = new PDO('sqlite:' . DB_FILE, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('CREATE TABLE IF NOT EXISTS theses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL, title TEXT NOT NULL, year INTEGER,
        program TEXT, advisors TEXT NOT NULL DEFAULT "[]",
        examiners TEXT NOT NULL DEFAULT "[]"
    )');
    $columns = $pdo->query('PRAGMA table_info(theses)')->fetchAll(PDO::FETCH_COLUMN, 1);
    if (in_array('honors', $columns, true)) $pdo->exec('ALTER TABLE theses DROP COLUMN honors');
    if ((int)$pdo->query('SELECT COUNT(*) FROM theses')->fetchColumn() === 0 && is_file(DATA_FILE)) {
        $rows = json_decode((string)file_get_contents(DATA_FILE), true) ?: [];
        $insert = $pdo->prepare('INSERT INTO theses (name,title,year,program,advisors,examiners) VALUES (?,?,?,?,?,?)');
        foreach ($rows as $row) $insert->execute([$row['name'], $row['title'], $row['year'], $row['program'], json_encode($row['advisors'], JSON_UNESCAPED_UNICODE), json_encode($row['examiners'], JSON_UNESCAPED_UNICODE)]);
    }
    return $pdo;
}

// Kata kunci judul per algoritma; dipakai filter katalog dan halaman Topik Riset agar jumlahnya selalu sama.
function algorithm_keywords(): array {
    return [
        'CNN / Deep Learning' => ['cnn', 'convolutional neural network', 'deep learning', 'transfer learning', 'resnet', 'mobilenet', 'yolo'],
        'K-Means / Clustering' => ['k-means', 'kmeans', 'clustering', 'c-means'],
        'Naive Bayes' => ['naive bayes', 'bayes'],
        'C4.5 / Decision Tree' => ['c4.5', 'decision tree', 'pohon keputusan'],
        'K-Nearest Neighbor (KNN)' => ['knn', 'k-nearest neighbor', 'k-nearest'],
        'AHP (Analytical Hierarchy Process)' => ['ahp', 'analytical hierarchy process'],
        'SAW (Simple Additive Weighting)' => ['saw', 'simple additive weighting'],
        'TOPSIS' => ['topsis'],
        'Apriori / Association' => ['apriori', 'fp-growth', 'association rule'],
        'Certainty Factor' => ['certainty factor', 'cf'],
        'Forward/Backward Chaining' => ['forward chaining', 'backward chaining'],
        'Dijkstra / A*' => ['dijkstra', 'a-star', 'a*'],
        'String Matching / NLP' => ['rabin karp', 'knuth morris pratt', 'kmp', 'tf-idf', 'nlp', 'natural language processing', 'cosine similarity'],
        'Kriptografi' => ['aes', 'rsa', 'kriptografi', 'steganografi', 'lsb']
    ];
}

function filters(): array {
    $q = trim((string)($_GET['q'] ?? ''));
    $year = trim((string)($_GET['year'] ?? ''));
    $where = [];
    $params = [];
    if ($q !== '') {
        $where[] = '(title LIKE :q OR name LIKE :q OR advisors LIKE :q OR examiners LIKE :q OR program LIKE :q)';
        $params[':q'] = '%' . $q . '%';
    }
    $adviser = trim((string)($_GET['adviser'] ?? ''));
    if ($adviser !== '') {
        $where[] = 'advisors LIKE :adviser';
        $params[':adviser'] = '%' . json_encode($adviser, JSON_UNESCAPED_UNICODE) . '%';
    }
    if ($year !== '' && ctype_digit($year)) { $where[] = 'year = :year'; $params[':year'] = (int)$year; }
    $keywords = algorithm_keywords()[(string)($_GET['algo'] ?? '')] ?? [];
    if ($keywords) {
        $match = [];
        foreach ($keywords as $i => $keyword) { $match[] = "title LIKE :algo$i"; $params[":algo$i"] = '%' . $keyword . '%'; }
        $where[] = '(' . implode(' OR ', $match) . ')';
    }
    return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
}

if (isset($_GET['api'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $pdo = database();
        if ($_GET['api'] === 'statistics') {
            $counts = [];
            foreach ($pdo->query('SELECT name,advisors FROM theses')->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $advisors = array_unique(json_decode($row['advisors'], true) ?: []);
                foreach ($advisors as $adviser) {
                    $key = mb_strtolower(trim($adviser));
                    if (!isset($counts[$key])) $counts[$key] = ['name'=>$adviser, 'students'=>0];
                    $counts[$key]['students']++;
                }
            }
            $advisors = array_values($counts);
            usort($advisors, static fn($a, $b) => ($b['students'] <=> $a['students']) ?: strcasecmp($a['name'], $b['name']));
            echo json_encode(['total'=>(int)$pdo->query('SELECT COUNT(*) FROM theses')->fetchColumn(), 'advisers'=>$advisors], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($_GET['api'] !== 'theses') { http_response_code(404); echo json_encode(['error'=>'Layanan tidak ditemukan.']); exit; }
        [$where, $params] = filters();
        $total = $pdo->prepare('SELECT COUNT(*) FROM theses' . $where); $total->execute($params); $count = (int)$total->fetchColumn();
        $years = $pdo->query('SELECT year, COUNT(*) AS count FROM theses WHERE year IS NOT NULL GROUP BY year ORDER BY year DESC')->fetchAll(PDO::FETCH_ASSOC);
        $advisorNames = [];
        foreach ($pdo->query('SELECT advisors FROM theses')->fetchAll(PDO::FETCH_COLUMN) as $json) {
            foreach (json_decode($json, true) ?: [] as $name) $advisorNames[mb_strtolower($name)] = $name;
        }
        natcasesort($advisorNames);
        $sorts = ['year_desc'=>'year DESC, name COLLATE NOCASE', 'year_asc'=>'year ASC, name COLLATE NOCASE', 'title_asc'=>'title COLLATE NOCASE', 'name_asc'=>'name COLLATE NOCASE'];
        $sort = (string)($_GET['sort'] ?? 'year_desc');
        $order = $sorts[$sort] ?? $sorts['year_desc'];
        $page = max(1, (int)($_GET['page'] ?? 1)); $limit = 12;
        $stmt = $pdo->prepare('SELECT id,name,title,year,program,advisors,examiners FROM theses' . $where . ' ORDER BY ' . $order . ' LIMIT :limit OFFSET :offset');
        foreach ($params as $key => $value) $stmt->bindValue($key, $value);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT); $stmt->bindValue(':offset', ($page - 1) * $limit, PDO::PARAM_INT); $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as &$item) { $item['advisors'] = json_decode($item['advisors'], true) ?: []; $item['examiners'] = json_decode($item['examiners'], true) ?: []; }
        echo json_encode(['items'=>$items,'total'=>$count,'page'=>$page,'pages'=>max(1,(int)ceil($count/$limit)),'years'=>$years,'advisors'=>array_values($advisorNames),'all'=>(int)$pdo->query('SELECT COUNT(*) FROM theses')->fetchColumn()], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) { http_response_code(500); echo json_encode(['error'=>'Data belum dapat dimuat. Pastikan data/theses.json tersedia dan folder data dapat ditulis.']); }
    exit;
}

if (isset($_GET['admin'])) {
    $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
    if (!$isLocal) { http_response_code(403); exit('Halaman impor hanya tersedia dari komputer server.'); }
    session_set_cookie_params(['httponly'=>true, 'samesite'=>'Strict', 'secure'=>isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    session_start();
    $_SESSION['import_token'] ??= bin2hex(random_bytes(32));
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $message = '';
        try {
            if (!hash_equals($_SESSION['import_token'], (string)($_POST['token'] ?? ''))) throw new RuntimeException('Permintaan tidak valid. Muat ulang halaman lalu coba lagi.');
            $file = $_FILES['workbook'] ?? null;
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Pilih file Excel .xlsx yang valid.');
            if ($file['size'] > 12 * 1024 * 1024 || strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'xlsx') throw new RuntimeException('File harus berformat .xlsx dan berukuran maksimal 12 MB.');
            $python = getenv('PYTHON_BINARY') ?: 'python';
            $userProfile = getenv('USERPROFILE') ?: '';
            $bundledPython = rtrim($userProfile, '\\/') . '/.cache/codex-runtimes/codex-primary-runtime/dependencies/python/python.exe';
            if (!getenv('PYTHON_BINARY') && $userProfile !== '' && is_file($bundledPython)) $python = $bundledPython;
            $tempDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $uploadPath = $tempDir . 'repositori-' . bin2hex(random_bytes(8)) . '.xlsx';
            $jsonPath = $tempDir . 'repositori-' . bin2hex(random_bytes(8)) . '.json';
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) throw new RuntimeException('File tidak dapat disimpan. Periksa izin folder data.');
            $command = escapeshellarg($python) . ' ' . escapeshellarg(__DIR__ . '/import_data.py') . ' ' . escapeshellarg($uploadPath) . ' --output ' . escapeshellarg($jsonPath);
            $process = proc_open($command, [['pipe','r'],['pipe','w'],['pipe','w']], $pipes, __DIR__);
            if (!is_resource($process)) throw new RuntimeException('Python tidak dapat dijalankan. Pastikan Python dan openpyxl tersedia.');
            fclose($pipes[0]); $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]); $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]); $exitCode = proc_close($process);
            if ($exitCode !== 0 || !is_file($jsonPath)) throw new RuntimeException('File Excel gagal dibaca. Pastikan strukturnya sama dengan workbook repositori.');
            $rows = json_decode((string)file_get_contents($jsonPath), true);
            if (!is_array($rows) || count($rows) === 0) throw new RuntimeException('Tidak ada data skripsi yang ditemukan dalam file.');
            $pdo = database(); $pdo->beginTransaction();
            try {
                $pdo->exec('DELETE FROM theses');
                $insert = $pdo->prepare('INSERT INTO theses (name,title,year,program,advisors,examiners) VALUES (?,?,?,?,?,?)');
                foreach ($rows as $row) $insert->execute([$row['name'], $row['title'], $row['year'], $row['program'], json_encode($row['advisors'], JSON_UNESCAPED_UNICODE), json_encode($row['examiners'], JSON_UNESCAPED_UNICODE)]);
                $pdo->commit();
            } catch (Throwable $error) { $pdo->rollBack(); throw $error; }
            file_put_contents(DATA_FILE, json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
            $message = count($rows) . ' judul berhasil diimpor.';
            @unlink($uploadPath); @unlink($jsonPath);
        } catch (Throwable $error) {
            if (isset($uploadPath)) @unlink($uploadPath);
            if (isset($jsonPath)) @unlink($jsonPath);
            $message = $error->getMessage();
        }
        $_SESSION['import_message'] = $message;
        header('Location: ?admin=1'); exit;
    }
    $message = (string)($_SESSION['import_message'] ?? ''); unset($_SESSION['import_message']);
    ?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#0d4f91"><title>Impor Data — Repositori FKOM</title><link rel="icon" href="assets/favicon.ico"><link rel="stylesheet" href="style.css"></head><body>
    <header class="topbar"><a class="brand" href="./"><img class="brand-icon" src="assets/fkom-icon.png" alt="Logo FKOM"><span class="brand-name">Repositori Skripsi<small>TEKNIK INFORMATIKA - FKOM UNIKU</small></span></a><nav class="top-menu" aria-label="Menu utama"><a href="./">Katalog</a><a href="?statistics=1">Dosen &amp; Pembimbing</a><a href="?topics=1">Topik Riset</a></nav></header>
    <main class="admin-main"><div class="eyebrow"><span></span> KELOLA KOLEKSI</div><h1>Impor data Excel</h1><p>Unggah workbook laporan sidang untuk mengganti data repositori saat ini. Gunakan workbook dengan format sumber yang sama.</p>
      <?php if ($message !== ''): ?><div class="admin-message" role="status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
      <form class="import-form" method="post" enctype="multipart/form-data"><input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['import_token'], ENT_QUOTES, 'UTF-8') ?>"><label for="workbook">Pilih file workbook (.xlsx)</label><input id="workbook" name="workbook" type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required><p>Maksimal 12 MB. Impor akan mengganti seluruh koleksi yang tersimpan.</p><button type="submit">Impor dan perbarui data</button></form>
    </main></body></html><?php exit;
}

if (isset($_GET['topics'])) {
    $pdo = database();

    // Define keyword mappings
    $algorithms = algorithm_keywords();

    $themes = [
        'Sistem Pendukung Keputusan' => ['sistem pendukung keputusan', 'spk', 'pemilihan', 'penentuan'],
        'Sistem Pakar' => ['sistem pakar', 'diagnosa', 'diagnosis penyakit'],
        'Data Mining' => ['data mining', 'klasifikasi', 'prediksi', 'peramalan'],
        'Pengolahan Citra / Computer Vision' => ['pengolahan citra', 'pengenalan wajah', 'citra daun', 'deteksi objek', 'citra digital', 'segmentasi', 'pengenalan pola', 'citra', 'gambar', 'computer vision', 'objek', 'wajah', 'cnn', 'convolutional neural network', 'deep learning', 'transfer learning', 'resnet', 'mobilenet', 'yolo'],
        'Internet of Things (IoT)' => ['iot', 'internet of things', 'mikrokontroler', 'arduino', 'raspberry'],
        'Game & AR/VR' => ['game', 'augmented reality', 'virtual reality', 'ar ', 'vr '],
        'Sistem Informasi (Web/Mobile)' => ['sistem informasi', 'aplikasi', 'rancang bangun', 'pengembangan sistem', 'berbasis web', 'berbasis android']
    ];

    $algo_counts = array_fill_keys(array_keys($algorithms), 0);
    $theme_counts = array_fill_keys(array_keys($themes), 0);

    $algo_list = array_fill_keys(array_keys($algorithms), []);
    $theme_list = array_fill_keys(array_keys($themes), []);

    foreach ($pdo->query('SELECT title, name, year FROM theses')->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $title = mb_strtolower($row['title']);
        $matched_algo = false;
        foreach ($algorithms as $algo_name => $keywords) {
            foreach ($keywords as $kw) {
                if (strpos($title, $kw) !== false) {
                    $algo_counts[$algo_name]++;
                    $algo_list[$algo_name][] = $row;
                    $matched_algo = true;
                    break;
                }
            }
        }
        if (!$matched_algo) {
            if (!isset($algo_counts['Lainnya/Tidak Spesifik'])) $algo_counts['Lainnya/Tidak Spesifik'] = 0;
            $algo_counts['Lainnya/Tidak Spesifik']++;
        }
        
        $matched_theme = false;
        foreach ($themes as $theme_name => $keywords) {
            foreach ($keywords as $kw) {
                if (strpos($title, $kw) !== false) {
                    $theme_counts[$theme_name]++;
                    $theme_list[$theme_name][] = $row;
                    $matched_theme = true;
                    break;
                }
            }
        }
        if (!$matched_theme) {
            if (!isset($theme_counts['Lainnya'])) $theme_counts['Lainnya'] = 0;
            $theme_counts['Lainnya']++;
        }
    }

    arsort($algo_counts);
    arsort($theme_counts);

    ?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#0d4f91"><title>Topik & Algoritma — Repositori FKOM</title><link rel="icon" href="assets/favicon.ico"><link rel="stylesheet" href="style.css">
    </head><body>
    <header class="topbar"><a class="brand" href="./"><img class="brand-icon" src="assets/fkom-icon.png" alt="Logo FKOM"><span class="brand-name">Repositori Skripsi<small>TEKNIK INFORMATIKA - FKOM UNIKU</small></span></a><nav class="top-menu" aria-label="Menu utama"><a href="./">Katalog</a><a href="?statistics=1">Dosen &amp; Pembimbing</a><a class="current" href="?topics=1" aria-current="page">Topik Riset</a></nav></header>
    <main class="stats-page"><div class="eyebrow"><span></span> ANALISIS KOLEKSI</div><h1>Pengelompokan Topik & Algoritma</h1><p class="stats-intro">Statistik penggunaan algoritma/metode dan tema riset berdasarkan ekstraksi kata kunci dari judul skripsi.</p>
      <div class="topic-grid">
        <div class="topic-card">
            <h2>Algoritma & Metode Terpopuler</h2>
            <?php foreach ($algo_counts as $name => $count): if ($count === 0 || $name === 'Lainnya/Tidak Spesifik') continue; ?>
                <a class="topic-item" href="./?algo=<?= urlencode($name) ?>"><span class="topic-name"><?= htmlspecialchars($name) ?></span><span class="topic-count"><?= $count ?></span></a>
            <?php endforeach; ?>
        </div>
        <div class="topic-card">
            <h2>Tema Riset Terpopuler</h2>
            <?php foreach ($theme_counts as $name => $count): if ($count === 0 || $name === 'Lainnya') continue; ?>
                <div class="topic-item"><span class="topic-name"><?= htmlspecialchars($name) ?></span><span class="topic-count"><?= $count ?></span></div>
            <?php endforeach; ?>
        </div>
      </div>
    </main></body></html><?php exit;
}

if (isset($_GET['statistics'])) {
    ?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#0d4f91"><title>Statistik Pembimbing — Repositori FKOM</title><link rel="icon" href="assets/favicon.ico"><link rel="stylesheet" href="style.css"></head><body>
    <header class="topbar"><a class="brand" href="./"><img class="brand-icon" src="assets/fkom-icon.png" alt="Logo FKOM"><span class="brand-name">Repositori Skripsi<small>TEKNIK INFORMATIKA - FKOM UNIKU</small></span></a><nav class="top-menu" aria-label="Menu utama"><a href="./">Katalog</a><a class="current" href="?statistics=1" aria-current="page">Dosen &amp; Pembimbing</a><a href="?topics=1">Topik Riset</a></nav></header>
    <main class="stats-page"><div class="eyebrow"><span></span> RINGKASAN KOLEKSI</div><h1>Statistik pembimbing</h1><p class="stats-intro">Jumlah mahasiswa pada data skripsi yang tercatat dibimbing oleh setiap dosen.</p>
      <div class="stats-summary"><div><strong id="stats-total">—</strong><span>skripsi dalam koleksi</span></div><div><strong id="stats-adviser-total">—</strong><span>dosen pembimbing</span></div></div>
      <section class="stats-section"><div class="stats-section-head"><h2>Jumlah bimbingan</h2><span>DIURUTKAN TERBANYAK</span></div><ol id="stats-list" class="stats-list" aria-live="polite"><li class="stats-loading">Memuat statistik…</li></ol></section>
      <p class="stats-note">Setiap skripsi dihitung satu kali untuk masing-masing dosen pembimbing yang tercatat. Jika satu skripsi memiliki dua pembimbing, data tersebut masuk ke hitungan keduanya.</p>
    </main><script src="statistics.js" defer></script></body></html><?php exit;
}


?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0b5cce"><title>Repositori Skripsi - FKOM UNIKU</title>
  <link rel="icon" href="assets/favicon.ico" sizes="any">
  <link rel="icon" type="image/png" sizes="32x32" href="assets/fkom-icon-32.png">
  <link rel="apple-touch-icon" href="assets/fkom-icon-192.png">
  <link rel="manifest" href="site.webmanifest">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header class="topbar">
    <a class="brand" href="./" aria-label="Repositori Skripsi FKOM UNIKU">
      <img class="brand-icon" src="assets/fkom-icon.png" alt="Logo FKOM">
      <span class="brand-name">Repositori Skripsi<small>TEKNIK INFORMATIKA - FKOM UNIKU</small></span>
    </a>
    <nav class="top-menu" aria-label="Menu utama">
      <a href="./" class="current" aria-current="page"><svg class="nav-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 3h12a1 1 0 011 1v12a1 1 0 01-1 1H4a1 1 0 01-1-1V4a1 1 0 011-1z"/><path d="M7 7h6M7 10h6M7 13h4"/></svg> Katalog</a>
      <a href="?statistics=1"><svg class="nav-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="7" cy="7" r="3"/><circle cx="14" cy="7" r="3"/><path d="M1 17c0-3 2.5-5 6-5s6 2 6 5"/></svg> Dosen &amp; Pembimbing</a>
      <a href="?topics=1"><svg class="nav-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="10" cy="10" r="7"/><path d="M10 6v4l3 2"/></svg> Topik Riset</a>
      <button class="search-btn" aria-label="Cari"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="9" r="5"/><path d="M13 13l4 4"/></svg></button>
    </nav>
  </header>
  <main>
    <section class="hero">
      <div class="hero-copy">
        <div class="eyebrow"><span></span> PUSAT KARYA AKADEMIK</div>
        <h1>Temukan referensi.<br><em>Kembangkan ide penelitian.</em></h1>
        <p>Jelajahi kumpulan karya skripsi mahasiswa Teknik Informatika berdasarkan judul, topik, pembimbing, dan tahun.</p>
      </div>
      <div class="hero-stats">
        <div class="stat-card">
          <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 7h6M9 11h6M9 15h4"/></svg></div>
          <div class="stat-value" id="hero-count">&mdash;</div>
          <div class="stat-label">Karya terarsip</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/><path d="M8 7h8M8 11h5"/></svg></div>
          <div class="stat-value">Cari</div>
          <div class="stat-label">Judul dan pembimbing</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><rect x="8" y="14" width="3" height="3" rx="0.5"/></svg></div>
          <div class="stat-value" id="hero-years">&mdash;</div>
          <div class="stat-label">Rentang koleksi</div>
        </div>
      </div>
    </section>

    <section class="search-container">
      <div class="searchbox">
        <svg class="search-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="9" r="5"/><path d="M13 13l4 4"/></svg>
        <input id="search" type="search" placeholder="Cari judul, nama, pembimbing, atau kata kunci..." autocomplete="off">
        <button class="btn-cari" type="button">Cari</button>
      </div>
    </section>

    <section class="popular-topics">
      <span class="popular-label"><svg class="popular-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 1c1.5 3 5 4 5 8a5 5 0 01-10 0c0-4 3.5-5 5-8z"/></svg> Topik populer</span>
      <button class="topic-pill" type="button" data-query="sistem pakar">Artificial Intelligence</button>
      <button class="topic-pill" type="button" data-query="machine learning">Machine Learning</button>
      <button class="topic-pill" type="button" data-query="computer vision">Computer Vision</button>
      <button class="topic-pill" type="button" data-query="android">Mobile Development</button>
      <button class="topic-pill" type="button" data-query="berbasis web">Web Development</button>
      <button class="topic-pill" type="button" data-query="data mining">Data Science</button>
      <button class="topic-pill" type="button" data-query="iot">IoT</button>
      <button class="topic-pill" type="button" data-query="sistem informasi">Sistem Informasi</button>
      <a href="?topics=1" class="topic-pill outline">Lihat semua &rarr;</a>
    </section>

    <section class="filters-shell" aria-label="Filter katalog">
      <div class="filters-row">
        <label class="filter-control">
          <svg class="filter-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3"><rect x="2" y="2" width="12" height="12" rx="1.5"/><path d="M11 1v3M5 1v3M2 7h12"/></svg><span>Tahun</span>
          <select id="year"><option value="">Semua tahun</option></select>
        </label>
        <label class="filter-control">
          <svg class="filter-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M2 2h5v5H2zM9 2h5v5H9zM2 9h5v5H2zM9 9h5v5H9z"/></svg><span>Topik</span>
          <select id="topic"><option value="">Semua topik</option><option value="sistem pakar">Artificial Intelligence</option><option value="machine learning">Machine Learning</option><option value="computer vision">Computer Vision</option><option value="android">Mobile Development</option><option value="berbasis web">Web Development</option><option value="data mining">Data Science</option><option value="iot">IoT</option><option value="sistem informasi">Sistem Informasi</option></select>
        </label>
        <label class="filter-control">
          <svg class="filter-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3"><circle cx="3.5" cy="8" r="2"/><circle cx="12.5" cy="3.5" r="2"/><circle cx="12.5" cy="12.5" r="2"/><path d="M5.3 7l5.4-2.6M5.3 9l5.4 2.6"/></svg><span>Algoritma</span>
          <select id="algorithm"><option value="">Semua algoritma</option><?php foreach (array_keys(algorithm_keywords()) as $name): ?><option value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select>
        </label>
        <label class="filter-control">
          <svg class="filter-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3"><circle cx="6" cy="5" r="2.5"/><path d="M1 14c0-2.5 2.2-4 5-4s5 1.5 5 4"/><circle cx="12" cy="5" r="2"/></svg><span>Pembimbing</span>
          <select id="adviser"><option value="">Semua dosen</option></select>
        </label>
        <label class="filter-control">
          <svg class="filter-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M4 6l4-4 4 4M4 10l4 4 4-4"/></svg><span>Urutan</span>
          <select id="sort"><option value="year_desc">Tahun terbaru</option><option value="year_asc">Tahun terlama</option><option value="title_asc">Judul A-Z</option><option value="name_asc">Nama A-Z</option></select>
        </label>
      </div>
    </section>

    <section class="results-head">
      <h2>Daftar karya <span id="result-count"></span></h2>
      <div class="result-note" id="result-note">Memuat koleksi...</div>
    </section>

    <section id="results" class="records" aria-live="polite"></section>
    <nav id="pagination" class="pagination" aria-label="Navigasi halaman"></nav>
  </main>
  <script src="app.js" defer></script>
</body>
</html>
