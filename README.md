# Repositori Skripsi FKOM

Aplikasi katalog judul skripsi Teknik Informatika berbasis PHP dan SQLite. Data awal diambil dari workbook Excel yang ada di folder proyek.

Antarmuka menggunakan palet biru FKOM. Logo yang disediakan tersedia sebagai ikon header, favicon, dan ikon aplikasi.

## Menjalankan di Laragon

1. Pastikan Apache aktif.
2. Buka `http://localhost/repositori_skripsi/`.
3. Pada akses pertama, aplikasi membuat `data/repository.sqlite` dan mengisinya dari `data/theses.json`.

Fitur yang tersedia: pencarian judul/nama/pembimbing/penguji, filter tahun dan dosen pembimbing, pilihan pengurutan, statistik jumlah bimbingan per dosen, pengelolaan judul oleh admin, serta impor workbook Excel. Impor hanya dapat dibuka dari komputer server oleh admin yang sudah masuk, dan mengganti seluruh koleksi.

## Login admin dan kelola judul

1. Buka `http://localhost/repositori_skripsi/?login=1`. Saat belum ada akun, halaman ini meminta Anda membuat akun admin pertama. Pembuatan akun pertama hanya bisa dilakukan dari komputer server.
2. Setelah masuk, halaman **Kelola Judul** (`?manage=1`) dipakai untuk mencari, menambah, mengubah, dan menghapus judul. Pembimbing dan penguji ditulis satu nama per baris.
3. Akun disimpan di tabel `admins` dalam `data/repository.sqlite`, dengan kata sandi dalam bentuk hash. Lima kali gagal masuk dari IP yang sama akan diblokir selama 15 menit.

Setiap perubahan juga disalin ke `data/theses.json`. Jika `repository.sqlite` dihapus, judul akan dipulihkan dari JSON, tetapi akun admin ikut hilang dan perlu dibuat ulang.

## Memperbarui data dari workbook

Impor bisa dilakukan dari halaman katalog atau dengan menjalankan `python import_data.py` dari folder proyek. Python perlu memiliki paket `openpyxl`; di Laragon, perintah `python` perlu tersedia di PATH. Untuk lokasi Python khusus, atur variabel lingkungan `PYTHON_BINARY` sebelum menjalankan Apache. Setelah impor melalui skrip, hapus `data/repository.sqlite` agar aplikasi membangun ulang database dari JSON terbaru.

Data sumber mempunyai beberapa kolom identitas pribadi. Aplikasi hanya menyimpan nama, judul, tahun, program studi, pembimbing, dan penguji; NIK, tanggal lahir, dan predikat tidak diimpor.

## Hosting di cPanel

Kebutuhan server: PHP 8.1 atau lebih baru dengan ekstensi `pdo_sqlite` dan `mbstring` (keduanya aktif secara bawaan di sebagian besar cPanel). Tidak perlu MySQL.

1. Jalankan `python build_deploy.py`. Hasilnya `deploy/repositori-skripsi.zip`, yang hanya berisi file situs. Workbook Excel sumber, skrip impor, dan folder kerja lain tidak ikut.
2. Di cPanel, buka **Domains** lalu tambahkan domain Anda. Catat folder *document root*-nya, misalnya `public_html` atau `public_html/namadomain.com`.
3. Di registrar domain, arahkan nameserver ke nameserver hosting, atau arahkan A record ke IP server. Propagasi bisa memakan waktu beberapa jam.
4. Buka **File Manager**, masuk ke document root, unggah `repositori-skripsi.zip`, lalu klik **Extract**. Aktifkan *Show Hidden Files* untuk memastikan `.htaccess` ikut terekstrak.
5. Di **Select PHP Version** atau **MultiPHP Manager**, pilih PHP 8.1+ dan pastikan `pdo_sqlite` tercentang.
6. Pastikan folder `data` berizin 755 dan file di dalamnya 644.
7. Setelah domain aktif, jalankan **SSL/TLS Status → Run AutoSSL**. Setelah HTTPS berfungsi, hapus tanda `#` pada dua baris pengalihan HTTPS di `.htaccess`.
8. Periksa: halaman utama tampil, sedangkan `https://domain-anda/data/repository.sqlite` harus menghasilkan **403 Forbidden**.

Untuk hosting, buat akun admin di Laragon terlebih dahulu, lalu unggah `data/repository.sqlite` sekali; setelah itu login dan kelola judul bisa langsung dilakukan di hosting lewat `?login=1`. Perhatikan bahwa mengunggah ulang `repository.sqlite` dari Laragon akan menimpa perubahan yang dibuat langsung di hosting. Halaman impor (`?admin=1`) hanya bisa dibuka dari komputer server, sehingga di hosting akan menolak akses. Untuk memperbarui data, lakukan impor di Laragon, jalankan lagi `python build_deploy.py`, lalu unggah ulang `data/repository.sqlite` saja ke folder `data` di hosting.

Nama dosen pembimbing dinormalisasi saat impor agar variasi ejaan, singkatan, gelar, dan tanda baca tidak muncul sebagai pilihan dosen terpisah.
