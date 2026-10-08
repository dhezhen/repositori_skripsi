"""Membuat deploy/repositori-skripsi.zip berisi hanya file yang dibutuhkan situs.

Workbook Excel sumber (berisi NIK dan tanggal lahir), skrip impor, dan folder kerja lain
sengaja tidak ikut. Jalankan: python build_deploy.py
"""
from pathlib import Path
import sqlite3
import zipfile

ROOT = Path(__file__).resolve().parent
OUTPUT = ROOT / "deploy" / "repositori-skripsi.zip"
FILES = [
    ".htaccess",
    "index.php",
    "algorithms.php",
    "app.js",
    "statistics.js",
    "style.css",
    "site.webmanifest",
    "data/.htaccess",
    "data/repository.sqlite",
    "data/theses.json",
]


def main() -> None:
    files = FILES + sorted(p.relative_to(ROOT).as_posix() for p in (ROOT / "assets").iterdir() if p.is_file())
    missing = [f for f in files if not (ROOT / f).is_file()]
    if missing:
        raise SystemExit("File tidak ditemukan: " + ", ".join(missing))

    with sqlite3.connect(ROOT / "data" / "repository.sqlite") as db:
        total = db.execute("SELECT COUNT(*) FROM theses").fetchone()[0]

    OUTPUT.parent.mkdir(exist_ok=True)
    with zipfile.ZipFile(OUTPUT, "w", zipfile.ZIP_DEFLATED) as archive:
        for name in files:
            archive.write(ROOT / name, name)

    print(f"{OUTPUT.relative_to(ROOT)}: {len(files)} file, {total} judul skripsi, {OUTPUT.stat().st_size / 1024:.0f} KB")


if __name__ == "__main__":
    main()
