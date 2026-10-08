# Repositori Skripsi FKOM

Aplikasi katalog judul skripsi Teknik Informatika berbasis PHP dan SQLite. Data awal diambil dari workbook Excel yang ada di folder proyek.

Antarmuka menggunakan palet biru FKOM. Logo yang disediakan tersedia sebagai ikon header, favicon, dan ikon aplikasi.

## Menjalankan di Laragon

1. Pastikan Apache aktif.
2. Buka `http://localhost/repositori_skripsi/`.
3. Pada akses pertama, aplikasi membuat `data/repository.sqlite` dan mengisinya dari `data/theses.json`.

Fitur yang tersedia: pencarian judul/nama/pembimbing/penguji, filter tahun dan dosen pembimbing, pilihan pengurutan, statistik jumlah bimbingan per dosen, serta impor workbook Excel dari tautan **Impor data** di bagian bawah halaman. Impor hanya dapat dibuka dari komputer server dan mengganti seluruh koleksi.

## Memperbarui data dari workbook

Impor bisa dilakukan dari halaman katalog atau dengan menjalankan `python import_data.py` dari folder proyek. Python perlu memiliki paket `openpyxl`; di Laragon, perintah `python` perlu tersedia di PATH. Untuk lokasi Python khusus, atur variabel lingkungan `PYTHON_BINARY` sebelum menjalankan Apache. Setelah impor melalui skrip, hapus `data/repository.sqlite` agar aplikasi membangun ulang database dari JSON terbaru.

Data sumber mempunyai beberapa kolom identitas pribadi. Aplikasi hanya menyimpan nama, judul, tahun, program studi, pembimbing, dan penguji; NIK, tanggal lahir, dan predikat tidak diimpor.

Nama dosen pembimbing dinormalisasi saat impor agar variasi ejaan, singkatan, gelar, dan tanda baca tidak muncul sebagai pilihan dosen terpisah.
