# SiPoin — Sistem Poin Pelanggaran
## SMK TI Bali Global Denpasar
### Guidebook Proposal

---

## 1. Cover Page

```
+------------------------------------------------------+
|                                                      |
|           _____ _____ _____ _____ _____              |
|          |   __|   __|     |  _  |   __|             |
|          |   __|__   | | | |   __|   __|             |
|          |_____|_____|_|_|_|__|  |_____|             |
|                                                      |
|     _____ _____ ____  _____ _____ _____              |
|    |   __|_   _|    \ |     |   __|                |
|    |   __| | | |  |  \| | | |   __|                |
|    |_____| |_| |____/|_|_|_|_____|                  |
|                                                      |
|         SISTEM POIN PELANGGARAN SISWA                 |
|              SMK TI BALI GLOBAL DENPASAR              |
|                                                      |
|    ┌──────────────────────────────────────────────┐  |
|    │  [Logo Sekolah]                              │  |
|    │  versi 1.0                                  │  |
|    └──────────────────────────────────────────────┘  |
|                                                      |
+------------------------------------------------------+
```

---

## 2. Table of Contents

```
┌─────────────────────────────────────────────────────┐
│                  DAFTAR ISI                          │
├─────┬───────────────────────────────────────────────┤
│  1  │ Gambaran Sistem                              │
│  2  │ Peran & Hak Akses Pengguna                  │
│  3  │ Cara Login                                   │
│  4  │ Dashboard         [5 role berbeda]            │
│  5  │ Menu & Navigasi                              │
│  6  │ Kelola User          (Admin)                │
│  7  │ Kelola Jenis Pelanggaran (Admin)             │
│  8  │ Kelola Kelas & Jurusan (Admin)              │
│  9  │ Input Pelanggaran     (Admin/BK/Guru)       │
│ 10  │ Kelola Surat          (Admin/BK)            │
│ 11  │ Rekapitulasi Surat    (Admin/BK)            │
│ 12  │ Statistik              (Admin/BK/Kepsek)     │
│ 13  │ Profil Siswa          (Siswa)                │
│ 14  │ Log Aktivitas                              │
│ 15  │ Cetak Dokumen Surat                         │
│ 16  │ API Reference                               │
│ 17  │ Struktur Database                           │
└─────┴───────────────────────────────────────────────┘
```

---

## 3. Gambaran Sistem (System Overview)

```
┌─────────────────────────────────────────────────────────────┐
│                   ARSITEKTUR SISTEM                        │
│                                                             │
│   ┌──────────────┐         ┌──────────────────┐           │
│   │   BROWSER    │◄──────►│   PHP Backend     │           │
│   │  (Frontend)  │  JSON  │   (API Router)   │           │
│   └──────────────┘         └────────┬─────────┘           │
│                                     │                      │
│                              ┌──────▼──────┐              │
│                              │   MySQL      │              │
│                              │  Database    │              │
│                              └─────────────┘              │
└─────────────────────────────────────────────────────────────┘

TEKNOLOGI:
  Frontend : HTML5 + Tailwind CSS v4 + Vanilla JS
  Backend  : PHP Native (tanpa framework)
  Database : MySQL
  Icons    : Lucide Icons (CDN)
  Fonts    : Poppins (Google Fonts CDN)
```

---

## 4. Peran & Hak Akses (Role Access Matrix)

```
┌────────────┬────────┬────────┬────────┬────────┬────────┐
│ Fitur      │ Admin  │   BK   │ Guru   │ Kepsek │ Siswa  │
├────────────┼────────┼────────┼────────┼────────┼────────┤
│ Dashboard  │   ✓    │   ✓    │   ✓    │   ✓    │   ✓    │
├────────────┼────────┼────────┼────────┼────────┼────────┤
│ Kelola     │        │        │        │        │        │
│ Users      │   ✓    │   ✗    │   ✗    │   ✗    │   ✗    │
├────────────┼────────┼────────┼────────┼────────┼────────┤
│ Kelola     │        │        │        │        │        │
│ Jenis      │   ✓    │   ✗    │   ✗    │   ✗    │   ✗    │
│ Pelanggaran│        │        │        │        │        │
├────────────┼────────┼────────┼────────┼────────┼────────┤
│ Kelola     │        │        │        │        │        │
│ Kelas &    │   ✓    │   ✗    │   ✗    │   ✗    │   ✗    │
│ Jurusan    │        │        │        │        │        │
├────────────┼────────┼────────┼────────┼────────┼────────┤
│ Input      │        │        │        │        │        │
│ Pelanggaran│   ✓    │   ✓    │   ✓    │   ✗    │   ✗    │
├────────────┼────────┼────────┼────────┼────────┼────────┤
│ Kelola     │        │        │        │        │        │
│ Surat*     │   ✓    │   ✓    │   ✗    │   ✗    │   ✗    │
├────────────┼────────┼────────┼────────┼────────┼────────┤
│ Rekapitulasi│       │        │        │        │        │
│ Surat      │   ✓    │   ✓    │   ✗    │   ✗    │   ✗    │
├────────────┼────────┼────────┼────────┼────────┼────────┤
│ Statistik  │   ✓    │   ✓    │   ✗    │   ✓    │   ✗    │
├────────────┼────────┼────────┼────────┼────────┼────────┤
│ Log        │        │        │        │        │        │
│ Aktivitas  │   ✓    │   ✓    │   ✓    │   ✓    │   ✗    │
├────────────┼────────┼────────┼────────┼────────┼────────┤
│ Profil     │        │        │        │        │        │
│ Siswa      │   ✓    │   ✓    │   ✗    │   ✗    │   ✓    │
├────────────┼────────┼────────┼────────┼────────┼────────┤
│ Log Aktivitas│  ✓   │   ✓    │   ✓    │   ✓    │   ✗    │
└────────────┴────────┴────────┴────────┴────────┴────────┘

* Surat = Pemanggilan, Perjanjian, DO, Pindah, Pengurangan Poin, Ortu
```

---

## 5. Cara Login

```
┌───────────────────────────────────────────────────────────┐
│                                                           │
│              [NEXUS]   Student Management                 │
│                                                           │
│   ┌─────────────────────────────────────────────────┐    │
│   │                                                 │    │
│   │              [User Avatar Icon]                │    │
│   │                                                 │    │
│   │   ┌───────────────────────────────────────┐   │    │
│   │   │  Username / NIS                        │   │    │
│   │   └───────────────────────────────────────┘   │    │
│   │                                                 │    │
│   │   ┌───────────────────────────────────────┐   │    │
│   │   │  Password                             │   │    │
│   │   └───────────────────────────────────────┘   │    │
│   │                                                 │    │
│   │   ┌───────────────────────────────────────┐   │    │
│   │   │            LOGIN                        │   │    │
│   │   └───────────────────────────────────────┘   │    │
│   │                                                 │    │
│   │   Lupa password? Hubungi administrator.        │    │
│   └─────────────────────────────────────────────────┘    │
│                                                           │
└───────────────────────────────────────────────────────────┘

AKUN DEFAULT (Development):
  admin      / password
  bk         / password
  guru       / password
  12345      / password   (siswa)
```

---

## 6. Halaman Dashboard

### 6a. Dashboard Admin

```
┌─────────────────────────────────────────────────────────────────┐
│ [NEXUS Student Management]          Halo, Admin    [+ Tambah]  │
├────────┬────────────────────────────────────────────────────────┤
│        │                                                        │
│  ▣     │  Dashboard                                             │
│        │                                                        │
│ ───────│                                                        │
│  ▣ Dash│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐        │
│  ▣ SMK TI│  │ Total  │ │ Online │ │ Siswa  │ │ Jenis  │        │
│        │  │ Users  │ │   3    │ │ Aktif  │ │Pelang. │        │
│ ADMIN  │  │   12   │ │        │ │  248   │ │   15   │        │
│  ▣ User│  └────────┘ └────────┘ └────────┘ └────────┘        │
│  ▣ Jenis│                                                       │
│  ▣ Kelas│  ┌──────────────────────────┐ ┌─────────────────┐     │
│ ────────│  │       PERINGATAN        │ │    MENU CEPAT   │     │
│  ▣ Input│  │    ⚠ Total Poin >= 30  │ │                 │     │
│  ▣ Surat│  │       15 siswa          │ │ [👤 Kelola User ]│     │
│  ▣ Log  │  └──────────────────────────┘ │ [📋 Jenis Pel..]│     │
│        │  ┌──────────────────────────┐ │ [➕ Input Pel.. ]│     │
│        │  │ PELANGGARAN TERAKHIR    │ │                 │     │
│        │  │ ┌──────┬───────┬──────┐  │ └─────────────────┘     │
│        │  │ │Siswa │Jenis  │ Poin│  │                        │
│        │  │ ├──────┼───────┼──────┤  │ ┌─────────────────┐     │
│        │  │ │Andi  │Mangk │  +5  │  │ │  LOG TERBARU    │     │
│        │  │ │Budi  │Terlambat│ +3│  │ │ ● Andi - input  │     │
│        │  │ │Citra │Bolos │ +10 │  │ │ ● Budi - input  │     │
│        │  │ └──────┴───────┴──────┘  │ │ ● Guru X - login│     │
│        │  └──────────────────────────┘ └─────────────────┘     │
└────────┴────────────────────────────────────────────────────────┘
```

### 6b. Dashboard BK

```
┌─────────────────────────────────────────────────────────────────┐
│ [NEXUS Student Management]          Halo, Guru BK  [+ Tambah] │
├────────┬────────────────────────────────────────────────────────┤
│        │  Dashboard                                             │
│  ▣     │                                                        │
│        │  ┌────────┐ ┌────────┐ ┌────────┐                     │
│ ───────│  │ Surat  │ │Pelang.│ │ Akurasi │                     │
│  ▣ Dash│  │Pending │ │ (30hr)│ │  Data   │                     │
│  ▣ SMK TI│ │   7    │ │  23   │ │   99%   │                     │
│  ▣ Input│  └────────┘ └────────┘ └────────┘                     │
│  ▣ Surat│                                                       │
│  ▣ Stats│  ┌──────────────────────────┐ ┌─────────────────┐       │
│  ▣ Log  │  │       PERINGATAN        │ │    MENU CEPAT   │       │
│        │  │    ⚠ 7 siswa perlu      │ │                 │       │
│        │  │       surat panggilan    │ │[📝 Input Pel.. ]│       │
│        │  └──────────────────────────┘ │[📁 Kelola Surat]│       │
│        │  ┌──────────────────────────┐ │[📊 Statistik   ]│       │
│        │  │ PELANGGARAN TERAKHIR    │ └─────────────────┘       │
│        │  │ (sama struktur...)     │                         │
│        │  └──────────────────────────┘                         │
└────────┴────────────────────────────────────────────────────────┘
```

### 6c. Dashboard Guru

```
┌─────────────────────────────────────────────────────────────────┐
│ [NEXUS] Student Management              Halo, Guru X  [+ Tambah]│
├────────┬────────────────────────────────────────────────────────┤
│        │  Dashboard                                             │
│  ▣     │                                                        │
│        │  ┌────────────────┐                                   │
│ ───────│  │   KELAS WALI    │                                   │
│  ▣ Dash│  │    X-RPL-1      │                                   │
│  ▣ Input│  └────────────────┘                                   │
│  ▣ Log │                                                        │
│        │  ┌────────────────────┐ ┌────────────────────┐       │
│        │  │ INPUT PELANGGARAN  │ │  LOG AKTIVITAS     │       │
│        │  │ [▶ Buka Formulir] │ │ ● Anda - input     │       │
│        │  └────────────────────┘ │ ● Anda - login     │       │
│        │                         └────────────────────┘       │
└────────┴────────────────────────────────────────────────────────┘
```

### 6d. Dashboard Siswa

```
┌─────────────────────────────────────────────────────────────────┐
│ [NEXUS] Student Management                      Halo, Andi      │
├────────┬────────────────────────────────────────────────────────┤
│        │  Dashboard                                             │
│  ▣     │                                                        │
│        │  ┌────────────┐ ┌────────────┐ ┌────────────┐        │
│ ───────│  │ Total Poin │ │   Kelas    │ │Status Level│        │
│  ▣ Dash│  │    32      │ │  X-RPL-1  │ │   MERAH    │        │
│        │  └────────────┘ └────────────┘ └────────────┘        │
│        │                                                        │
│        │  ┌─────────────────────────────────────────────┐      │
│        │  │ ⚠ PERHATIAN: Poin Anda sudah 32!           │      │
│        │  │    Segera buat surat perjanjian dengan BK.   │      │
│        │  └─────────────────────────────────────────────┘      │
│        │                                                        │
│        │  ┌─────────────────────────────────────────────┐      │
│        │  │ RIWAYAT PELANGGARAN                         │      │
│        │  │ ─────────────────────────────────────────── │      │
│        │  │ 🟡 Terlambat 15 menit          +5  26 Mar   │      │
│        │  │ 🔴 Bolos pelajaran             +10  20 Mar   │      │
│        │  │ 🟡 Mangkir upacara            +5  15 Mar   │      │
│        │  │ 🟡 Tidak seragam              +3  10 Mar   │      │
│        │  └─────────────────────────────────────────────┘      │
└────────┴────────────────────────────────────────────────────────┘
```

---

## 7. Navigasi Sidebar (Semua Role)

```
┌──────────────────────────────────┐
│  [NEXUS] Student Management      │
│  👤 Admin                        │
│  ────────────────────────────────│
│                                  │
│  🏠 Dashboard                    │
│  ✨ Nexus Intelligence           │
│                                  │
│  ── ADMIN ONLY ──────────────    │
│  👥 Kelola Users                │
│  📋 Kelola Kelas & Jurusan      │
│  📝 Jenis Pelanggaran           │
│                                  │
│  ── INPUT ──────────────────    │
│  ✏️ Input Pelanggaran            │
│                                  │
│  ── KELOLA SURAT ───────────    │
│  📁 Surat Orang Tua             │
│  📁 Surat Pemanggilan           │
│  📁 Surat Perjanjian             │
│  📁 Rekapitulasi Surat          │
│  📁 Pengurangan Poin            │
│  📁 Surat DO                    │
│  📁 Surat Pindah                 │
│                                  │
│  ── STATISTIK ───────────────    │
│  📊 Statistik                   │
│                                  │
│  📜 Log Aktivitas               │
│                                  │
│  ────────────────────────────────│
│  [Avatar] Nama Admin            │
│  [Logout]                       │
└──────────────────────────────────┘

Catatan: Menu berubah sesuai role user yang login.
```

---

## 8. Kelola Users (Admin)

```
┌─────────────────────────────────────────────────────────────────┐
│ 👥 Kelola Users                              [+ Tambah User]   │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  [Semua] [Admin] [BK] [Guru] [Kepsek] [Siswa]   ← Filter      │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ USER          │ ROLE & STATUS     │ LAST LOGIN    │ AKSI │  │
│  ├──────────────┼───────────────────┼───────────────┼──────┤  │
│  │ 👤           │                   │               │      │  │
│  │ admin        │ admin   ● Aktif  │ 26 Mar 09:00  │ ✏️🔑│  │
│  │ Administrator │                   │               │  🗑️ │  │
│  ├──────────────┼───────────────────┼───────────────┼──────┤  │
│  │ 👤           │                   │               │      │  │
│  │ bk_guru      │ BK     ● Aktif   │ 26 Mar 08:45  │ ✏️🔑│  │
│  │ Dr. Wardana  │                   │               │  🗑️ │  │
│  ├──────────────┼───────────────────┼───────────────┼──────┤  │
│  │ 👤           │                   │               │      │  │
│  │ 12345        │ Siswa  ● Aktif   │ 25 Mar 17:30  │ ✏️🔑│  │
│  │ Andi Pratama │                   │               │  🗑️ │  │
│  └──────────────┴───────────────────┴───────────────┴──────┘  │
│                                                                 │
│  ┌─ MODAL: TAMBAH USER ────────────────────────────────────┐    │
│  │                                                            │    │
│  │  Username    [_______________]   Nama    [_______________]│    │
│  │                                                            │    │
│  │  Password    [_______________]   Role    [▾ Admin      ]  │    │
│  │                                                            │    │
│  │  Status      [▾ Aktif        ]                             │    │
│  │                                                            │    │
│  │  [siswa-fields - only shown when role=Siswa]              │    │
│  │   Kelas [▾ X-RPL-1      ]  Ortu  [_______________]      │    │
│  │   Kontak [_______________]  Pekerjaan [______________]   │    │
│  │   Alamat [___________________________________________]   │    │
│  │                                                            │    │
│  │                           [Batal]  [💾 Simpan Perubahan]   │    │
│  └────────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

---

## 9. Kelola Jenis Pelanggaran (Admin)

```
┌─────────────────────────────────────────────────────────────────┐
│ 📝 Kelola Jenis Pelanggaran                    [+ Tambah Baru] │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐    │
│  │ NAMA JENIS            │ POIN │ KATEGORI    │ STATUS │AKSI│    │
│  ├───────────────────────┼──────┼─────────────┼────────┼────┤    │
│  │ 1. Bolos Sekolah       │  10  │ Akademik   │  ✓    │ ✏️🗑️│    │
│  │ 2. Terlambat >=15mnt   │   5  │ Kedisiplinan│ ✓    │ ✏️🗑️│    │
│  │ 3. Tidak Seragam        │   3  │ Kedisiplinan│  ✓    │ ✏️🗑️│    │
│  │ 4. Mangkir Upacara     │   5  │ Kedisiplinan│  ✓    │ ✏️🗑️│    │
│  │ 5. Merokok di Sekolah  │  15  │ Kedisiplinan│  ✓    │ ✏️🗑️│    │
│  │ 6. Bolos Piket         │   8  │ Akademik   │  ✓    │ ✏️🗑️│    │
│  └───────────────────────┴──────┴─────────────┴────────┴────┘    │
│                                                                 │
│  ┌─ MODAL: TAMBAH/PERBAIKI JENIS ───────────────────────────┐    │
│  │                                                            │    │
│  │  Nama Jenis Pelanggaran                                    │    │
│  │  [_______________________________________________]          │    │
│  │                                                            │    │
│  │  Poin [____]    Kategori [▾ Kedisiplinan             ]     │    │
│  │                                                            │    │
│  │  [ ] Nonaktif (sembunyikan dari daftar input)             │    │
│  │                                                            │    │
│  │                           [Batal]  [💾 Simpan]             │    │
│  └────────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

---

## 10. Input Pelanggaran (Admin/BK/Guru)

```
┌─────────────────────────────────────────────────────────────────┐
│ ✏️ Input Pelanggaran                                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─ FORM INPUT ────────────────────────────────────────────┐    │
│  │                                                            │    │
│  │  Siswa   [▾ 12345 — Andi Pratama (X-RPL-1) [Poin: 32] ] │    │
│  │                                                            │    │
│  │  Jenis   [▾ Bolos Sekolah                             ] │    │
│  │                                                            │    │
│  │  Tanggal [____2026-03-27________________]               │    │
│  │                                                            │    │
│  │  Keterangan (opsional)                                   │    │
│  │  [_______________________________________________]       │    │
│  │                                                            │    │
│  │                      [💾 SIMPAN PELANGGARAN]               │    │
│  └────────────────────────────────────────────────────────────┘    │
│                                                                 │
│  ┌─ RIWAYAT INPUT ──────────────────────────────────────────┐    │
│  │                                                            │    │
│  │  ┌────────────────────────────────────────────────────┐  │    │
│  │  │ #  │ Siswa       │ Jenis          │ Poin │ Tgl    │  │    │
│  │  ├────┼─────────────┼────────────────┼──────┼────────┤  │    │
│  │  │ 1  │ Budi (X-1) │ Terlambat 15mnt │  +5  │ 27 Mar │  │    │
│  │  │ 2  │ Citra (X-2)│ Bolos           │ +10  │ 27 Mar │  │    │
│  │  └────────────────────────────────────────────────────┘  │    │
│  └────────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

---

## 11. Kelola Surat — Surat Perjanjian (Admin/BK)

```
┌─────────────────────────────────────────────────────────────────┐
│ 📝 Surat Perjanjian                                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─ BUAT SURAT BARU ───────────────────────────────────────┐    │
│  │                                                            │    │
│  │  Siswa   [▾ Pilih Siswa — (Poin: 32)              ]     │    │
│  │  Tanggal [____2026-03-27________________]               │    │
│  │                                                            │    │
│  │  Isi Perjanjian & Pernyataan                              │    │
│  │  [________________________________________________]     │    │
│  │  [Saya berjanji tidak akan bolos sekolah lagi...]        │    │
│  │  [________________________________________________]     │    │
│  │                                                            │    │
│  │                       [💾 SIMPAN DOKUMEN]                  │    │
│  └────────────────────────────────────────────────────────────┘    │
│                                                                 │
│  ┌─ DAFTAR SURAT PERJANJIAN ───────────────────────────────┐    │
│  │                                                            │    │
│  │  ┌────────────────────────────────────────────────────┐  │    │
│  │  │ #  │ Siswa / Kelas      │ Perjanjian       │ Aksi │  │    │
│  │  ├────┼─────────────────────┼─────────────────┼───────┤  │    │
│  │  │ 1  │ Andi - X RPL 1    │ Tidak bolos...  │ [🖨️]│  │    │
│  │  │ 2  │ Budi - X RPL 2     │ Hadir tepat     │ [🖨️]│  │    │
│  │  └────────────────────────────────────────────────────┘  │    │
│  └────────────────────────────────────────────────────────────┘    │
│                                                                 │
│  ┌─ MODAL PRINT PREVIEW ───────────────────────────────────┐    │
│  │                                                            │    │
│  │                    CETAK DOKUMEN           [🖨️ CETAK] ✕  │    │
│  │  ─────────────────────────────────────────────────────    │    │
│  │                                                            │    │
│  │            SURAT PERJANJIAN SISWA                         │    │
│  │            SMK NEXUS SCHOOL INDONESIA                      │    │
│  │                                                            │    │
│  │  Yang bertanda tangan di bawah ini:                       │    │
│  │    Nama    : Andi Pratama                                 │    │
│  │    NIS     : 12345                                        │    │
│  │    Kelas   : X-RPL-1                                      │    │
│  │                                                            │    │
│  │  Dengan ini menyatakan:                                    │    │
│  │  ┌────────────────────────────────────────────────────┐  │    │
│  │  │ 1. Berjanji tidak akan bolos sekolah lagi.          │  │    │
│  │  │ 2. Akan selalu hadir tepat waktu.                   │  │    │
│  │  │ 3. Mentaati semua peraturan sekolah.                 │  │    │
│  │  └────────────────────────────────────────────────────┘  │    │
│  │                                                            │    │
│  │  Denpasar, 27 Maret 2026                                  │    │
│  │                                                            │    │
│  │  Siswa,              Guru BK,            Kepala Sekolah,   │    │
│  │  __________         __________          __________         │    │
│  │                                                            │    │
│  └────────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

---

## 12. Rekapitulasi Surat (Admin/BK) — HALAMAN BARU

```
┌─────────────────────────────────────────────────────────────────┐
│ 📊 Rekapitulasi Surat                              [⬇ Export][🖨️]│
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ TOTAL │ PEMANGGILAN │ PERJANJIAN │   SURAT DO  │ PINDAH │   │
│  │   47  │     15      │     20     │      7     │    5   │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─ FILTER ─────────────────────────────────────────────────┐   │
│  │ Tgl Mulai[___] Tgl Selesai[___] Kelas[▾ Semua   ]      │   │
│  │ Jenis Surat [▾ Semua ▾         ]    [🔍 TERAPKAN]      │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─ PER JENIS ──┐ ┌─ PER KELAS ───────┐ ┌─ PER BULAN ────┐  │
│  │ Pemanggilan ▓▓▓▓ 15│ X-RPL-1   ▓▓▓▓ 8 │ Mar 2026 ▓▓▓▓ 12│  │
│  │ Perjanjian  ▓▓▓▓▓ 20│ X-RPL-2   ▓▓▓▓ 7 │ Feb 2026 ▓▓▓▓ 10│  │
│  │ DO          ▓▓▓   7│ XI-RPL-1  ▓▓▓   6 │ Jan 2026 ▓▓▓▓▓ 15│  │
│  │ Pindah      ▓▓    5│ XI-IPS-1  ▓▓    5 │                │  │
│  └──────────────┘ └──────────────────┘ └────────────────┘       │
│                                                                 │
│  ┌─ DAFTAR SURAT ───────────────────────────────────────────┐   │
│  │ #  │ JENIS SURAT │ SISWA/KELAS    │ TANGGAL │ TAHAP   │   │
│  ├────┼─────────────┼─────────────────┼─────────┼─────────┤   │
│  │ 1  │[PERJANJIAN] │ Andi - X RPL-1 │ 27 Mar  │🟡Kuning│   │
│  │ 2  │[PEMANGGILAN]│ Budi - X RPL-2 │ 26 Mar  │🟡Kuning│   │
│  │ 3  │[   DO    ]  │ Citra - XI-IPS1│ 25 Mar  │🔴Merah│   │
│  │ 4  │[ PERJANJIAN]│ Dedi - X IPS-1 │ 24 Mar  │🟡Kuning│   │
│  │ 5  │[  PINDAH   ]│ Eka - XI-RPL-2 │ 23 Mar  │🔴Merah│   │
│  └────┴─────────────┴─────────────────┴─────────┴─────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 13. Statistik (Admin/BK/Kepsek)

```
┌─────────────────────────────────────────────────────────────────┐
│ 📊 Statistik                                                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─ DISTRIBUSI LEVEL ──────────────────────────────────────┐   │
│  │                                                            │   │
│  │  🟢 Hijau (0-14 poin)   ████████████████████  180 siswa │   │
│  │  🟡 Kuning (15-29 poin) ████████                48 siswa │   │
│  │  🔴 Merah (30+ poin)     ████                  15 siswa  │   │
│  │  ⚫ DO                   █                       5 siswa  │   │
│  │                                                            │   │
│  └────────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─ JENIS PELANGGARAN TERBANYAK ────────────────────────────┐   │
│  │                                                            │   │
│  │  Bolos Sekolah         ████████████████████  45 kali   │   │
│  │  Terlambat >=15 menit  █████████████         38 kali   │   │
│  │  Tidak Seragam         ████████               25 kali   │   │
│  │  Mangkir Upacara       ██████                 18 kali   │   │
│  │                                                            │   │
│  └────────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─ TOP 10 SISWA ─────────────────────────────────────────┐   │
│  │                                                            │   │
│  │  1. 🔴 Citra - XI-IPS-1 ............... 78 poin        │   │
│  │  2. 🔴 Budi  - X-RPL-2  ............... 65 poin        │   │
│  │  3. 🔴 Andi  - X-RPL-1  ............... 55 poin        │   │
│  │                                                            │   │
│  └────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 14. Profil Siswa (Siswa & Admin/BK)

```
┌─────────────────────────────────────────────────────────────────┐
│ 👤 Profil Siswa                           [🔗 Lihat Detail]     │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ 👤 Andi Pratama                                         │   │
│  │    NIS: 12345  |  Kelas: X-RPL-1  |  Wali: Budi S.Pd    │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─ STATUS KEDISIPLINAN ───────────────────────────────────┐   │
│  │                                                            │   │
│  │  🟢 HIJAU (AMAN)                                         │   │
│  │  Poin Saat Ini: 0                                        │   │
│  │                                                            │   │
│  │  ════════════════════════════════════════  0/30         │   │
│  │                                                            │   │
│  └────────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─ RIWAYAT PELANGGARAN ───────────────────────────────────┐   │
│  │                                                            │   │
│  │  ┌────────────────────────────────────────────────────┐  │   │
│  │  │ 🟢 Bolos      │ +10 poin │ 15 Mar 2026           │  │   │
│  │  │ 🟢 Terlambat  │  +5 poin │ 10 Mar 2026           │  │   │
│  │  │ 🔄 [Poin direset otomatis saat naik level]        │  │   │
│  │  └────────────────────────────────────────────────────┘  │   │
│  └────────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─ DATA ORANG TUA ────────────────────────────────────────┐   │
│  │                                                            │   │
│  │  Nama       : Dr. Ir. Budi Santoso, M.Pd                 │   │
│  │  Kontak     : 081234567890                               │   │
│  │  Pekerjaan  : PNS / Dosen                               │   │
│  │  Alamat     : Jl. Ngurah Rai No. 123, Denpasar          │   │
│  │                                                            │   │
│  └────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 15. Log Aktivitas (Semua Role)

```
┌─────────────────────────────────────────────────────────────────┐
│ 📜 Log Aktivitas                                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ ⏰ 27 Mar 2026, 09:15:32                               │   │
│  │ 👤 Dr. Wardana (bk)  ──  INPUT_PELANGGARAN            │   │
│  │    Siswa: Citra (XI-IPS-1) - Bolos Sekolah (+10)      │   │
│  ├──────────────────────────────────────────────────────────┤   │
│  │ ⏰ 27 Mar 2026, 09:10:15                               │   │
│  │ 👤 admin  ──  INPUT_PELANGGARAN                        │   │
│  │    Siswa: Budi (X-RPL-2) - Terlambat (+5)              │   │
│  ├──────────────────────────────────────────────────────────┤   │
│  │ ⏰ 27 Mar 2026, 08:45:00                               │   │
│  │ 👤 Dr. Wardana (bk)  ──  BUAT_SURAT_PERJANJIAN       │   │
│  │    Siswa: Andi (X-RPL-1) - Surat #003/SPT/2026        │   │
│  ├──────────────────────────────────────────────────────────┤   │
│  │ ⏰ 26 Mar 2026, 16:30:00                               │   │
│  │ 👤 admin  ──  NAIK_LEVEL                               │   │
│  │    Siswa: Citra (XI-IPS-1) - Hijau → Kuning            │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 16. Level Sistem (Student Discipline Flow) (Ga Jadi Kepake)

```
┌─────────────────────────────────────────────────────────────────┐
│                    ALUR LEVEL SISWA                            │
│                                                             │
│   ┌─────────┐    ≥30 poin    ┌─────────┐   ≥30 poin   ┌───┐ │
│   │  HIJAU  │ ──────────────►│ KUNING  │ ────────────►│MER│ │
│   │  (Aman) │                │(Pering.)│              │AH │ │
│   │  0-29   │◄───────────────│  30-59  │◄──────────────│   │ │
│   └─────────┘   reset poin   └─────────┘   reset poin└───┘ │
│       ▲                                                      │
│       │                                                      │
│  Poin di-                                                   │
│  reset otomatis                                              │
│  setelah semua                                               │
│  surat lengkap                                               │
│                                                             │
│ DETAIL SETIAP LEVEL:                                         │
│                                                             │
│ 🟢 HIJAU:              [aman, belum ada tindakan]            │
│    Syarat naik:        POIN >= 30                          │
│    Aksi:               Input pelanggaran                     │
│                                                             │
│ 🟡 KUNING:             [perlu perhatian]                    │
│    Syarat naik:        POIN >= 30                          │
│    Syarat turun:       Poin reset + semua surat terpenuhi  │
│    Aksi otomatis:      → Surat Pemanggilan Ortu             │
│                        → Surat Perjanjian Siswa             │
│                                                             │
│ 🔴 MERAH:              [kritis, risiko DO/pindah]           │
│    Syarat naik:        POIN >= 30 + semua surat kuning     │
│    Aksi otomatis:      → Surat Pemanggilan Ortu             │
│                        → Surat DO / Surat Pindah           │
│                                                             │
│ ⚫ DO / PINDAH:        [hak siswa berakhir]                │
│                                                             │
└─────────────────────────────────────────────────────────────────┘
```

---

## 17. Struktur Database (Entity Relationship)

```
┌──────────────────┐       ┌──────────────────┐
│     USERS        │       │     SISWA        │
├──────────────────┤       ├──────────────────┤
│ id (PK)          │──┐    │ id_siswa (PK)   │
│ username         │  │    │ nis (UNIQUE)    │
│ password         │  │    │ nama            │
│ role             │  └───►│ kelas           │
│ nama_asli        │       │ progress_level  │
│ status           │       │ poin_reset_at   │
│ kelas_wali       │       └────────┬─────────┘
│ last_login       │                │
│ is_online         │                │
└──────────────────┘                │
                                      │
┌──────────────────┐       ┌──────────┴─────────┐
│ PELANGGARAN      │       │                    │
├──────────────────┤       │                    │
│ id_pelanggaran   │◄─────│ (FK id_siswa)      │
│ id_siswa (FK)    │       │                    │
│ id_jenis (FK)    │       │                    │
│ tanggal          │       │                    │
│ keterangan       │       │                    │
│ created_by       │       │                    │
│ deleted_at       │       │                    │
└──────────────────┘       │                    │
                            │                    │
┌──────────────────┐       │                    │
│ JENIS_PELANGGARAN │       │                    │
├──────────────────┤       │                    │
│ id_jenis (PK)     │──────►│                    │
│ nama_jenis        │       │                    │
│ poin              │       │                    │
│ kategori          │       │                    │
│ aktif             │       │                    │
└──────────────────┘       │                    │
                            │                    │
┌──────────────────┐       │                    │
│SURAT_PERJANJIAN  │       │                    │
├──────────────────┤       │                    │
│ id_perjanjian(PK)│       │                    │
│ id_siswa (FK)    │──────►│                    │
│ tanggal_perjanjian│       │                    │
│ isi_perjanjian    │       │                    │
└──────────────────┘       │                    │
                            │                    │
┌──────────────────┐       │                    │
│SURAT_PEMANGGILAN │       │                    │
├──────────────────┤       │                    │
│ id (PK)           │       │                    │
│ id_siswa (FK)    │──────►│                    │
│ nomor            │       │                    │
│ tanggal_surat    │       │                    │
│ hari_tanggal     │       │                    │
│ pukul, tempat    │       │                    │
│ keperluan         │       │                    │
└──────────────────┘       │                    │
                          │                    │
┌──────────────────┐       │                    │
│SURAT_TERBIT_LOG  │       │                    │
├──────────────────┤       │                    │
│ id (PK)           │       │                    │
│ id_siswa (FK)    │◄──────│                    │
│ tahap            │       │                    │
│ jenis_surat       │       │                    │
│ id_surat_ref     │       │                    │
│ tanggal_terbit    │       │                    │
└──────────────────┘       │                    │
```

---

## 18. API Endpoints Summary

```
┌─────────────────────────────────────────────────────────────────┐
│                        API REFERENCE                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  AUTH:                                                          │
│    POST /api/auth/login      → Login user                       │
│    POST /api/auth/logout     → Logout user                      │
│    GET  /api/me              → Current user info                │
│                                                                 │
│  USERS:                                                         │
│    GET  /api/users           → List users (filter by role)      │
│    GET  /api/users/detail    → User detail + siswa info         │
│    POST /api/users/create     → Create user                     │
│    POST /api/users/update     → Update user                     │
│    POST /api/users/delete     → Delete user                     │
│    POST /api/users/reset-pw  → Reset password                   │
│                                                                 │
│  SISWA:                                                         │
│    GET  /api/siswa           → List all siswa                   │
│    GET  /api/siswa/detail    → Siswa detail + history           │
│                                                                 │
│  JENIS PELANGGARAN:                                             │
│    GET  /api/jenis           → List jenis pelanggaran           │
│    POST /api/jenis/create    → Create jenis                     │
│    POST /api/jenis/update    → Update jenis                     │
│    POST /api/jenis/delete    → Delete jenis                     │
│                                                                 │
│  PELANGGARAN:                                                   │
│    GET  /api/pelanggaran/recent  → Recent violations            │
│    POST /api/pelanggaran/input  → Input violation               │
│    DELETE /api/pelanggaran/{id}  → Hapus pelanggaran (soft)     │
│                                                                 │
│  SURAT:                                                         │
│    GET  /api/surat/list             → Siswa perlu surat         │
│    POST /api/surat/upload           → Upload file surat         │
│    POST /api/surat/verify           → Verifikasi surat          │
│    GET  /api/surat/rekap            → Rekapitulasi semua        │
│    GET  /api/surat/perjanjian        → List surat perjanjian    │
│    POST /api/surat/perjanjian        → Buat surat perjanjian    │
│    GET  /api/surat/perjanjian/print → Print perjanjian          │
│    GET  /api/surat/pemanggilan       → List pemanggilan         │
│    POST /api/surat/pemanggilan      → Buat pemanggilan          │
│    GET  /api/surat/pemanggilan/print→ Print pemanggilan         │
│    GET  /api/surat/orangtua         → List surat ortu           │
│    POST /api/surat/orangtua         → Buat surat ortu           │
│    GET  /api/surat/do               → List surat DO             │
│    POST /api/surat/do               → Buat surat DO             │
│    GET  /api/surat/do/print         → Print DO                  │
│    GET  /api/surat/pindah           → List surat pindah         │
│    POST /api/surat/pindah           → Buat surat pindah         │
│    GET  /api/surat/pengurangan      → List pengurangan poin     │
│    POST /api/surat/pengurangan      → Catat pengurangan poin    │
│    GET  /api/surat/pejabat          → Data pejabat sekolah      │
│    GET  /api/surat/rekomendasi       → Rekomendasi surat        │
│                                                                 │
│  LOG:                                                           │
│    GET  /api/log               → Log aktivitas                  │
│                                                                 │
│  DASHBOARD:                                                     │
│    GET  /api/dashboard         → Data dashboard per role        │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 19. Technical Stack & Folder Structure

```
SIAPIN PROJECT FILES (root: C:\Users\Andrew\Documents\ProjectUK EXP 2)
│
├── config/
│   └── database.php          ← Koneksi MySQL
│
├── api/
│   ├── index.php             ← Router utama
│   ├── helpers.php           ← Fungsi auth, response
│   ├── auth.php              ← Login, logout, me
│   ├── users.php             ← CRUD user (Admin)
│   ├── siswa.php             ← List & detail siswa
│   ├── jenis.php             ← CRUD jenis pelanggaran
│   ├── pelanggaran.php        ← Input & hapus pelanggaran
│   ├── surat.php             ← Semua endpoint surat
│   ├── dashboard.php         ← Data dashboard per role
│   └── log.php               ← Log aktivitas
│
├── public/
│   ├── login.html             ← Halaman login
│   ├── dashboard.html         ← Dashboard utama
│   ├── index.html             ← Landing page
│   ├── log.html               ← Log aktivitas
│   ├── js/
│   │   └── app.js             ← Utility JS inti
│   ├── admin/
│   │   ├── users.html          ← Kelola users
│   │   ├── jenis.html          ← Kelola jenis
│   │   └── kelola-kelas.html   ← Kelola kelas
│   ├── bk/
│   │   ├── surat.html          ← Kelola surat utama
│   │   ├── surat-perjanjian.html
│   │   ├── surat-pemanggilan.html
│   │   ├── surat-orangtua.html
│   │   ├── surat-do.html
│   │   ├── surat-pindah.html
│   │   ├── surat-pengurangan.html
│   │   ├── rekapitulasi-surat.html    ← (BARU)
│   │   ├── stats.html          ← Statistik
│   │   └── nexus-intelligence.html
│   ├── siswa/
│   │   └── profil.html         ← Profil siswa
│   └── uploads/
│       └── surat/              ← File upload surat
│
├── migrate_surat.sql          ← Schema tabel surat
├── migrate_kelas_jurusan.php
├── run_migrate.php
└── README.md
```

---

## 20. Informasi Tambahan untuk Guidebook

### Cara Menjalankan
```bash
cd "C:\Users\Andrew\Documents\ProjectUK EXP 2"
php -S localhost:8080 api/index.php
```
Buka browser: http://localhost:8080

### Akun Default
| Username | Password | Role |
|----------|----------|------|
| admin | password | Admin |
| bk | password | Guru BK |
| guru | password | Guru | XX (Ga Kepake)
| 12345 | password | Siswa | XX (Ga Kepake)

### Catatan Penting
- Sistem menggunakan **soft delete** untuk pelanggaran (kolom `deleted_at`)
- **Auto reset poin** terjadi saat semua surat di level terpenuhi
- **Auto numbering** surat menggunakan tabel `surat_counter`
- Upload surat mendukung format **JPG, PNG, PDF** (maks 5MB)
- Gambar di-compress otomatis menggunakan GD extension
