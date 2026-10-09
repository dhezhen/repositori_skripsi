<?php
declare(strict_types=1);

// Login admin dan pengelolaan judul skripsi. File ini hanya berisi fungsi; rutenya dipanggil dari index.php.

const LOGIN_MAX_FAILURES = 5;
const LOGIN_WINDOW_SECONDS = 900;
const MANAGE_PAGE_SIZE = 20;

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function is_local_request(): bool {
    return in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
}

function admin_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name('repositori_admin');
    session_set_cookie_params(['httponly'=>true, 'samesite'=>'Strict', 'secure'=>isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    session_start();
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function admin_db(): PDO {
    $pdo = database();
    $pdo->exec('CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE COLLATE NOCASE,
        password_hash TEXT NOT NULL
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS login_attempts (ip TEXT NOT NULL, attempted_at INTEGER NOT NULL)');
    return $pdo;
}

function csrf_field(): string {
    return '<input type="hidden" name="token" value="' . e($_SESSION['csrf']) . '">';
}

function check_csrf(): void {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['token'] ?? ''))) {
        http_response_code(400);
        exit('Permintaan tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}

function current_admin(): ?string {
    admin_session();
    return isset($_SESSION['admin']) ? (string)$_SESSION['admin'] : null;
}

function require_admin(): string {
    $admin = current_admin();
    if ($admin === null) { header('Location: ?login=1'); exit; }
    return $admin;
}

function flash(?string $message = null): string {
    if ($message !== null) { $_SESSION['flash'] = $message; return ''; }
    $stored = (string)($_SESSION['flash'] ?? '');
    unset($_SESSION['flash']);
    return $stored;
}

function admin_page_start(string $title, string $current = ''): void {
    $user = $_SESSION['admin'] ?? null;
    ?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#0d4f91"><meta name="robots" content="noindex"><title><?= e($title) ?> — Repositori FKOM</title><link rel="icon" href="assets/favicon.ico"><link rel="stylesheet" href="style.css"></head><body>
    <header class="topbar"><a class="brand" href="./"><img class="brand-icon" src="assets/fkom-icon.png" alt="Logo FKOM"><span class="brand-name">Repositori Skripsi<small>TEKNIK INFORMATIKA - FKOM UNIKU</small></span></a><nav class="top-menu" aria-label="Menu utama"><a href="./">Katalog</a><?php if ($user !== null): ?><a href="?manage=1"<?= $current === 'manage' ? ' class="current" aria-current="page"' : '' ?>>Kelola Judul</a><?php if (is_local_request()): ?><a href="?admin=1"<?= $current === 'import' ? ' class="current" aria-current="page"' : '' ?>>Impor Excel</a><?php endif; ?><form method="post" action="?logout=1" class="logout-form"><?= csrf_field() ?><button type="submit" title="Keluar dari akun <?= e($user) ?>">Keluar</button></form><?php endif; ?></nav></header>
    <?php
}

function handle_login(): void {
    admin_session();
    $pdo = admin_db();
    $hasAdmin = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if (!$hasAdmin) {
            // Akun pertama hanya boleh dibuat dari komputer server agar tidak bisa diklaim orang lain di hosting.
            if (!is_local_request()) { http_response_code(403); exit('Akun admin pertama hanya dapat dibuat dari komputer server.'); }
            if (!preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $username)) $error = 'Nama pengguna 3–32 karakter: huruf, angka, titik, garis bawah, atau tanda hubung.';
            elseif (strlen($password) < 8) $error = 'Kata sandi minimal 8 karakter.';
            elseif ($password !== (string)($_POST['password_confirm'] ?? '')) $error = 'Konfirmasi kata sandi tidak sama.';
            else {
                $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)')->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
                session_regenerate_id(true);
                $_SESSION['admin'] = $username;
                flash('Akun admin dibuat. Selamat datang, ' . $username . '.');
                header('Location: ?manage=1'); exit;
            }
        } else {
            $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
            $since = time() - LOGIN_WINDOW_SECONDS;
            $pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < ?')->execute([$since]);
            $failures = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ?');
            $failures->execute([$ip]);
            if ((int)$failures->fetchColumn() >= LOGIN_MAX_FAILURES) {
                $error = 'Terlalu banyak percobaan gagal. Coba lagi dalam 15 menit.';
            } else {
                $stmt = $pdo->prepare('SELECT username, password_hash FROM admins WHERE username = ?');
                $stmt->execute([$username]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && password_verify($password, $row['password_hash'])) {
                    $pdo->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([$ip]);
                    if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
                        $pdo->prepare('UPDATE admins SET password_hash = ? WHERE username = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $row['username']]);
                    }
                    session_regenerate_id(true);
                    $_SESSION['admin'] = $row['username'];
                    header('Location: ?manage=1'); exit;
                }
                $pdo->prepare('INSERT INTO login_attempts (ip, attempted_at) VALUES (?, ?)')->execute([$ip, time()]);
                $error = 'Nama pengguna atau kata sandi salah.';
            }
        }
    } elseif (current_admin() !== null) {
        header('Location: ?manage=1'); exit;
    }

    admin_page_start($hasAdmin ? 'Masuk' : 'Buat akun admin');
    ?><main class="admin-main auth-main"><div class="eyebrow"><span></span> AREA PENGELOLA</div>
    <?php if (!$hasAdmin && !is_local_request()): ?>
      <h1>Belum ada akun admin</h1><p>Akun admin pertama dibuat dari komputer server (Laragon) melalui halaman ini, lalu unggah <code>data/repository.sqlite</code> ke hosting.</p>
    <?php else: ?>
      <h1><?= $hasAdmin ? 'Masuk' : 'Buat akun admin' ?></h1>
      <p><?= $hasAdmin ? 'Masuk untuk menambah, mengubah, atau menghapus judul skripsi.' : 'Belum ada akun pengelola. Buat akun pertama untuk mulai mengelola judul.' ?></p>
      <?php if ($error !== ''): ?><div class="admin-message error" role="alert"><?= e($error) ?></div><?php endif; ?>
      <form class="import-form admin-form" method="post" action="?login=1"><?= csrf_field() ?>
        <label for="username">Nama pengguna</label><input id="username" name="username" autocomplete="username" required value="<?= e($_POST['username'] ?? '') ?>" autofocus>
        <label for="password">Kata sandi</label><input id="password" name="password" type="password" autocomplete="<?= $hasAdmin ? 'current-password' : 'new-password' ?>" required>
        <?php if (!$hasAdmin): ?><label for="password_confirm">Ulangi kata sandi</label><input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required minlength="8"><?php endif; ?>
        <button type="submit"><?= $hasAdmin ? 'Masuk' : 'Buat akun dan masuk' ?></button>
      </form>
    <?php endif; ?>
    </main></body></html><?php
    exit;
}

function handle_logout(): void {
    admin_session();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ./'); exit; }
    check_csrf();
    $_SESSION = [];
    session_destroy();
    setcookie(session_name(), '', ['expires'=>time() - 3600, 'path'=>'/', 'httponly'=>true, 'samesite'=>'Strict']);
    header('Location: ?login=1'); exit;
}

function lines_to_list(string $text): array {
    $items = array_map('trim', preg_split('/\R/u', $text) ?: []);
    return array_values(array_filter($items, static fn($item) => $item !== ''));
}

// theses.json menjadi sumber pengisian ulang jika database dibuat ulang, jadi ikut diperbarui setiap ada perubahan.
function export_theses_json(PDO $pdo): void {
    $rows = [];
    foreach ($pdo->query('SELECT name,title,year,program,advisors,examiners FROM theses ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $row['year'] = $row['year'] === null ? null : (int)$row['year'];
        $row['advisors'] = json_decode($row['advisors'], true) ?: [];
        $row['examiners'] = json_decode($row['examiners'], true) ?: [];
        $rows[] = $row;
    }
    @file_put_contents(DATA_FILE, json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
}

function handle_manage(): void {
    require_admin();
    $pdo = admin_db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $id = (int)($_POST['id'] ?? 0);
        $back = (string)($_POST['back'] ?? '?manage=1');
        if (!str_starts_with($back, '?manage=1')) $back = '?manage=1';

        if (($_POST['action'] ?? '') === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM theses WHERE id = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount()) { export_theses_json($pdo); flash('Judul dihapus.'); }
            header('Location: ' . $back); exit;
        }

        $data = [
            'name' => trim((string)($_POST['name'] ?? '')),
            'title' => trim(preg_replace('/\s+/u', ' ', (string)($_POST['title'] ?? ''))),
            'year' => trim((string)($_POST['year'] ?? '')),
            'program' => trim((string)($_POST['program'] ?? '')),
            'advisors' => lines_to_list((string)($_POST['advisors'] ?? '')),
            'examiners' => lines_to_list((string)($_POST['examiners'] ?? '')),
        ];
        $errors = [];
        if ($data['name'] === '') $errors[] = 'Nama mahasiswa wajib diisi.';
        if ($data['title'] === '') $errors[] = 'Judul wajib diisi.';
        if ($data['year'] !== '' && (!ctype_digit($data['year']) || (int)$data['year'] < 1980 || (int)$data['year'] > (int)date('Y') + 1)) $errors[] = 'Tahun tidak valid.';

        if (!$errors) {
            $values = [$data['name'], $data['title'], $data['year'] === '' ? null : (int)$data['year'], $data['program'], json_encode($data['advisors'], JSON_UNESCAPED_UNICODE), json_encode($data['examiners'], JSON_UNESCAPED_UNICODE)];
            if ($id > 0) {
                $values[] = $id;
                $pdo->prepare('UPDATE theses SET name=?, title=?, year=?, program=?, advisors=?, examiners=? WHERE id=?')->execute($values);
                flash('Perubahan judul disimpan.');
            } else {
                $pdo->prepare('INSERT INTO theses (name,title,year,program,advisors,examiners) VALUES (?,?,?,?,?,?)')->execute($values);
                flash('Judul baru ditambahkan.');
            }
            export_theses_json($pdo);
            header('Location: ' . $back); exit;
        }
        render_thesis_form($id, $data, $errors, $back);
    }

    if (isset($_GET['new'])) render_thesis_form(0, null, [], (string)($_GET['back'] ?? '?manage=1'));
    if (isset($_GET['edit'])) {
        $stmt = $pdo->prepare('SELECT * FROM theses WHERE id = ?');
        $stmt->execute([(int)$_GET['edit']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) { flash('Judul tidak ditemukan.'); header('Location: ?manage=1'); exit; }
        $row['advisors'] = json_decode($row['advisors'], true) ?: [];
        $row['examiners'] = json_decode($row['examiners'], true) ?: [];
        render_thesis_form((int)$row['id'], $row, [], (string)($_GET['back'] ?? '?manage=1'));
    }

    $q = trim((string)($_GET['q'] ?? ''));
    $where = $q === '' ? '' : ' WHERE title LIKE :q OR name LIKE :q OR advisors LIKE :q';
    $count = $pdo->prepare('SELECT COUNT(*) FROM theses' . $where);
    if ($q !== '') $count->bindValue(':q', '%' . $q . '%');
    $count->execute();
    $total = (int)$count->fetchColumn();
    $pages = max(1, (int)ceil($total / MANAGE_PAGE_SIZE));
    $page = min($pages, max(1, (int)($_GET['page'] ?? 1)));
    $stmt = $pdo->prepare('SELECT id,name,title,year,advisors FROM theses' . $where . ' ORDER BY id DESC LIMIT :limit OFFSET :offset');
    if ($q !== '') $stmt->bindValue(':q', '%' . $q . '%');
    $stmt->bindValue(':limit', MANAGE_PAGE_SIZE, PDO::PARAM_INT);
    $stmt->bindValue(':offset', ($page - 1) * MANAGE_PAGE_SIZE, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $back = '?manage=1' . ($q !== '' ? '&q=' . rawurlencode($q) : '') . ($page > 1 ? '&page=' . $page : '');
    $pageUrl = static fn(int $n) => '?manage=1' . ($q !== '' ? '&q=' . rawurlencode($q) : '') . '&page=' . $n;
    $message = flash();

    admin_page_start('Kelola Judul', 'manage');
    ?><main class="admin-main manage-main"><div class="eyebrow"><span></span> KELOLA KOLEKSI</div>
      <div class="manage-head"><h1>Kelola judul</h1><a class="btn-primary" href="?manage=1&amp;new=1&amp;back=<?= e(rawurlencode($back)) ?>">+ Tambah judul</a></div>
      <?php if ($message !== ''): ?><div class="admin-message" role="status"><?= e($message) ?></div><?php endif; ?>
      <form class="manage-search" method="get"><input type="hidden" name="manage" value="1"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari judul, nama, atau pembimbing..." aria-label="Cari judul"><button type="submit">Cari</button><?php if ($q !== ''): ?><a href="?manage=1">Reset</a><?php endif; ?></form>
      <p class="manage-count"><?= number_format($total, 0, ',', '.') ?> judul<?= $q !== '' ? ' cocok dengan “' . e($q) . '”' : '' ?></p>
      <div class="manage-list">
      <?php foreach ($rows as $row): $advisors = json_decode($row['advisors'], true) ?: []; ?>
        <div class="manage-row">
          <span class="manage-year"><?= e($row['year'] !== null ? (string)$row['year'] : '–') ?></span>
          <div class="manage-info"><strong><?= e($row['title']) ?></strong><span><?= e($row['name']) ?><?= $advisors ? ' · ' . e(implode(', ', $advisors)) : '' ?></span></div>
          <div class="manage-actions">
            <a href="?manage=1&amp;edit=<?= (int)$row['id'] ?>&amp;back=<?= e(rawurlencode($back)) ?>">Ubah</a>
            <form method="post" action="?manage=1" onsubmit="return confirm('Hapus judul ini? Tindakan tidak dapat dibatalkan.')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="back" value="<?= e($back) ?>"><button type="submit" class="danger">Hapus</button></form>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$rows): ?><div class="empty"><h3>Tidak ada judul</h3><p>Ubah kata kunci pencarian atau tambahkan judul baru.</p></div><?php endif; ?>
      </div>
      <?php if ($pages > 1): ?><nav class="pagination" aria-label="Navigasi halaman">
        <?php if ($page > 1): ?><a class="page-btn arrow" href="<?= e($pageUrl($page - 1)) ?>">&lt; Sebelumnya</a><?php endif; ?>
        <span class="manage-page">Halaman <?= $page ?> dari <?= $pages ?></span>
        <?php if ($page < $pages): ?><a class="page-btn arrow" href="<?= e($pageUrl($page + 1)) ?>">Berikutnya &gt;</a><?php endif; ?>
      </nav><?php endif; ?>
    </main></body></html><?php
    exit;
}

function render_thesis_form(int $id, ?array $data, array $errors, string $back): void {
    if (!str_starts_with($back, '?manage=1')) $back = '?manage=1';
    $data ??= ['name'=>'', 'title'=>'', 'year'=>date('Y'), 'program'=>'', 'advisors'=>[], 'examiners'=>[]];
    admin_page_start($id ? 'Ubah judul' : 'Tambah judul', 'manage');
    ?><main class="admin-main"><div class="eyebrow"><span></span> KELOLA KOLEKSI</div><h1><?= $id ? 'Ubah judul' : 'Tambah judul' ?></h1>
      <?php if ($errors): ?><div class="admin-message error" role="alert"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>
      <form class="import-form admin-form" method="post" action="?manage=1"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="back" value="<?= e($back) ?>">
        <label for="title">Judul skripsi</label><textarea id="title" name="title" rows="3" required><?= e($data['title']) ?></textarea>
        <label for="name">Nama mahasiswa</label><input id="name" name="name" required value="<?= e($data['name']) ?>">
        <div class="form-pair">
          <div><label for="year">Tahun</label><input id="year" name="year" inputmode="numeric" pattern="\d{4}" value="<?= e((string)$data['year']) ?>"></div>
          <div><label for="program">Program studi</label><input id="program" name="program" value="<?= e($data['program']) ?>"></div>
        </div>
        <label for="advisors">Pembimbing</label><textarea id="advisors" name="advisors" rows="2"><?= e(implode("\n", $data['advisors'])) ?></textarea>
        <label for="examiners">Penguji</label><textarea id="examiners" name="examiners" rows="3"><?= e(implode("\n", $data['examiners'])) ?></textarea>
        <p>Pembimbing dan penguji ditulis satu nama per baris.</p>
        <div class="form-actions"><button type="submit">Simpan</button><a href="<?= e($back) ?>">Batal</a></div>
      </form>
    </main></body></html><?php
    exit;
}
