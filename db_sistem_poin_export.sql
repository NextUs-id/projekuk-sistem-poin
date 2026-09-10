-- ============================================================
-- SiPoin — Sistem Poin Pelanggaran
-- SMK TI Bali Global Denpasar
-- Full Database Export
-- Generated: 2026-04-05 16:13:35
-- ============================================================

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+08:00';
SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- Buat database jika belum ada
CREATE DATABASE IF NOT EXISTS `db_sistem_poin` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_sistem_poin`;

-- ============================================================

-- ── Tabel: `aturan_poin` ────────────────────────────────────
DROP TABLE IF EXISTS `aturan_poin`;
CREATE TABLE `aturan_poin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `batas_aman` int NOT NULL,
  `batas_peringatan` int NOT NULL,
  `batas_pembinaan` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `aturan_poin` (`id`, `batas_aman`, `batas_peringatan`, `batas_pembinaan`) VALUES
(1, 25, 50, 51);

-- ── Tabel: `jenis_pelanggaran` ────────────────────────────────────
DROP TABLE IF EXISTS `jenis_pelanggaran`;
CREATE TABLE `jenis_pelanggaran` (
  `id_jenis` int NOT NULL AUTO_INCREMENT,
  `nama_jenis` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `poin` int NOT NULL,
  `kode_kategori` enum('SS','KS','PBM','PNN','PB','KB','UB') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_jenis`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `jenis_pelanggaran` (`id_jenis`, `nama_jenis`, `deskripsi`, `poin`, `kode_kategori`) VALUES
(1, 'Terlambat', 'Terlambat datang ke sekolah', 8, 'KS'),
(2, 'Tidak memakai atribut lengkap', 'Atribut tidak lengkap', 5, 'SS'),
(3, 'Merokok', 'Merokok di lingkungan sekolah', 15, 'PNN'),
(4, 'Test 30 Point', 'Di buat untuk test system', 30, NULL),
(8, 'Tidak Mengikuti Upacara Bendera', 'Tidak mengikuti Upacara Bendera (kecuali sakit)', 5, 'UB'),
(9, 'Bully', 'Bullys', 10, 'PB'),
(10, 'Tidak Memakai Dasi', NULL, 5, 'SS');

-- ── Tabel: `jurusans` ────────────────────────────────────
DROP TABLE IF EXISTS `jurusans`;
CREATE TABLE `jurusans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `jurusans` (`id`, `kode`, `nama`, `created_at`) VALUES
(2, 'RPL', 'Rekayasa Perangkat Lunak', '2026-03-02 18:30:11'),
(4, 'DKV', 'Desain Komunikasi Visual', '2026-03-02 18:30:11'),
(6, 'MP', 'Manajemen Perkantoran', '2026-03-02 18:30:11'),
(8, 'BD', 'Bisnis Digital', '2026-04-05 23:48:49');

-- ── Tabel: `kelass` ────────────────────────────────────
DROP TABLE IF EXISTS `kelass`;
CREATE TABLE `kelass` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tingkat` enum('X','XI','XII') COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_jurusan` int NOT NULL,
  `nomor` int NOT NULL DEFAULT '1',
  `id_wali_guru` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kelas` (`tingkat`,`id_jurusan`,`nomor`),
  KEY `id_jurusan` (`id_jurusan`),
  KEY `id_wali_guru` (`id_wali_guru`),
  CONSTRAINT `kelass_ibfk_1` FOREIGN KEY (`id_jurusan`) REFERENCES `jurusans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kelass_ibfk_2` FOREIGN KEY (`id_wali_guru`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `kelass` (`id`, `tingkat`, `id_jurusan`, `nomor`, `id_wali_guru`, `created_at`) VALUES
(2, 'X', 2, 1, 3, '2026-03-04 10:56:32'),
(3, 'XI', 4, 1, 28, '2026-03-09 11:13:20'),
(5, 'X', 2, 2, NULL, '2026-03-09 11:13:44'),
(6, 'XII', 8, 1, NULL, '2026-04-05 23:48:57');

-- ── Tabel: `log_aktivitas` ────────────────────────────────────
DROP TABLE IF EXISTS `log_aktivitas`;
CREATE TABLE `log_aktivitas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `aksi` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `actor_user_id` int DEFAULT NULL,
  `actor_role` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `siswa_id` int DEFAULT NULL,
  `pelanggaran_id` int DEFAULT NULL,
  `alasan` text COLLATE utf8mb4_unicode_ci,
  `metadata` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_log_created_at` (`created_at`),
  KEY `idx_log_aksi` (`aksi`),
  KEY `idx_log_siswa` (`siswa_id`),
  KEY `idx_log_pelanggaran` (`pelanggaran_id`)
) ENGINE=InnoDB AUTO_INCREMENT=148 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `log_aktivitas` (`id`, `aksi`, `actor_user_id`, `actor_role`, `siswa_id`, `pelanggaran_id`, `alasan`, `metadata`, `created_at`) VALUES
(1, 'tambah_pelanggaran', 2, 'bk', 3, 2, NULL, '{"jenis":"Tidak memakai atribut lengkap","poin":-5,"tanggal":"2026-01-13","keterangan":"Belum pakai Dasi"}', '2026-01-13 10:40:02'),
(2, 'tambah_pelanggaran', 2, 'bk', 5, 3, NULL, '{"jenis":"Terlambat","poin":-10,"tanggal":"2026-01-12","keterangan":"Terlambat 13 menit"}', '2026-01-13 10:40:29'),
(3, 'tambah_pelanggaran', 2, 'bk', 3, 4, NULL, '{"jenis":"Terlambat","poin":-10,"tanggal":"2026-01-13","keterangan":"Terlambat 1 Jam"}', '2026-01-13 10:40:49'),
(4, 'hapus_pelanggaran', 2, 'bk', 3, 4, 'Mau ngubah bukan 1 jam, kesalahan input', '{"jenis":"Terlambat","poin":-10,"tanggal":"2026-01-13","keterangan":"Terlambat 1 Jam","created_by_user_id":2,"siswa_nis":"6543","siswa_nama":"Siosida"}', '2026-01-13 10:41:37'),
(5, 'tambah_pelanggaran', 2, 'bk', 3, 5, NULL, '{"jenis":"Terlambat","poin":-10,"tanggal":"2026-01-13","keterangan":"Terlambat 30 menit"}', '2026-01-13 10:42:00'),
(6, 'pengurangan_poin', 2, 'bk', 5, NULL, NULL, '{"tanggal":"2026-01-13","jumlah_pengurangan":3,"keterangan":"Berbuat baik","pengurangan_id":1}', '2026-01-13 10:43:50'),
(7, 'tambah_pelanggaran', 2, 'bk', 3, 6, NULL, '{"jenis":"Merokok","poin":-50,"tanggal":"2026-01-14","keterangan":"Merokok di kelas"}', '2026-01-13 10:44:59'),
(8, 'rekomendasi_panggilan_ortu', 2, 'bk', 3, 6, NULL, '{"pesan":"Sistem merekomendasikan cetak surat panggilan orang tua.","total_poin":-65,"batas_pembinaan":51}', '2026-01-13 10:44:59'),
(9, 'hapus_pelanggaran', 2, 'bk', 3, 6, 'Test kembali', '{"jenis":"Merokok","poin":-50,"tanggal":"2026-01-14","keterangan":"Merokok di kelas","created_by_user_id":2,"siswa_nis":"6543","siswa_nama":"Siosida"}', '2026-01-13 10:49:24'),
(10, 'tambah_pelanggaran', 2, 'bk', 3, 7, NULL, '{"jenis":"Merokok","poin":-50,"tanggal":"2026-01-13","keterangan":"Test"}', '2026-01-13 10:53:56'),
(11, 'tambah_pelanggaran', 2, 'bk', 3, 8, NULL, '{"jenis":"Terlambat","poin":-8,"tanggal":"2026-01-14","keterangan":""}', '2026-01-14 11:48:37'),
(12, 'progress_tahap', 2, 'bk', 3, 8, NULL, '{"tahap":1,"aksi":"surat_orangtua+perjanjian","total_poin":-36,"batas":30}', '2026-01-14 11:48:37'),
(13, 'tambah_pelanggaran', 2, 'bk', 5, 15, NULL, '{"jenis":"Merokok","poin":-15,"tanggal":"2026-01-20","keterangan":""}', '2026-01-20 09:10:55'),
(14, 'tambah_pelanggaran', 2, 'bk', 5, 16, NULL, '{"jenis":"Merokok","poin":-15,"tanggal":"2026-01-20","keterangan":""}', '2026-01-20 09:11:15'),
(15, 'tambah_pelanggaran', 2, 'bk', 3, 17, NULL, '{"jenis":"Merokok","poin":-15,"tanggal":"2026-01-20","keterangan":""}', '2026-01-20 09:26:57'),
(16, 'tambah_pelanggaran', 2, 'bk', 3, 18, NULL, '{"jenis":"Terlambat","poin":-8,"tanggal":"2026-01-20","keterangan":""}', '2026-01-20 09:27:06'),
(17, 'tambah_pelanggaran', 2, 'bk', 7, 19, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-01-20","keterangan":""}', '2026-01-20 09:35:39'),
(18, 'tambah_pelanggaran', 2, 'bk', 8, 20, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-01-20","keterangan":""}', '2026-01-20 09:42:22'),
(19, 'tambah_pelanggaran', 2, 'bk', 9, 21, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-01-20","keterangan":""}', '2026-01-20 10:29:28'),
(20, 'tambah_pelanggaran', 2, 'bk', 9, 22, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-01-20","keterangan":""}', '2026-01-20 10:30:25'),
(21, 'tambah_pelanggaran', 2, 'bk', 9, 23, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-01-20","keterangan":""}', '2026-01-20 10:31:17'),
(22, 'login', 1, 'admin', NULL, NULL, NULL, '{"ip":"::1"}', '2026-01-27 20:26:28'),
(23, 'login', 2, 'bk', NULL, NULL, NULL, '{"ip":"::1"}', '2026-01-27 20:27:01'),
(24, 'login', 1, 'admin', NULL, NULL, NULL, '{"ip":"::1"}', '2026-01-27 20:27:20'),
(25, 'tambah_pelanggaran', 1, 'admin', 11, 24, NULL, '{"jenis":"Tidak memakai atribut lengkap","poin":-5,"tanggal":"2026-01-31","keterangan":""}', '2026-01-31 08:00:53'),
(26, 'hapus_pelanggaran', 1, 'admin', 11, 24, 'test', '{"jenis":"Tidak memakai atribut lengkap","poin":-5,"tanggal":"2026-01-31","keterangan":"","created_by_user_id":1,"siswa_nis":"2348","siswa_nama":"Yosep"}', '2026-01-31 08:01:06'),
(27, 'tambah_pelanggaran', 2, 'bk', 12, 25, NULL, '{"jenis":"Test 30 Point","poin":-30,"tanggal":"2026-02-01","keterangan":""}', '2026-02-01 18:36:40'),
(28, 'tambah_pelanggaran', 1, 'admin', 3, 26, NULL, '{"jenis":"Merokok","poin":-15,"tanggal":"2026-02-02","keterangan":""}', '2026-02-02 10:55:20'),
(29, 'hapus_pelanggaran', 1, 'admin', 3, 26, 'test', '{"jenis":"Merokok","poin":-15,"tanggal":"2026-02-02","keterangan":"","created_by_user_id":1,"siswa_nis":"6543","siswa_nama":"Siosida"}', '2026-02-02 11:00:22'),
(30, 'tambah_pelanggaran', 1, 'admin', 3, 27, NULL, '{"jenis":"Merokok","poin":-15,"tanggal":"2026-02-02","keterangan":""}', '2026-02-02 11:00:34'),
(31, 'tambah_pelanggaran', 1, 'admin', 3, 28, NULL, '{"jenis":"Merokok","poin":-15,"tanggal":"2026-02-02","keterangan":""}', '2026-02-02 11:00:56'),
(32, 'tambah_pelanggaran', 1, 'admin', 3, 29, NULL, '{"jenis":"Merokok","poin":-15,"tanggal":"2026-02-02","keterangan":""}', '2026-02-02 11:01:10'),
(33, 'hapus_pelanggaran', 1, 'admin', 3, 29, 'test', '{"jenis":"Merokok","poin":-15,"tanggal":"2026-02-02","keterangan":"","created_by_user_id":1,"siswa_nis":"6543","siswa_nama":"Siosida"}', '2026-02-02 11:01:50'),
(34, 'hapus_pelanggaran', 1, 'admin', 3, 28, 'test', '{"jenis":"Merokok","poin":-15,"tanggal":"2026-02-02","keterangan":"","created_by_user_id":1,"siswa_nis":"6543","siswa_nama":"Siosida"}', '2026-02-02 11:14:38'),
(35, 'tambah_pelanggaran', 1, 'admin', 3, 30, NULL, '{"jenis":"Merokok","poin":-15,"tanggal":"2026-02-02","keterangan":""}', '2026-02-02 11:14:51'),
(36, 'hapus_pelanggaran', 1, 'admin', 3, 30, 'test', '{"jenis":"Merokok","poin":-15,"tanggal":"2026-02-02","keterangan":"","created_by_user_id":1,"siswa_nis":"6543","siswa_nama":"Siosida"}', '2026-02-02 11:15:03'),
(37, 'tambah_pelanggaran', 1, 'admin', 3, 31, NULL, '{"jenis":"Merokok","poin":-15,"tanggal":"2026-02-02","keterangan":""}', '2026-02-02 11:27:31'),
(38, 'hapus_pelanggaran', 1, 'admin', 3, 31, 'test', '{"jenis":"Merokok","poin":-15,"tanggal":"2026-02-02","keterangan":"","created_by_user_id":1,"siswa_nis":"6543","siswa_nama":"Siosida"}', '2026-02-02 11:27:41'),
(39, 'tambah_pelanggaran', 1, 'admin', 3, 32, NULL, '{"jenis":"Merokok","poin":-15,"tanggal":"2026-02-02","keterangan":""}', '2026-02-02 11:33:28'),
(40, 'hapus_pelanggaran', 1, 'admin', 3, 32, 'test', '{"jenis":"Merokok","poin":-15,"tanggal":"2026-02-02","keterangan":"","created_by_user_id":1,"siswa_nis":"6543","siswa_nama":"Siosida"}', '2026-02-02 11:33:35'),
(41, 'tambah_pelanggaran', 1, 'admin', 3, 33, NULL, '{"jenis":"Test 30 Point","poin":-30,"tanggal":"2026-02-02","keterangan":""}', '2026-02-02 11:33:39'),
(42, 'hapus_pelanggaran', 1, 'admin', 3, 33, 'test', '{"jenis":"Test 30 Point","poin":-30,"tanggal":"2026-02-02","keterangan":"","created_by_user_id":1,"siswa_nis":"6543","siswa_nama":"Siosida"}', '2026-02-02 11:33:45'),
(43, 'tambah_pelanggaran', 2, 'bk', 15, 34, NULL, '{"jenis":"Test 30 Point","poin":-30,"tanggal":"2026-02-02","keterangan":""}', '2026-02-02 11:40:29'),
(44, 'naik_level', 2, 'bk', 15, NULL, 'Surat Orang Tua Terkirim', '{"from":0,"to":1,"id_surat":14,"reset_at":"2026-02-02 03:41:03"}', '2026-02-02 11:41:03'),
(45, 'naik_level', 2, 'bk', 15, NULL, 'Surat Orang Tua Terkirim', '{"from":1,"to":2,"id_surat":15,"reset_at":"2026-02-02 03:45:04"}', '2026-02-02 11:45:04'),
(46, 'tambah_pelanggaran', 2, 'bk', 16, 35, NULL, '{"jenis":"Test 30 Point","poin":-30,"tanggal":"2026-02-02","keterangan":""}', '2026-02-02 11:49:02'),
(47, 'naik_level', 2, 'bk', 16, NULL, 'Surat Orang Tua Terkirim', '{"from":0,"to":1,"id_surat":16,"reset_at":"2026-02-02 03:50:34"}', '2026-02-02 11:50:34'),
(48, 'revert_level', 2, 'bk', 16, NULL, 'Pembatalan Surat Orang Tua (Status: Batal Terkirim)', '{"from":1,"to":0,"id_surat":16,"restored_reset_at":null}', '2026-02-02 11:50:55'),
(49, 'naik_level', 2, 'bk', 16, NULL, 'Surat Orang Tua Terkirim', '{"from":0,"to":1,"id_surat":16,"reset_at":"2026-02-03 09:04:45"}', '2026-02-03 09:04:45'),
(50, 'revert_level', 2, 'bk', 16, NULL, 'Pembatalan Surat Orang Tua (Status: Belum Terkirim)', '{"from":1,"to":0,"id_surat":16,"restored_reset_at":"2026-02-02 03:50:34"}', '2026-02-03 09:04:59'),
(51, 'naik_level', 2, 'bk', 16, NULL, 'Surat Orang Tua Terkirim', '{"from":0,"to":1,"id_surat":16,"reset_at":"2026-02-03 09:05:15"}', '2026-02-03 09:05:15'),
(52, 'tambah_pelanggaran', 2, 'bk', 16, 36, NULL, '{"jenis":"Test 30 Point","poin":-30,"tanggal":"2026-02-03","keterangan":""}', '2026-02-03 09:05:41'),
(53, 'naik_level', 2, 'bk', 16, NULL, 'Surat Orang Tua Terkirim', '{"from":1,"to":2,"id_surat":17,"reset_at":"2026-02-03 10:22:26"}', '2026-02-03 10:22:26'),
(54, 'tambah_pelanggaran', 2, 'bk', 18, 37, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-02-04","keterangan":""}', '2026-02-04 11:45:58'),
(55, 'naik_level', 2, 'bk', 18, NULL, 'Upload bukti surat terverifikasi', '{"from":0,"to":1,"tahap":"hijau-kuning","need":["perjanjian_siswa"]}', '2026-02-04 11:57:20'),
(56, 'tambah_pelanggaran', 2, 'bk', 19, 38, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-02-09","keterangan":""}', '2026-02-09 11:09:12'),
(57, 'naik_level', 2, 'bk', 19, NULL, 'Surat Orang Tua Dibuat (Status: Terkirim)', '{"from":0,"to":1,"reset_at":"2026-02-09 11:09:49"}', '2026-02-09 11:09:49'),
(58, 'tambah_pelanggaran', 2, 'bk', 19, 39, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-02-09","keterangan":""}', '2026-02-09 11:33:37'),
(59, 'naik_level', 2, 'bk', 19, NULL, 'Surat Orang Tua Dibuat (Status: Terkirim)', '{"from":1,"to":2,"reset_at":"2026-02-09 11:34:57"}', '2026-02-09 11:34:57'),
(60, 'tambah_pelanggaran', 1, 'admin', 20, 40, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-02-10","keterangan":"test"}', '2026-02-10 09:24:44'),
(61, 'hapus_pelanggaran', 1, 'admin', 20, 40, 'test', '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-02-10","keterangan":"test","created_by_user_id":1,"siswa_nis":"5002","siswa_nama":"Rey"}', '2026-02-10 09:25:04'),
(62, 'tambah_pelanggaran', 2, 'bk', 20, 41, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-02-10","keterangan":""}', '2026-02-10 10:48:38'),
(63, 'naik_level', 2, 'bk', 20, NULL, 'Surat Orang Tua Dibuat (Status: Terkirim)', '{"from":0,"to":1,"reset_at":"2026-02-10 10:51:01"}', '2026-02-10 10:51:01'),
(64, 'tambah_pelanggaran', 2, 'bk', 19, 42, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-02-10","keterangan":""}', '2026-02-10 10:55:06'),
(65, 'tambah_pelanggaran', 2, 'bk', 18, 43, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-02-10","keterangan":""}', '2026-02-10 11:09:57'),
(66, 'verifikasi_do', 2, 'bk', 19, NULL, 'Surat DO diverifikasi. Siswa resmi berstatus DO dan akun dinonaktifkan.', '{"from":2,"to":3,"tahap":"merah-akhir","nis":"5001"}', '2026-02-10 11:17:45'),
(67, 'naik_level', 2, 'bk', 18, NULL, 'Otomatis naik level — semua surat tahap kuning-merah sudah terbit.', '{"from":1,"to":2,"tahap":"kuning-merah"}', '2026-02-10 11:18:54'),
(68, 'tambah_pelanggaran', 2, 'bk', 18, 44, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-02-10","keterangan":""}', '2026-02-10 11:21:10'),
(69, 'verifikasi_do', 2, 'bk', 18, NULL, 'Surat DO diverifikasi. Siswa resmi berstatus DO dan akun dinonaktifkan.', '{"from":2,"to":3,"tahap":"merah-akhir","nis":"5000"}', '2026-02-10 11:21:32'),
(70, 'tambah_pelanggaran', 2, 'bk', 20, 45, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-02-10","keterangan":""}', '2026-02-10 11:21:47'),
(71, 'naik_level', 2, 'bk', 20, NULL, 'Surat Orang Tua Terkirim', '{"from":1,"to":2,"id_surat":22,"reset_at":"2026-02-10 11:27:26"}', '2026-02-10 11:27:26'),
(72, 'tambah_pelanggaran', 2, 'bk', 20, 46, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-02-10","keterangan":""}', '2026-02-10 11:27:39'),
(73, 'verifikasi_do', 2, 'bk', 20, NULL, 'Surat DO diverifikasi. Siswa resmi berstatus DO dan akun dinonaktifkan.', '{"from":2,"to":3,"tahap":"merah-akhir","nis":"5002"}', '2026-02-10 11:28:45'),
(74, 'tambah_pelanggaran', 2, 'bk', 21, 47, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-02-10","keterangan":""}', '2026-02-10 11:38:23'),
(75, 'naik_level', 2, 'bk', 21, NULL, 'Otomatis naik level — semua surat tahap hijau-kuning sudah terbit.', '{"from":0,"to":1,"tahap":"hijau-kuning"}', '2026-02-10 11:42:25'),
(76, 'tambah_pelanggaran', 2, 'bk', 21, 48, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-02-10","keterangan":""}', '2026-02-10 11:42:42'),
(77, 'naik_level', 2, 'bk', 21, NULL, 'Surat Orang Tua Terkirim', '{"from":1,"to":2,"id_surat":23,"reset_at":"2026-02-10 11:43:31"}', '2026-02-10 11:43:31'),
(78, 'tambah_pelanggaran', 2, 'bk', 21, 49, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-02-10","keterangan":""}', '2026-02-10 11:43:43'),
(79, 'verifikasi_do', 2, 'bk', 21, NULL, 'Surat DO diverifikasi. Siswa resmi berstatus DO dan akun dinonaktifkan.', '{"from":2,"to":3,"tahap":"merah-akhir","nis":"5000"}', '2026-02-10 11:44:03'),
(80, 'tambah_pelanggaran', 2, 'bk', 22, 50, NULL, '{"jenis":"Merokok","poin":15,"tanggal":"2026-02-10","keterangan":""}', '2026-02-10 23:08:46'),
(81, 'tambah_pelanggaran', 2, 'bk', 22, 51, NULL, '{"jenis":"Merokok","poin":15,"tanggal":"2026-02-10","keterangan":""}', '2026-02-10 23:08:48'),
(82, 'naik_level', 2, 'bk', 22, NULL, 'Otomatis naik level — semua surat tahap hijau-kuning sudah terbit.', '{"from":0,"to":1,"tahap":"hijau-kuning"}', '2026-02-10 23:12:05'),
(83, 'tambah_pelanggaran', 2, 'bk', 22, 52, NULL, '{"jenis":"Merokok","poin":15,"tanggal":"2026-02-10","keterangan":""}', '2026-02-10 23:13:02'),
(84, 'tambah_pelanggaran', 2, 'bk', 22, 53, NULL, '{"jenis":"Merokok","poin":15,"tanggal":"2026-02-10","keterangan":""}', '2026-02-10 23:15:04'),
(85, 'naik_level', 2, 'bk', 22, NULL, 'Surat Orang Tua Terkirim', '{"from":1,"to":2,"id_surat":24,"reset_at":"2026-02-10 23:15:46"}', '2026-02-10 23:15:46'),
(86, 'tambah_pelanggaran', 2, 'bk', 22, 54, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-02-10","keterangan":""}', '2026-02-10 23:15:57'),
(87, 'verifikasi_do', 2, 'bk', 22, NULL, 'Surat DO diverifikasi. Siswa resmi berstatus DO dan akun dinonaktifkan.', '{"from":2,"to":3,"tahap":"merah-akhir","nis":"5001"}', '2026-02-10 23:16:17'),
(88, 'tambah_pelanggaran', 2, 'bk', 23, 55, NULL, '{"jenis":"Merokok","poin":15,"tanggal":"2026-02-11","keterangan":""}', '2026-02-11 11:00:46'),
(89, 'tambah_pelanggaran', 2, 'bk', 24, 56, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-02-11","keterangan":""}', '2026-02-11 11:09:02'),
(90, 'tambah_pelanggaran', 2, 'bk', 24, 57, NULL, '{"jenis":"Terlambat","poin":8,"tanggal":"2026-02-11","keterangan":""}', '2026-02-11 11:10:43'),
(91, 'naik_level', 2, 'bk', 24, NULL, 'Otomatis naik level — semua surat tahap hijau-kuning sudah terbit.', '{"from":0,"to":1,"tahap":"hijau-kuning"}', '2026-02-11 11:12:02'),
(92, 'tambah_pelanggaran', 2, 'bk', 24, 58, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-02-11","keterangan":""}', '2026-02-11 11:37:49'),
(93, 'tambah_pelanggaran', 28, 'guru', 24, 59, NULL, '{"jenis":"Merokok","poin":15,"tanggal":"2026-02-11","keterangan":"ttydktj"}', '2026-02-11 11:41:54'),
(94, 'tambah_pelanggaran', 2, 'bk', 24, 60, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-02-11","keterangan":""}', '2026-02-11 18:36:12'),
(95, 'tambah_pelanggaran', 2, 'bk', 24, 61, NULL, '{"jenis":"Tidak memakai atribut lengkap","poin":5,"tanggal":"2026-02-11","keterangan":""}', '2026-02-11 18:41:29'),
(96, 'tambah_pelanggaran', 1, 'admin', 24, 62, NULL, '{"jenis":"Merokok","poin":15,"tanggal":"2026-03-02","keterangan":""}', '2026-03-02 11:23:02'),
(97, 'pengurangan_poin', 1, 'admin', 24, NULL, NULL, '{"tanggal":"2026-03-02","jumlah_pengurangan":5,"keterangan":"Test","pengurangan_id":2}', '2026-03-02 11:23:45'),
(98, 'auto_reset_level', 2, 'bk', 24, NULL, 'Semua surat rekomendasi level Kuning telah terbit. Poin direset dan naik ke level Merah', '{"from":1,"to":2,"tahap":"kuning-merah","nis":"5002"}', '2026-03-02 17:41:42'),
(99, 'tambah_pelanggaran', 1, 'admin', 25, 63, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-03-04","keterangan":""}', '2026-03-04 11:11:16'),
(100, 'auto_reset_level', 1, 'admin', 25, NULL, 'Semua surat rekomendasi level Hijau telah terbit. Poin direset dan naik ke level Kuning', '{"from":0,"to":1,"tahap":"hijau-kuning","nis":"5003"}', '2026-03-04 11:12:33'),
(101, 'tambah_pelanggaran', 1, 'admin', 25, 64, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-03-04","keterangan":""}', '2026-03-04 11:12:54'),
(102, 'auto_reset_level', 1, 'admin', 25, NULL, 'Semua surat rekomendasi level Kuning telah terbit. Poin direset dan naik ke level Merah', '{"from":1,"to":2,"tahap":"kuning-merah","nis":"5003"}', '2026-03-04 11:13:48'),
(103, 'tambah_pelanggaran', 1, 'admin', 25, 65, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-03-04","keterangan":""}', '2026-03-04 11:14:04'),
(104, 'tambah_pelanggaran', 1, 'admin', 26, 66, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-03-04","keterangan":""}', '2026-03-04 11:17:06'),
(105, 'tambah_pelanggaran', 1, 'admin', 27, 67, NULL, '{"jenis":"Bully","poin":10,"tanggal":"2026-03-04","keterangan":""}', '2026-03-04 11:38:21'),
(106, 'auto_reset_level', 1, 'admin', 25, NULL, 'Surat DO/Pindah diterbitkan. Siswa berstatus DO dan akun dinonaktifkan.', '{"from":2,"to":3,"tahap":"merah-akhir","nis":"5003"}', '2026-03-04 18:32:12'),
(107, 'tambah_pelanggaran', 1, 'admin', 26, 68, NULL, '{"jenis":"Tidak Mengikuti Upacara Bendera","poin":5,"tanggal":"2026-03-04","keterangan":""}', '2026-03-04 18:37:26'),
(108, 'tambah_pelanggaran', 1, 'admin', 26, 69, NULL, '{"jenis":"Bully","poin":10,"tanggal":"2026-03-09","keterangan":""}', '2026-03-09 11:14:52'),
(109, 'tambah_pelanggaran', 1, 'admin', 26, 70, NULL, '{"jenis":"Tidak memakai atribut lengkap","poin":5,"tanggal":"2026-03-09","keterangan":""}', '2026-03-09 11:19:34'),
(110, 'auto_reset_level', 1, 'admin', 26, NULL, 'Semua surat rekomendasi level Hijau telah terbit. Poin direset dan naik ke level Kuning', '{"from":0,"to":1,"tahap":"hijau-kuning","nis":"5004"}', '2026-03-27 07:32:46'),
(111, 'tambah_pelanggaran', 1, 'admin', 28, 71, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-04-03","keterangan":""}', '2026-04-03 19:20:42'),
(112, 'auto_reset_level', 1, 'admin', 28, NULL, 'Semua surat rekomendasi level Hijau telah terbit. Poin direset dan naik ke level Kuning', '{"from":0,"to":1,"tahap":"hijau-kuning","nis":"5005"}', '2026-04-03 19:23:05'),
(113, 'tambah_pelanggaran', 1, 'admin', 28, 72, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-04-03","keterangan":""}', '2026-04-03 19:25:25'),
(114, 'auto_reset_level', 1, 'admin', 28, NULL, 'Semua surat rekomendasi level Kuning telah terbit. Poin direset dan naik ke level Merah', '{"from":1,"to":2,"tahap":"kuning-merah","nis":"5005"}', '2026-04-03 19:33:22'),
(115, 'tambah_pelanggaran', 1, 'admin', 28, 73, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-04-03","keterangan":""}', '2026-04-03 19:33:58'),
(116, 'auto_reset_level', 1, 'admin', 28, NULL, 'Surat DO/Pindah diterbitkan. Siswa berstatus DO dan akun dinonaktifkan.', '{"from":2,"to":3,"tahap":"merah-akhir","nis":"5005"}', '2026-04-03 19:44:15'),
(117, 'tambah_pelanggaran', 1, 'admin', 29, 74, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-04-03","keterangan":""}', '2026-04-03 19:51:03'),
(118, 'auto_reset_level', 1, 'admin', 29, NULL, 'Semua surat rekomendasi level Hijau telah terbit. Poin direset dan naik ke level Kuning', '{"from":0,"to":1,"tahap":"hijau-kuning","nis":"5006"}', '2026-04-03 19:54:26'),
(119, 'tambah_pelanggaran', 1, 'admin', 29, 75, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-04-03","keterangan":""}', '2026-04-03 19:56:27'),
(120, 'auto_reset_level', 1, 'admin', 29, NULL, 'Semua surat rekomendasi level Kuning telah terbit. Poin direset dan naik ke level Merah', '{"from":1,"to":2,"tahap":"kuning-merah","nis":"5006"}', '2026-04-03 19:57:59'),
(121, 'tambah_pelanggaran', 1, 'admin', 29, 76, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-04-03","keterangan":""}', '2026-04-03 19:58:28'),
(122, 'auto_reset_level', 1, 'admin', 29, NULL, 'Surat DO/Pindah diterbitkan. Siswa berstatus DO dan akun dinonaktifkan.', '{"from":2,"to":3,"tahap":"merah-akhir","nis":"5006"}', '2026-04-03 20:27:24'),
(123, 'tambah_pelanggaran', 1, 'admin', 30, 77, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-04-03","keterangan":""}', '2026-04-03 20:28:20'),
(124, 'auto_reset_level', 1, 'admin', 30, NULL, 'Semua surat rekomendasi level Hijau telah terbit. Poin direset dan naik ke level Kuning', '{"from":0,"to":1,"tahap":"hijau-kuning","nis":"5007"}', '2026-04-03 20:28:44'),
(125, 'tambah_pelanggaran', 1, 'admin', 30, 78, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-04-03","keterangan":""}', '2026-04-03 20:29:03'),
(126, 'auto_reset_level', 1, 'admin', 30, NULL, 'Semua surat rekomendasi level Kuning telah terbit. Poin direset dan naik ke level Merah', '{"from":1,"to":2,"tahap":"kuning-merah","nis":"5007"}', '2026-04-03 20:29:17'),
(127, 'tambah_pelanggaran', 1, 'admin', 30, 79, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-04-03","keterangan":""}', '2026-04-03 20:29:32'),
(128, 'auto_reset_level', 1, 'admin', 30, NULL, 'Surat DO/Pindah diterbitkan. Siswa berstatus DO dan akun dinonaktifkan.', '{"from":2,"to":3,"tahap":"merah-akhir","nis":"5007"}', '2026-04-03 20:29:58'),
(129, 'tambah_pelanggaran', 1, 'admin', 31, 80, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-04-04","keterangan":""}', '2026-04-04 22:22:02'),
(130, 'auto_reset_level', 2, 'bk', 31, NULL, 'Semua surat rekomendasi level Hijau telah terbit. Poin direset dan naik ke level Kuning', '{"from":0,"to":1,"tahap":"hijau-kuning","nis":"5008"}', '2026-04-04 23:02:10'),
(131, 'tambah_pelanggaran', 2, 'bk', 31, 81, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-04-04","keterangan":""}', '2026-04-04 23:05:06'),
(132, 'auto_reset_level', 2, 'bk', 31, NULL, 'Semua surat rekomendasi level Kuning telah terbit. Poin direset dan naik ke level Merah', '{"from":1,"to":2,"tahap":"kuning-merah","nis":"5008"}', '2026-04-04 23:18:36'),
(133, 'tambah_pelanggaran', 1, 'admin', 32, 82, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-04-04","keterangan":""}', '2026-04-05 07:30:55'),
(134, 'auto_reset_level', 1, 'admin', 32, NULL, 'Semua surat rekomendasi level Hijau telah terbit. Poin direset dan naik ke level Kuning', '{"from":0,"to":1,"tahap":"hijau-kuning","nis":"5009"}', '2026-04-05 07:31:20'),
(135, 'tambah_pelanggaran', 1, 'admin', 32, 83, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-04-05","keterangan":""}', '2026-04-05 08:18:14'),
(136, 'auto_reset_level', 1, 'admin', 32, NULL, 'Semua surat rekomendasi level Kuning telah terbit. Poin direset dan naik ke level Merah', '{"from":1,"to":2,"tahap":"kuning-merah","nis":"5009"}', '2026-04-05 08:29:07'),
(137, 'tambah_pelanggaran', 1, 'admin', 32, 84, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-04-05","keterangan":""}', '2026-04-05 08:31:37'),
(138, 'auto_reset_level', 1, 'admin', 32, NULL, 'Surat DO/Pindah diterbitkan. Siswa berstatus DO dan akun dinonaktifkan.', '{"from":2,"to":3,"tahap":"merah-akhir","nis":"5009"}', '2026-04-05 08:32:35'),
(139, 'tambah_pelanggaran', 1, 'admin', 33, 85, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-04-05","keterangan":""}', '2026-04-05 08:46:16'),
(140, 'auto_reset_level', 1, 'admin', 33, NULL, 'Semua surat rekomendasi level Hijau telah terbit. Poin direset dan naik ke level Kuning', '{"from":0,"to":1,"tahap":"hijau-kuning","nis":"5010"}', '2026-04-05 08:46:47'),
(141, 'tambah_pelanggaran', 1, 'admin', 33, 86, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-04-05","keterangan":""}', '2026-04-05 10:51:00'),
(142, 'auto_reset_level', 1, 'admin', 33, NULL, 'Semua surat rekomendasi level Kuning telah terbit. Poin direset dan naik ke level Merah', '{"from":1,"to":2,"tahap":"kuning-merah","nis":"5010"}', '2026-04-05 10:51:30'),
(143, 'tambah_pelanggaran', 1, 'admin', 33, 87, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-04-05","keterangan":""}', '2026-04-05 10:52:14'),
(144, 'auto_reset_level', 1, 'admin', 33, NULL, 'Surat DO/Pindah diterbitkan. Siswa berstatus DO dan akun dinonaktifkan.', '{"from":2,"to":3,"tahap":"merah-akhir","nis":"5010"}', '2026-04-05 10:52:44'),
(145, 'tambah_pelanggaran', 1, 'admin', 34, 88, NULL, '{"jenis":"Test 30 Point","poin":30,"tanggal":"2026-04-05","keterangan":""}', '2026-04-05 20:06:46'),
(146, 'auto_reset_level', 1, 'admin', 34, NULL, 'Semua surat rekomendasi level Hijau telah terbit. Poin direset dan naik ke level Kuning', '{"from":0,"to":1,"tahap":"hijau-kuning","nis":"5011"}', '2026-04-05 23:21:30'),
(147, 'tambah_pelanggaran', 1, 'admin', 34, 89, NULL, '{"jenis":"Terlambat","poin":8,"tanggal":"2026-04-05","keterangan":""}', '2026-04-05 23:49:16');

-- ── Tabel: `pejabat_sekolah` ────────────────────────────────────
DROP TABLE IF EXISTS `pejabat_sekolah`;
CREATE TABLE `pejabat_sekolah` (
  `id` int NOT NULL AUTO_INCREMENT,
  `jabatan` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_pejabat` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nip` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pejabat_sekolah` (`id`, `jabatan`, `nama_pejabat`, `nip`) VALUES
(3, 'Kepala Sekolah', '-', '-'),
(6, 'Kepala Sekolah', '-', '-'),
(9, 'Kepala Sekolah', '-', '-'),
(12, 'Kepala Sekolah', '-', '-'),
(15, 'Kepala Sekolah', '-', '-'),
(18, 'Kepala Sekolah', '-', '-'),
(21, 'Kepala Sekolah', '-', '-'),
(24, 'Kepala Sekolah', '-', '-'),
(27, 'Guru BK', 'Ni Putu Chintya Pradnya Suari, S.Pd', '-'),
(28, 'Wakasek Kesiswaan', 'Bagus Putu Eka Wijaya, S.Kom', '-');

-- ── Tabel: `pelanggaran` ────────────────────────────────────
DROP TABLE IF EXISTS `pelanggaran`;
CREATE TABLE `pelanggaran` (
  `id_pelanggaran` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `id_jenis` int NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `created_by_user_id` int DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by_user_id` int DEFAULT NULL,
  `delete_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pelanggaran`),
  KEY `fk_pelanggaran_siswa` (`id_siswa`),
  KEY `fk_pelanggaran_jenis` (`id_jenis`),
  CONSTRAINT `fk_pelanggaran_jenis` FOREIGN KEY (`id_jenis`) REFERENCES `jenis_pelanggaran` (`id_jenis`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pelanggaran_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pelanggaran` (`id_pelanggaran`, `id_siswa`, `id_jenis`, `tanggal`, `keterangan`, `created_by_user_id`, `deleted_at`, `deleted_by_user_id`, `delete_reason`, `created_at`) VALUES
(50, 22, 3, '2026-02-10', NULL, 2, NULL, NULL, NULL, '2026-02-10 23:08:46'),
(51, 22, 3, '2026-02-10', NULL, 2, NULL, NULL, NULL, '2026-02-10 23:08:48'),
(52, 22, 3, '2026-02-10', NULL, 2, NULL, NULL, NULL, '2026-02-10 23:13:02'),
(53, 22, 3, '2026-02-10', NULL, 2, NULL, NULL, NULL, '2026-02-10 23:15:04'),
(54, 22, 4, '2026-02-10', NULL, 2, NULL, NULL, NULL, '2026-02-10 23:15:57'),
(56, 24, 4, '2026-02-11', NULL, 2, NULL, NULL, NULL, '2026-02-11 11:09:02'),
(57, 24, 1, '2026-02-11', NULL, 2, NULL, NULL, NULL, '2026-02-11 11:10:43'),
(58, 24, 4, '2026-02-11', NULL, 2, NULL, NULL, NULL, '2026-02-11 11:37:49'),
(59, 24, 3, '2026-02-11', 'ttydktj', 28, NULL, NULL, NULL, '2026-02-11 11:41:54'),
(60, 24, 4, '2026-02-11', NULL, 2, NULL, NULL, NULL, '2026-02-11 18:36:12'),
(61, 24, 2, '2026-02-11', NULL, 2, NULL, NULL, NULL, '2026-02-11 18:41:29'),
(62, 24, 3, '2026-03-02', NULL, 1, NULL, NULL, NULL, '2026-03-02 11:23:02'),
(63, 25, 4, '2026-03-04', NULL, 1, NULL, NULL, NULL, '2026-03-04 11:11:16'),
(64, 25, 4, '2026-03-04', NULL, 1, NULL, NULL, NULL, '2026-03-04 11:12:54'),
(65, 25, 4, '2026-03-04', NULL, 1, NULL, NULL, NULL, '2026-03-04 11:14:04'),
(66, 26, 4, '2026-03-04', NULL, 1, NULL, NULL, NULL, '2026-03-04 11:17:06'),
(68, 26, 8, '2026-03-04', NULL, 1, NULL, NULL, NULL, '2026-03-04 18:37:26'),
(69, 26, 9, '2026-03-09', NULL, 1, NULL, NULL, NULL, '2026-03-09 11:14:52'),
(70, 26, 2, '2026-03-09', NULL, 1, NULL, NULL, NULL, '2026-03-09 11:19:34'),
(74, 29, 4, '2026-04-03', NULL, 1, NULL, NULL, NULL, '2026-04-03 19:51:03'),
(75, 29, 4, '2026-04-03', NULL, 1, NULL, NULL, NULL, '2026-04-03 19:56:27'),
(76, 29, 4, '2026-04-03', NULL, 1, NULL, NULL, NULL, '2026-04-03 19:58:28'),
(77, 30, 4, '2026-04-03', NULL, 1, NULL, NULL, NULL, '2026-04-03 20:28:20'),
(78, 30, 4, '2026-04-03', NULL, 1, NULL, NULL, NULL, '2026-04-03 20:29:03'),
(79, 30, 4, '2026-04-03', NULL, 1, NULL, NULL, NULL, '2026-04-03 20:29:32'),
(80, 31, 4, '2026-04-04', NULL, 1, NULL, NULL, NULL, '2026-04-04 22:22:02'),
(81, 31, 4, '2026-04-04', NULL, 2, NULL, NULL, NULL, '2026-04-04 23:05:06'),
(82, 32, 4, '2026-04-04', NULL, 1, NULL, NULL, NULL, '2026-04-05 07:30:55'),
(83, 32, 4, '2026-04-05', NULL, 1, NULL, NULL, NULL, '2026-04-05 08:18:14'),
(84, 32, 4, '2026-04-05', NULL, 1, NULL, NULL, NULL, '2026-04-05 08:31:37'),
(85, 33, 4, '2026-04-05', NULL, 1, NULL, NULL, NULL, '2026-04-05 08:46:16'),
(86, 33, 4, '2026-04-05', NULL, 1, NULL, NULL, NULL, '2026-04-05 10:51:00'),
(87, 33, 4, '2026-04-05', NULL, 1, NULL, NULL, NULL, '2026-04-05 10:52:14'),
(88, 34, 4, '2026-04-05', NULL, 1, NULL, NULL, NULL, '2026-04-05 20:06:46'),
(89, 34, 1, '2026-04-05', NULL, 1, NULL, NULL, NULL, '2026-04-05 23:49:16');

-- ── Tabel: `siswa` ────────────────────────────────────
DROP TABLE IF EXISTS `siswa`;
CREATE TABLE `siswa` (
  `id_siswa` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nis` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kelas` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `nama_orang_tua` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kontak_orang_tua` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pekerjaan_orang_tua` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wali_guru_id` int DEFAULT NULL,
  `program_keahlian` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `progress_level` tinyint NOT NULL DEFAULT '0',
  `poin_reset_at` datetime DEFAULT NULL,
  `last_stage_at` datetime DEFAULT NULL,
  `id_kelas` int DEFAULT NULL,
  PRIMARY KEY (`id_siswa`),
  UNIQUE KEY `nis` (`nis`),
  KEY `id_kelas` (`id_kelas`),
  CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`id_kelas`) REFERENCES `kelass` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `siswa` (`id_siswa`, `nama`, `nis`, `kelas`, `alamat`, `nama_orang_tua`, `kontak_orang_tua`, `pekerjaan_orang_tua`, `wali_guru_id`, `program_keahlian`, `progress_level`, `poin_reset_at`, `last_stage_at`, `id_kelas`) VALUES
(22, 'W', 5001, 'XI-A', 'a', 'a', 1, NULL, 28, NULL, 3, '2026-02-10 23:16:17', '2026-02-10 23:16:17', NULL),
(24, 'Z', 5002, 'XI-A', 'a', 'a', 1, NULL, 28, NULL, 2, '2026-03-02 17:41:42', '2026-03-02 17:41:42', NULL),
(25, 'John', 5003, 'X-RPL-1', 'Jl.', 'Doe', '081', NULL, NULL, NULL, 3, '2026-03-04 18:32:12', '2026-03-04 18:32:12', NULL),
(26, 'Doe', 5004, 'X-RPL-1', 'Hl', 'John', '0812', NULL, NULL, NULL, 1, '2026-03-27 07:32:46', '2026-03-27 07:32:46', NULL),
(29, 'abc', 5006, 'XI-DKV-1', NULL, 'a', 1, 'W', NULL, NULL, 3, '2026-04-03 20:27:24', '2026-04-03 20:27:24', NULL),
(30, 'JJ', 5007, 'XI-DKV-1', NULL, 'j', 1, 1, NULL, NULL, 3, '2026-04-03 20:29:58', '2026-04-03 20:29:58', NULL),
(31, 'QWE', 5008, 'X-RPL-1', NULL, 'a', 1, 'a', NULL, NULL, 2, '2026-04-04 23:18:36', '2026-04-04 23:18:36', NULL),
(32, 'WSX', 5009, 'X-RPL-2', NULL, 'A', 1, 'A', NULL, NULL, 3, '2026-04-05 08:32:35', '2026-04-05 08:32:35', NULL),
(33, 'KK', 5010, 'X-RPL-2', NULL, 'a', 1, 'a', NULL, NULL, 3, '2026-04-05 10:52:44', '2026-04-05 10:52:44', NULL),
(34, 'Seth', 5011, 'X-RPL-1', NULL, 'a', 1, 'a', NULL, NULL, 1, '2026-04-05 23:21:30', '2026-04-05 23:21:30', NULL);

-- ── Tabel: `surat_counter` ────────────────────────────────────
DROP TABLE IF EXISTS `surat_counter`;
CREATE TABLE `surat_counter` (
  `id` int NOT NULL AUTO_INCREMENT,
  `jenis` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tahun` int NOT NULL,
  `last_number` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_jenis_tahun` (`jenis`,`tahun`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `surat_counter` (`id`, `jenis`, `tahun`, `last_number`) VALUES
(1, 'pemanggilan_ortu', 2026, 31),
(19, 'perjanjian_siswa', 2026, 13);

-- ── Tabel: `surat_do` ────────────────────────────────────
DROP TABLE IF EXISTS `surat_do`;
CREATE TABLE `surat_do` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `tanggal_surat` date DEFAULT NULL,
  `nama_pengaju` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat_pengaju` text COLLATE utf8mb4_unicode_ci,
  `telp_pengaju` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alasan` text COLLATE utf8mb4_unicode_ci,
  `created_by_user_id` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sdo_siswa` (`id_siswa`),
  KEY `idx_sdo_tgl` (`tanggal_surat`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `surat_do` (`id`, `id_siswa`, `tanggal_surat`, `nama_pengaju`, `alamat_pengaju`, `telp_pengaju`, `alasan`, `created_by_user_id`, `created_at`) VALUES
(1, 15, '2026-02-04', NULL, NULL, NULL, 'Test', 2, '2026-02-04 11:25:30'),
(2, 19, '2026-02-10', NULL, NULL, NULL, 'DO', 2, '2026-02-10 10:55:36'),
(3, 19, '2026-02-10', NULL, NULL, NULL, 'DO', 2, '2026-02-10 11:10:45'),
(4, 18, '2026-02-10', NULL, NULL, NULL, 'DO', 2, '2026-02-10 11:21:26'),
(5, 20, '2026-02-10', NULL, NULL, NULL, 'DO', 2, '2026-02-10 11:28:24'),
(6, 21, '2026-02-10', NULL, NULL, NULL, 'DO', 2, '2026-02-10 11:43:54'),
(7, 22, '2026-02-10', NULL, NULL, NULL, 'DO', 2, '2026-02-10 23:16:09'),
(8, 25, '2026-03-04', NULL, NULL, NULL, 'Siswa telah mengumpulkan 30+ poin pelanggaran', 1, '2026-03-04 11:19:42'),
(9, 28, '2026-04-03', NULL, NULL, NULL, 'Siswa telah mengumpulkan 30+ poin pelanggaran', 1, '2026-04-03 19:36:11'),
(10, 29, '2026-04-03', NULL, NULL, NULL, 'Siswa telah melewati batas toleransi yang ditentukan dan tetap melakukan tindakan yang melanggar peraturan sekolah secara berulang kali.', 1, '2026-04-03 20:27:07'),
(11, 30, '2026-04-03', NULL, NULL, NULL, 'Siswa telah melewati batas toleransi yang ditentukan dan tetap melakukan tindakan yang melanggar peraturan sekolah secara berulang kali.', 1, '2026-04-03 20:29:52'),
(12, 32, '2026-04-05', NULL, NULL, NULL, 'Siswa telah melewati batas toleransi yang ditentukan.', 1, '2026-04-05 08:32:03'),
(13, 33, '2026-04-05', NULL, NULL, NULL, 'Siswa telah melewati batas toleransi yang ditentukan.', 1, '2026-04-05 10:52:24');

-- ── Tabel: `surat_orang_tua` ────────────────────────────────────
DROP TABLE IF EXISTS `surat_orang_tua`;
CREATE TABLE `surat_orang_tua` (
  `id_surat_orangtua` int NOT NULL AUTO_INCREMENT,
  `id_pelanggaran` int NOT NULL,
  `tanggal_cetak` date NOT NULL,
  `status_kirim` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_surat_orangtua`),
  KEY `fk_surat_orangtua_pelanggaran` (`id_pelanggaran`),
  CONSTRAINT `fk_surat_orangtua_pelanggaran` FOREIGN KEY (`id_pelanggaran`) REFERENCES `pelanggaran` (`id_pelanggaran`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `surat_orang_tua` (`id_surat_orangtua`, `id_pelanggaran`, `tanggal_cetak`, `status_kirim`, `created_at`) VALUES
(24, 53, '2026-02-10', 'Terkirim', '2026-02-10 23:15:41');

-- ── Tabel: `surat_pemanggilan_ortu` ────────────────────────────────────
DROP TABLE IF EXISTS `surat_pemanggilan_ortu`;
CREATE TABLE `surat_pemanggilan_ortu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `nomor` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_surat` date NOT NULL,
  `hari_tanggal` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pukul` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tempat` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keperluan` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by_user_id` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_spo_siswa` (`id_siswa`),
  KEY `idx_spo_tgl` (`tanggal_surat`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `surat_pemanggilan_ortu` (`id`, `id_siswa`, `nomor`, `tanggal_surat`, `hari_tanggal`, `pukul`, `tempat`, `keperluan`, `created_by_user_id`, `created_at`) VALUES
(1, 18, '001/SPO/2026', '2026-02-10', NULL, NULL, NULL, NULL, 2, '2026-02-10 11:18:11'),
(2, 20, '001/SPO/2026', '2026-02-10', NULL, NULL, NULL, NULL, 2, '2026-02-10 11:26:43'),
(3, 21, '002/SPO/2026', '2026-02-10', NULL, NULL, NULL, NULL, 2, '2026-02-10 11:42:23'),
(4, 21, '003/SPO/2026', '2026-02-10', NULL, NULL, NULL, NULL, 2, '2026-02-10 11:43:08'),
(5, 22, '004/SPO/2026', '2026-02-10', NULL, NULL, NULL, NULL, 2, '2026-02-10 23:11:59'),
(6, 22, '005/SPO/2026', '2026-02-10', NULL, NULL, NULL, NULL, 2, '2026-02-10 23:15:25'),
(7, 24, '006/SPO/2026', '2026-02-11', NULL, NULL, NULL, NULL, 2, '2026-02-11 11:11:34'),
(8, 24, '007/SPO/2026', '2026-02-11', NULL, NULL, NULL, NULL, 2, '2026-02-11 11:38:27'),
(9, 24, '008/SPO/2026', '2026-02-11', 'Senin, 12 February 2025', NULL, NULL, NULL, 2, '2026-02-11 11:39:03'),
(10, 24, '009/SPO/2026', '2026-03-02', 'Senin, 2 Maret 2026', 10.00, 'Ruang BK', 'Pembahasan pelanggaran siswa', 2, '2026-03-02 17:27:16'),
(11, 25, '010/SPO/2026', '2026-03-04', 'Rabu, 4 Maret 2026', 10.00, 'Ruang BK', 'Pembahasan pelanggaran siswa', 1, '2026-03-04 11:12:33'),
(12, 25, '011/SPO/2026', '2026-03-04', 'Rabu, 4 Maret 2026', 10.00, 'Ruang BK', 'Pembahasan pelanggaran siswa', 1, '2026-03-04 11:13:46'),
(13, 25, '012/SPO/2026', '2026-03-04', 'Rabu, 4 Maret 2026', 10.00, 'Ruang BK', 'Pembahasan pelanggaran siswa', 1, '2026-03-04 18:32:04'),
(14, 26, '013/SPO/2026', '2026-03-09', 'Senin, 9 Maret 2026', 10.00, 'Ruang BK', 'Pembahasan pelanggaran siswa', 1, '2026-03-09 11:55:56'),
(15, 28, '014/SPO/2026', '2026-04-03', 'Jumat, 3 April 2026', 10.00, 'Ruang BK', 'Pembahasan pelanggaran siswa', 1, '2026-04-03 19:22:04'),
(16, 28, '015/SPO/2026', '2026-04-03', 'Jumat, 3 April 2026', 10.00, 'Ruang BK', 'Pembahasan pelanggaran siswa', 1, '2026-04-03 19:33:08'),
(17, 28, '016/SPO/2026', '2026-04-03', 'Jumat, 3 April 2026', 10.00, 'Ruang BK', 'Pembahasan pelanggaran siswa', 1, '2026-04-03 19:36:02'),
(18, 29, '017/SPO/2026', '2026-04-03', NULL, NULL, NULL, NULL, 1, '2026-04-03 19:51:15'),
(19, 29, '018/SPO/2026', '2026-04-03', NULL, NULL, NULL, NULL, 1, '2026-04-03 19:57:27'),
(20, 29, '019/SPO/2026', '2026-04-03', NULL, NULL, NULL, NULL, 1, '2026-04-03 20:26:53'),
(21, 30, '020/SPO/2026', '2026-04-03', NULL, NULL, NULL, NULL, 1, '2026-04-03 20:28:28'),
(22, 30, '021/SPO/2026', '2026-04-03', NULL, NULL, NULL, NULL, 1, '2026-04-03 20:29:11'),
(23, 30, '022/SPO/2026', '2026-04-03', NULL, NULL, NULL, NULL, 1, '2026-04-03 20:29:46'),
(24, 31, '023/SPO/2026', '2026-04-04', NULL, NULL, NULL, NULL, 2, '2026-04-04 22:23:05'),
(25, 31, '024/SPO/2026', '2026-04-04', NULL, NULL, NULL, NULL, 2, '2026-04-04 23:17:22'),
(26, 32, '025/SPO/2026', '2026-04-04', NULL, NULL, NULL, NULL, 1, '2026-04-05 07:31:01'),
(27, 32, '026/SPO/2026', '2026-04-05', NULL, NULL, NULL, NULL, 1, '2026-04-05 08:18:22'),
(28, 32, '027/SPO/2026', '2026-04-05', NULL, NULL, NULL, NULL, 1, '2026-04-05 08:31:43'),
(29, 33, '028/SPO/2026', '2026-04-05', NULL, NULL, NULL, NULL, 1, '2026-04-05 08:46:21'),
(30, 33, '029/SPO/2026', '2026-04-05', NULL, NULL, NULL, NULL, 1, '2026-04-05 10:51:04'),
(31, 33, '030/SPO/2026', '2026-04-05', NULL, NULL, NULL, NULL, 1, '2026-04-05 10:52:18'),
(32, 34, '031/SPO/2026', '2026-04-05', NULL, NULL, NULL, NULL, 1, '2026-04-05 20:30:57');

-- ── Tabel: `surat_pengurangan_poin` ────────────────────────────────────
DROP TABLE IF EXISTS `surat_pengurangan_poin`;
CREATE TABLE `surat_pengurangan_poin` (
  `id_pengurangan` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `tanggal` date NOT NULL,
  `jumlah_pengurangan` int NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_pengurangan`),
  KEY `fk_pengurangan_siswa` (`id_siswa`),
  CONSTRAINT `fk_pengurangan_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `surat_pengurangan_poin` (`id_pengurangan`, `id_siswa`, `tanggal`, `jumlah_pengurangan`, `keterangan`) VALUES
(2, 24, '2026-03-02', 5, 'Test');

-- ── Tabel: `surat_perjanjian` ────────────────────────────────────
DROP TABLE IF EXISTS `surat_perjanjian`;
CREATE TABLE `surat_perjanjian` (
  `id_perjanjian` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `tanggal_perjanjian` date NOT NULL,
  `isi_perjanjian` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `nomor` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `ortu_nama` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ortu_pekerjaan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ortu_alamat` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ortu_hp` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_perjanjian`),
  KEY `fk_perjanjian_siswa` (`id_siswa`),
  CONSTRAINT `fk_perjanjian_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `surat_perjanjian` (`id_perjanjian`, `id_siswa`, `tanggal_perjanjian`, `isi_perjanjian`, `nomor`, `created_at`, `ortu_nama`, `ortu_pekerjaan`, `ortu_alamat`, `ortu_hp`) VALUES
(31, 22, '2026-02-10', 'Janji', NULL, '2026-02-10 23:10:18', NULL, NULL, NULL, NULL),
(32, 22, '2026-02-10', 'Janji', NULL, '2026-02-10 23:15:17', NULL, NULL, NULL, NULL),
(33, 22, '2026-02-10', 'Surat perjanjian tahap 2: siswa berjanji memperbaiki perilaku dan tidak mengulangi pelanggaran.', NULL, '2026-02-10 23:15:46', NULL, NULL, NULL, NULL),
(34, 24, '2026-02-11', 'Janji', NULL, '2026-02-11 11:09:24', NULL, NULL, NULL, NULL),
(35, 24, '2026-02-11', 'Janji', NULL, '2026-02-11 11:38:13', NULL, NULL, NULL, NULL),
(36, 24, '2026-03-02', 'Test', NULL, '2026-03-02 11:39:33', NULL, NULL, NULL, NULL),
(37, 24, '2026-03-02', 'Janji', NULL, '2026-03-02 11:47:04', NULL, NULL, NULL, NULL),
(38, 24, '2026-03-02', 'Siswa berjanji untuk memperbaiki perilaku dan tidak mengulangi pelanggaran.', NULL, '2026-03-02 17:18:44', NULL, NULL, NULL, NULL),
(39, 24, '2026-03-02', 'Orang tua siswa berkomitmen untuk mengawasi dan membimbing anak dalam perbaikan perilaku.', NULL, '2026-03-02 17:37:06', NULL, NULL, NULL, NULL),
(40, 24, '2026-03-02', 'Orang tua siswa berkomitmen untuk mengawasi dan membimbing anak dalam perbaikan perilaku.', NULL, '2026-03-02 17:37:36', NULL, NULL, NULL, NULL),
(41, 24, '2026-03-02', 'Orang tua siswa berkomitmen untuk mengawasi dan membimbing anak dalam perbaikan perilaku.', NULL, '2026-03-02 17:38:34', NULL, NULL, NULL, NULL),
(42, 24, '2026-03-02', 'Orang tua siswa berkomitmen untuk mengawasi dan membimbing anak dalam perbaikan perilaku.', NULL, '2026-03-02 17:41:42', NULL, NULL, NULL, NULL),
(43, 25, '2026-03-04', 'Siswa berjanji untuk memperbaiki perilaku dan tidak mengulangi pelanggaran.', NULL, '2026-03-04 11:11:53', NULL, NULL, NULL, NULL),
(44, 25, '2026-03-04', 'Siswa berjanji untuk memperbaiki perilaku dan tidak mengulangi pelanggaran.', NULL, '2026-03-04 11:13:43', NULL, NULL, NULL, NULL),
(45, 25, '2026-03-04', 'Orang tua siswa berkomitmen untuk mengawasi dan membimbing anak dalam perbaikan perilaku.', NULL, '2026-03-04 11:13:48', NULL, NULL, NULL, NULL),
(46, 26, '2026-03-26', 'Siswa berjanji untuk memperbaiki perilaku dan tidak mengulangi pelanggaran.', NULL, '2026-03-27 07:32:46', NULL, NULL, NULL, NULL),
(49, 29, '2026-04-03', 'Siswa berjanji untuk memperbaiki perilaku dan tidak mengulangi pelanggaran.', 'SP-2026-2', '2026-04-03 19:54:26', NULL, NULL, NULL, NULL),
(50, 29, '2026-04-03', 'Siswa berjanji untuk memperbaiki perilaku dan tidak mengulangi pelanggaran.', 'SP-2026-3', '2026-04-03 19:57:59', NULL, NULL, NULL, NULL),
(51, 30, '2026-04-03', 'Siswa berjanji untuk memperbaiki perilaku dan tidak mengulangi pelanggaran.', 'SP-2026-4', '2026-04-03 20:28:44', NULL, NULL, NULL, NULL),
(52, 30, '2026-04-03', 'Siswa berjanji untuk memperbaiki perilaku dan tidak mengulangi pelanggaran.', 'SP-2026-5', '2026-04-03 20:29:17', NULL, NULL, NULL, NULL),
(53, 31, '2026-04-04', 'Siswa berjanji untuk memperbaiki perilaku dan tidak mengulangi pelanggaran.', 'SP-2026-7', '2026-04-04 23:02:10', NULL, NULL, NULL, NULL),
(54, 31, '2026-04-04', 'Siswa berjanji untuk memperbaiki perilaku dan tidak mengulangi pelanggaran.', 'SP-2026-8', '2026-04-04 23:18:36', NULL, NULL, NULL, NULL),
(55, 32, '2026-04-04', 'Siswa berjanji untuk memperbaiki perilaku dan tidak mengulangi pelanggaran.', 'SP-2026-9', '2026-04-05 07:31:20', NULL, NULL, NULL, NULL),
(56, 32, '2026-04-05', 'Siswa berjanji untuk memperbaiki perilaku dan tidak mengulangi pelanggaran.', 'SP-2026-10', '2026-04-05 08:29:07', NULL, NULL, NULL, NULL),
(57, 33, '2026-04-05', 'Siswa berjanji untuk memperbaiki perilaku dan tidak mengulangi pelanggaran.', 'SP-2026-11', '2026-04-05 08:46:47', NULL, NULL, NULL, NULL),
(58, 33, '2026-04-05', 'Siswa berjanji untuk memperbaiki perilaku dan tidak mengulangi pelanggaran.', 'SP-2026-12', '2026-04-05 10:51:30', NULL, NULL, NULL, NULL),
(59, 34, '2026-04-05', 'Siswa berjanji untuk memperbaiki perilaku dan tidak mengulangi pelanggaran.', 'SP-2026-13', '2026-04-05 23:21:30', NULL, NULL, NULL, NULL);

-- ── Tabel: `surat_pindah` ────────────────────────────────────
DROP TABLE IF EXISTS `surat_pindah`;
CREATE TABLE `surat_pindah` (
  `id_surat_pindah` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `tanggal_pindah` date NOT NULL,
  `alasan_pindah` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_surat_pindah`),
  KEY `fk_pindah_siswa` (`id_siswa`),
  CONSTRAINT `fk_pindah_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `surat_pindah` (`id_surat_pindah`, `id_siswa`, `tanggal_pindah`, `alasan_pindah`) VALUES
(6, 25, '2026-03-04', 'Siswa dipindahkan karena terlalu banyak pelanggaran'),
(8, 29, '2026-04-03', 'Siswa telah melewati batas toleransi yang ditentukan dan tetap melakukan tindakan yang melanggar peraturan sekolah secara berulang kali.'),
(9, 30, '2026-04-03', 'Siswa telah melewati batas toleransi yang ditentukan dan tetap melakukan tindakan yang melanggar peraturan sekolah secara berulang kali.'),
(10, 32, '2026-04-05', 'Siswa telah melewati batas toleransi yang ditentukan.'),
(11, 33, '2026-04-05', 'Siswa telah melewati batas toleransi yang ditentukan.');

-- ── Tabel: `surat_terbit_log` ────────────────────────────────────
DROP TABLE IF EXISTS `surat_terbit_log`;
CREATE TABLE `surat_terbit_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `tahap` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_surat` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_surat_ref` int NOT NULL,
  `tanggal_terbit` date NOT NULL,
  `created_by_user_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_siswa_tahap_jenis` (`id_siswa`,`tahap`,`jenis_surat`),
  CONSTRAINT `fk_terbit_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `surat_terbit_log` (`id`, `id_siswa`, `tahap`, `jenis_surat`, `id_surat_ref`, `tanggal_terbit`, `created_by_user_id`, `created_at`) VALUES
(1, 24, 'kuning-merah', 'perjanjian_siswa', 41, '2026-03-02', 2, '2026-03-02 17:18:44'),
(2, 24, 'kuning-merah', 'pemanggilan_ortu', 10, '2026-03-02', 2, '2026-03-02 17:27:16'),
(6, 24, 'kuning-merah', 'perjanjian_ortu', 42, '2026-03-02', 2, '2026-03-02 17:41:42'),
(7, 25, 'hijau-kuning', 'perjanjian_siswa', 43, '2026-03-04', 1, '2026-03-04 11:11:53'),
(8, 25, 'hijau-kuning', 'pemanggilan_ortu', 11, '2026-03-04', 1, '2026-03-04 11:12:33'),
(9, 25, 'kuning-merah', 'perjanjian_siswa', 44, '2026-03-04', 1, '2026-03-04 11:13:43'),
(10, 25, 'kuning-merah', 'pemanggilan_ortu', 12, '2026-03-04', 1, '2026-03-04 11:13:46'),
(11, 25, 'kuning-merah', 'perjanjian_ortu', 45, '2026-03-04', 1, '2026-03-04 11:13:48'),
(12, 25, 'merah-akhir', 'do', 8, '2026-03-04', 1, '2026-03-04 11:19:42'),
(13, 25, 'merah-akhir', 'pemanggilan_ortu', 13, '2026-03-04', 1, '2026-03-04 18:32:04'),
(14, 25, 'merah-akhir', 'pindah', 6, '2026-03-04', 1, '2026-03-04 18:32:12'),
(15, 26, 'hijau-kuning', 'pemanggilan_ortu', 14, '2026-03-09', 1, '2026-03-09 11:55:56'),
(16, 26, 'hijau-kuning', 'perjanjian_siswa', 46, '2026-03-26', 1, '2026-03-27 07:32:46'),
(24, 29, 'hijau-kuning', 'pemanggilan_ortu', 18, '2026-04-03', 1, '2026-04-03 19:51:15'),
(25, 29, 'hijau-kuning', 'perjanjian_siswa', 49, '2026-04-03', 1, '2026-04-03 19:54:26'),
(26, 29, 'kuning-merah', 'pemanggilan_ortu', 19, '2026-04-03', 1, '2026-04-03 19:57:27'),
(27, 29, 'kuning-merah', 'perjanjian_siswa', 50, '2026-04-03', 1, '2026-04-03 19:57:59'),
(28, 29, 'merah-akhir', 'pemanggilan_ortu', 20, '2026-04-03', 1, '2026-04-03 20:26:53'),
(29, 29, 'merah-akhir', 'do', 10, '2026-04-03', 1, '2026-04-03 20:27:07'),
(30, 29, 'merah-akhir', 'pindah', 8, '2026-04-03', 1, '2026-04-03 20:27:24'),
(31, 30, 'hijau-kuning', 'pemanggilan_ortu', 21, '2026-04-03', 1, '2026-04-03 20:28:28'),
(32, 30, 'hijau-kuning', 'perjanjian_siswa', 51, '2026-04-03', 1, '2026-04-03 20:28:44'),
(33, 30, 'kuning-merah', 'pemanggilan_ortu', 22, '2026-04-03', 1, '2026-04-03 20:29:11'),
(34, 30, 'kuning-merah', 'perjanjian_siswa', 52, '2026-04-03', 1, '2026-04-03 20:29:17'),
(35, 30, 'merah-akhir', 'pemanggilan_ortu', 23, '2026-04-03', 1, '2026-04-03 20:29:46'),
(36, 30, 'merah-akhir', 'do', 11, '2026-04-03', 1, '2026-04-03 20:29:52'),
(37, 30, 'merah-akhir', 'pindah', 9, '2026-04-03', 1, '2026-04-03 20:29:58'),
(38, 31, 'hijau-kuning', 'pemanggilan_ortu', 24, '2026-04-04', 2, '2026-04-04 22:23:05'),
(39, 31, 'hijau-kuning', 'perjanjian_siswa', 53, '2026-04-04', 2, '2026-04-04 23:02:10'),
(40, 31, 'kuning-merah', 'pemanggilan_ortu', 25, '2026-04-04', 2, '2026-04-04 23:17:22'),
(41, 31, 'kuning-merah', 'perjanjian_siswa', 54, '2026-04-04', 2, '2026-04-04 23:18:36'),
(42, 32, 'hijau-kuning', 'pemanggilan_ortu', 26, '2026-04-04', 1, '2026-04-05 07:31:01'),
(43, 32, 'hijau-kuning', 'perjanjian_siswa', 55, '2026-04-04', 1, '2026-04-05 07:31:20'),
(44, 32, 'kuning-merah', 'pemanggilan_ortu', 27, '2026-04-05', 1, '2026-04-05 08:18:22'),
(45, 32, 'kuning-merah', 'perjanjian_siswa', 56, '2026-04-05', 1, '2026-04-05 08:29:07'),
(46, 32, 'merah-akhir', 'pemanggilan_ortu', 28, '2026-04-05', 1, '2026-04-05 08:31:43'),
(47, 32, 'merah-akhir', 'do', 12, '2026-04-05', 1, '2026-04-05 08:32:03'),
(48, 32, 'merah-akhir', 'pindah', 10, '2026-04-05', 1, '2026-04-05 08:32:35'),
(49, 33, 'hijau-kuning', 'pemanggilan_ortu', 29, '2026-04-05', 1, '2026-04-05 08:46:21'),
(50, 33, 'hijau-kuning', 'perjanjian_siswa', 57, '2026-04-05', 1, '2026-04-05 08:46:47'),
(51, 33, 'kuning-merah', 'pemanggilan_ortu', 30, '2026-04-05', 1, '2026-04-05 10:51:04'),
(52, 33, 'kuning-merah', 'perjanjian_siswa', 58, '2026-04-05', 1, '2026-04-05 10:51:30'),
(53, 33, 'merah-akhir', 'pemanggilan_ortu', 31, '2026-04-05', 1, '2026-04-05 10:52:18'),
(54, 33, 'merah-akhir', 'do', 13, '2026-04-05', 1, '2026-04-05 10:52:24'),
(55, 33, 'merah-akhir', 'pindah', 11, '2026-04-05', 1, '2026-04-05 10:52:44'),
(56, 34, 'hijau-kuning', 'pemanggilan_ortu', 32, '2026-04-05', 1, '2026-04-05 20:30:57'),
(57, 34, 'hijau-kuning', 'perjanjian_siswa', 59, '2026-04-05', 1, '2026-04-05 23:21:30');

-- ── Tabel: `surat_upload` ────────────────────────────────────
DROP TABLE IF EXISTS `surat_upload`;
CREATE TABLE `surat_upload` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `nis` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tahap` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal` date NOT NULL,
  `stored_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size_bytes` int NOT NULL DEFAULT '0',
  `uploaded_by_user_id` int DEFAULT NULL,
  `uploaded_by_role` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `verified_at` datetime DEFAULT NULL,
  `verified_by_user_id` int DEFAULT NULL,
  `verified_by_role` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_surat_upload` (`id_siswa`,`jenis`,`tahap`),
  KEY `idx_surat_upload_nis` (`nis`),
  KEY `idx_surat_upload_tahap` (`tahap`),
  KEY `idx_surat_upload_tanggal` (`tanggal`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `surat_upload` (`id`, `id_siswa`, `nis`, `jenis`, `tahap`, `tanggal`, `stored_name`, `mime`, `size_bytes`, `uploaded_by_user_id`, `uploaded_by_role`, `uploaded_at`, `verified_at`, `verified_by_user_id`, `verified_by_role`) VALUES
(1, 18, 5000, 'perjanjian_siswa', 'hijau-kuning', '2026-02-04', '5000__perjanjian_siswa__hijau-kuning__2026-02-04.png', 'image/png', 265773, 2, 'bk', '2026-02-04 11:52:07', '2026-02-04 11:57:20', 2, 'bk');

-- ── Tabel: `template_surat` ────────────────────────────────────
DROP TABLE IF EXISTS `template_surat`;
CREATE TABLE `template_surat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_template` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `isi_template_html` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (tabel kosong, tidak ada data)

-- ── Tabel: `users` ────────────────────────────────────
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_asli` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','bk','guru','kepsek','siswa') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('aktif','nonaktif','pindah','DO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `kelas_wali` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `username`, `password`, `nama_asli`, `role`, `status`, `kelas_wali`, `created_at`, `last_login`, `last_activity`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator Utama', 'admin', 'aktif', NULL, '2026-01-12 11:46:35', '2026-04-06 00:04:01', '2026-04-06 00:04:02'),
(2, 'bk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Guru BK', 'bk', 'aktif', NULL, '2026-01-12 11:46:35', '2026-04-05 23:49:46', '2026-04-06 00:00:08'),
(3, '0001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kadek Wisnu', 'guru', 'aktif', 'X-A', '2026-01-12 11:46:35', '2026-04-06 00:03:50', '2026-04-06 00:03:51'),
(5, '0002', '$2y$12$iNox.3w86WqtnXIxi7uPmuiAgbit7gF0vVhqOCGUkJNoHu/G5/bXi', 'Wayan Yana', 'guru', 'aktif', 'XII-A', '2026-01-13 08:48:52', '2026-04-06 00:03:29', '2026-04-06 00:03:37'),
(16, '0000', '$2y$12$SOenOCtWfYuX74t8FNuE3eGgVSiyiXEMOb7R.hAvIojHjoQqkezOS', 'KepSek', 'kepsek', 'aktif', NULL, '2026-01-21 09:14:06', '2026-04-05 20:19:03', '2026-04-05 20:19:03'),
(28, '0003', '$2y$12$EOjbBzV70z9gne/9qQ8UFexHJTkZqu0xFajIED9MuINaep.ylFip6', 'Falao', 'guru', 'aktif', 'XI-A', '2026-02-10 09:28:33', '2026-04-06 00:02:26', '2026-04-06 00:02:27'),
(31, 5001, '$2y$12$T9rD5Ii6rDcYpg/TtUyfF.bKGZbqIq1E/qTRWWPIjeUkbHdWpZ7FK', 'W', 'siswa', 'DO', NULL, '2026-02-10 23:08:26', '2026-02-10 23:13:10', '2026-02-10 23:14:24'),
(33, 5002, '$2y$12$Fl5XJMI3B9sjn790rbO2d.F5rXeyGmL5sby/TKTUUnQexnKoMRVq2', 'Z', 'siswa', 'aktif', NULL, '2026-02-11 11:08:37', '2026-04-05 20:21:16', '2026-04-05 20:21:16'),
(34, 9999, '$2y$12$qiqdjx/7/HzCnXlGwDg1D.cbV3ikHJhwZrn3c9l7IyZ65rsvQAsGy', 'Endmin', 'admin', 'aktif', NULL, '2026-02-11 11:35:56', '2026-02-11 11:44:59', '2026-02-11 11:45:32'),
(35, 5003, '$2y$12$7hfozv6sI0a0HWhtO0dg9uiUqtjQmadhhKLj74o9024oHbHXFD1Pm', 'John', 'siswa', 'DO', NULL, '2026-03-04 11:07:11', NULL, NULL),
(36, 5004, '$2y$12$eHuY7tNGw0WuQNmegnbH0uF1H7wlAtrM7Wh9EKLUrWI2sU.10JKBe', 'Doe', 'siswa', 'aktif', NULL, '2026-03-04 11:16:29', '2026-04-05 18:57:19', '2026-04-05 18:57:19'),
(37, 'bk10', '$2y$12$zBwTg2dIZju1A2EVDsHR2eGyTDND8/PADkIbh5ds72e6UC.fDk6RK', 'Bk', 'bk', 'aktif', NULL, '2026-03-04 11:26:05', NULL, NULL),
(40, 5006, '$2y$12$5NPf9JceALF2jUK98NySP.ALeDIb0GEp4ZthuhOa6jFPM1SWZOhay', 'abc', 'siswa', 'DO', NULL, '2026-04-03 19:50:51', NULL, NULL),
(41, 5007, '$2y$12$aqQcTm2ivf3dKt2BxJGY7.ZPFOw6au5D5gv9ttyslc7L6z7yZdflG', 'JJ', 'siswa', 'DO', NULL, '2026-04-03 20:28:11', NULL, NULL),
(42, 5008, '$2y$12$zef2xTBxO4GL/.5Qh.TrguRE89MJI18xk1reAWMIEixh7QEEGY7YW', 'QWE', 'siswa', 'aktif', NULL, '2026-04-04 22:21:48', '2026-04-04 22:22:09', '2026-04-04 22:22:23'),
(43, 5009, '$2y$12$ukTvoOM4jB5t2B08S0TBWOJDY7n4fttaFMH2sxuSGPhkuPwWaZ8ni', 'WSX', 'siswa', 'DO', NULL, '2026-04-05 07:30:35', NULL, NULL),
(44, 5010, '$2y$12$Nbn7RLJ8PBR0T2OciK0P/.iHl6RtZsaA4ignziao746qbMViqJ6By', 'KK', 'siswa', 'DO', NULL, '2026-04-05 08:46:02', NULL, NULL),
(45, 5011, '$2y$12$.Ho8GnGWo2hkUiKHsrxNyOtJ3WjDur72KhvBkuzGNGpHYSZ1YTYIS', 'Seth', 'siswa', 'aktif', NULL, '2026-04-05 20:06:28', NULL, NULL);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Export selesai.
-- Untuk import: mysql -u root -p < db_sistem_poin_export.sql
-- Atau gunakan phpMyAdmin: Import > pilih file ini
-- ============================================================
