<?php
declare(strict_types=1);

// Daftar algoritma/metode per kategori beserta kata kunci judulnya.
// Kata kunci ditulis huruf kecil; tanda hubung dan spasi ganda dinormalisasi otomatis,
// jadi "fisher yates" juga cocok dengan "Fisher-Yates". Ejaan salah yang ada di data ikut dicantumkan.
function algorithm_groups(): array {
    return [
        'Pengacakan & Bilangan Acak' => [
            'Fisher-Yates Shuffle' => ['fisher yates', 'fisher yate', 'fys'],
            'Shuffle Random' => ['shuffle random', 'suffle random', 'naive shuffle'],
            'Linear Congruential Generator (LCG/LCM)' => ['linear congruent', 'linier congruent', 'linear congruential', 'linier congruential', 'linier congurential', 'lcg', 'lcm'],
            'Multiplicative Congruential RNG' => ['multiplicative congruential', 'multiplicative crng', 'mcrng', 'congruental random number'],
            'Blum Blum Shub' => ['blum blum shub', 'bbs'],
            'Mersenne Twister' => ['mersenne twister'],
            'Random Number Generator (RNG)' => ['rng', 'random number generator'],
        ],
        'Kriptografi & Keamanan' => [
            'AES / Rijndael' => ['aes', 'advanced encryption standard', 'rijndael'],
            'RSA' => ['rsa', 'rivest shamir adleman', 'ron rivest'],
            'DES / Triple DES' => ['des', 'data encryption standard', 'triple des', '3des'],
            'Base64' => ['base64', 'base 64'],
            'Caesar Cipher' => ['caesar', 'chaesar'],
            'Vigenere Cipher' => ['vigenere', 'vignere'],
            'Hill Cipher' => ['hill cipher', 'hill chiper'],
            'Speck' => ['speck'],
            'Blowfish' => ['blowfish'],
            'ElGamal' => ['elgamal'],
            'ECDSA' => ['ecdsa'],
            'Cipher Block Chaining (CBC)' => ['cipher block chaining', 'cbc'],
            'Reverse Cipher' => ['reverse cipher'],
            'Vertical Bit Rotation' => ['vertical bit rotation'],
            'SHA / Tiger Hash' => ['sha 512', 'sha 256', 'sha 1', 'sha512', 'sha256', 'tiger hash'],
            'Time-Based OTP (TOTP)' => ['time based one time password', 'totp'],
            'LSB / Steganografi' => ['lsb', 'least significant bit', 'steganografi'],
        ],
        'Kompresi' => [
            'Huffman' => ['huffman', 'hoffmah'],
            'Run Length Encoding (RLE)' => ['run length', 'rle'],
            'Lempel-Ziv-Welch (LZW)' => ['lempel ziv', 'lzw'],
            'AV1 / AVIF' => ['av1', 'avif'],
        ],
        'Pencocokan String' => [
            'Rabin-Karp' => ['rabin karp'],
            'Knuth-Morris-Pratt (KMP)' => ['knuth morris', 'kmp'],
            'Boyer-Moore' => ['boyer moore'],
            'Horspool' => ['horspool'],
            'Brute Force' => ['brute force'],
            'Levenshtein Distance' => ['levenshtein', 'levensthein'],
            'Winnowing' => ['winnowing'],
            'Cosine Similarity / TF-IDF' => ['cosine similarity', 'tf idf'],
        ],
        'Pencarian Jalur & Graf' => [
            'A* (A-Star)' => ['a*', 'a star'],
            'Dynamic Weighting A* (DWA)' => ['dynamic weighting', 'dwa'],
            'Dijkstra' => ['dijkstra', 'djikstra'],
            'Floyd-Warshall' => ['floyd warshall'],
            'Breadth First Search (BFS)' => ['breadth first', 'bfs'],
            'Depth First Search (DFS)' => ['depth first', 'dfs'],
            'Jump Point Search' => ['jump point search'],
            'Backtracking' => ['backtracking', 'backtraking'],
            'Greedy' => ['greedy'],
            'Binary Search' => ['binary search'],
        ],
        'Game AI & Simulasi' => [
            'Minimax' => ['minimax'],
            'NegaScout' => ['negascout'],
            'Algoritma Genetika' => ['genetika', 'genetic algorithm'],
            'Boids' => ['boids'],
            'Collision Detection' => ['collision detection', 'collisionndetection'],
        ],
        'Pengolahan Citra & Augmented Reality' => [
            'CNN / Deep Learning' => ['cnn', 'convolutional neural', 'convolution neural', 'covolutional neural', 'deep learning', 'transfer learning', 'resnet', 'mobilenet'],
            'YOLO' => ['yolo', 'you only look once'],
            'FAST Corner Detection' => ['fast corner', 'features from accelerated segment test', 'features from acceierated segment test'],
            'Harris Corner Detection' => ['harris corner', 'haris corner'],
            'ORB (Oriented FAST and Rotated BRIEF)' => ['orb', 'oriented fast'],
            'SIFT' => ['sift', 'scale invariant feature transform'],
            'SURF' => ['surf', 'speeded up robust', 'speed up robust', 'speed up with robust'],
            'Occlusion Based' => ['occlusion based', 'occlusion base'],
            'Haar Cascade' => ['haar', 'haer cascade'],
            'Sobel' => ['sobel'],
            'Canny' => ['canny'],
            'Robert Cross' => ['robert cross', 'roberts cross'],
            'Kirsch' => ['kirsch'],
            'Hough Transform' => ['hough'],
            'Lucas-Kanade' => ['lucas kanade'],
            'SUSAN' => ['susan', 'smallest univalue segment'],
            'Fisherface' => ['fisherface'],
            'Background Subtraction' => ['background subtraction', 'backgroud subtraction'],
            'Transformasi Wavelet' => ['wavelet'],
            'Discrete Cosine Transform (DCT)' => ['discrete cosine transform'],
            'Fast Fourier Transform (FFT)' => ['fast fourier', 'fft'],
        ],
        'Machine Learning & Data Mining' => [
            'Naive Bayes' => ['naive bayes', 'naïve bayes'],
            'K-Means / Clustering' => ['k means', 'kmeans', 'clustering', 'c means'],
            'K-Nearest Neighbor (KNN)' => ['knn', 'k nearest'],
            'C4.5 / Decision Tree' => ['c4.5', 'c45', 'decision tree', 'pohon keputusan'],
            'Random Forest' => ['random forest'],
            'Apriori / FP-Growth' => ['apriori', 'fp growth', 'association rule'],
            'Jaringan Saraf Tiruan (ANN/MLP)' => ['artificial neural network', 'multilayer perceptron', 'backpropagation', 'jaringan syaraf tiruan', 'jaringan saraf tiruan'],
            'Regresi Linear' => ['linear regression', 'regresi linear', 'regresi linier'],
            'Single Exponential Smoothing' => ['exponential smoothing'],
            'Collaborative / Content-Based Filtering' => ['collaborative filtering', 'content based filtering', 'content based filltering'],
            'Bayesian Knowledge Tracing' => ['bayesian knowledge tracing'],
        ],
        'Sistem Pendukung Keputusan & Sistem Pakar' => [
            'AHP' => ['ahp', 'analytical hierarchy process', 'analytic hierarchy process'],
            'SAW (Simple Additive Weighting)' => ['saw', 'simple additive weighting', 'simple additive waighting'],
            'TOPSIS' => ['topsis'],
            'Weighted Product' => ['weighted product'],
            'Profile Matching' => ['profile matching'],
            'Certainty Factor' => ['certainty factor'],
            'Forward / Backward Chaining' => ['forward chaining', 'backward chaining'],
        ],
        'Logika Fuzzy' => [
            'Fuzzy Sugeno' => ['sugeno', 'sugero'],
            'Fuzzy Mamdani' => ['mamdani', 'mandani'],
            'Fuzzy Tsukamoto' => ['tsukamoto'],
            'Fuzzy Logic (umum)' => ['fuzzy'],
        ],
        'Geometri, Lokasi & Antrian' => [
            'Point in Polygon' => ['point in polygon', 'point in poligon', 'poin in polygon'],
            'Haversine' => ['haversine'],
            'Euclidean Distance' => ['euclidean'],
            'Geofencing' => ['geofencing'],
            'Digital Differential Analyzer' => ['digital differential'],
            'Round Robin' => ['round robin'],
            'Multilevel Feedback Queue' => ['multilevel feedback queue'],
            'FIFO' => ['fifo', 'first in first out'],
            'Multi Channel Single Phase' => ['multi chanel single phase', 'multi channel single phase'],
        ],
        'Lainnya' => [
            'Hargreaves-Samani' => ['hargreaves'],
            'Silence Removal' => ['silence removal'],
        ],
    ];
}

function algorithm_keywords(): array {
    return array_merge(...array_values(algorithm_groups()));
}

function normalize_title(string $text): string {
    return trim((string)preg_replace('/\s+/u', ' ', str_replace('-', ' ', mb_strtolower($text))));
}

// Cocok per kata utuh, supaya "rsa" tidak ikut menangkap "bersama" dan "saw" tidak menangkap "sawah".
function algorithm_pattern(array $keywords): string {
    $alternatives = array_map(static fn($keyword) => preg_quote(normalize_title($keyword), '/'), $keywords);
    return '/(?<![\p{L}\p{N}])(?:' . implode('|', $alternatives) . ')(?![\p{L}\p{N}])/u';
}

// Nama algoritma => daftar id skripsi yang judulnya cocok. Dihitung sekali per request.
function algorithm_index(PDO $pdo): array {
    static $index = null;
    if ($index !== null) return $index;
    $patterns = array_map('algorithm_pattern', algorithm_keywords());
    $index = array_fill_keys(array_keys($patterns), []);
    foreach ($pdo->query('SELECT id, title FROM theses')->fetchAll(PDO::FETCH_KEY_PAIR) as $id => $title) {
        $title = normalize_title((string)$title);
        foreach ($patterns as $name => $pattern) if (preg_match($pattern, $title)) $index[$name][] = (int)$id;
    }
    return $index;
}
