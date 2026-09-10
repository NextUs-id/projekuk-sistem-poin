# SiPoin — Sistem Poin Pelanggaran Siswa

Aplikasi web untuk **SMK TI Bali Global Denpasar** untuk mengelola sistem poin pelanggaran siswa.

## Teknologi
- **Backend:** PHP Native (tanpa framework)
- **Frontend:** HTML + Tailwind CSS v4 (CDN) + Vanilla JavaScript
- **Database:** MySQL (`db_sistem_poin`)

## Menjalankan Aplikasi

```bash
cd projekuk-sistem-poin
php -S localhost:8080 api/index.php
```

Buka browser: **http://localhost:8080**

## Struktur Folder

```
config/database.php    → Koneksi MySQL
api/
  index.php            → Router utama
  helpers.php          → Fungsi bantu (auth, response)
  auth.php             → Login, Logout, Me
  users.php            → CRUD user (Admin)
  siswa.php            → List & Detail siswa
  jenis.php            → CRUD jenis pelanggaran
  pelanggaran.php      → Tambah & Hapus pelanggaran
  surat.php            → Upload & verifikasi surat
  dashboard.php        → Dashboard per role
  log.php              → Log aktivitas
public/
  index.html           → Landing page
  login.html           → Halaman login
  dashboard.html       → Dashboard
  admin/users.html     → Kelola user
  admin/jenis.html     → Kelola jenis pelanggaran
  pelanggaran/input.html → Input pelanggaran
  bk/surat.html        → Kelola surat
  bk/stats.html        → Statistik
  siswa/profil.html    → Profil siswa
  log.html             → Log aktivitas
  js/app.js            → Utilitas JS inti
```

## Role & Akses
| Role | Akses |
|------|-------|
| Admin | Kelola user, jenis pelanggaran, input pelanggaran, log |
| BK | Input pelanggaran, kelola surat, statistik, log |
| Guru | Input pelanggaran (kelas wali), log |
| Kepsek | Statistik, log |
| Siswa | Lihat profil & riwayat sendiri |

## Akun Default (Development)
- `admin` / `password`
- `bk` / `password`
- `guru` / `password`
- `12345` / `password` (siswa)

## Import Database

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS db_sistem_poin CHARACTER SET utf8mb4;"
mysql -u root -p db_sistem_poin < db_sistem_poin_export.sql
```
