-- ============================================================
-- migrate_surat.sql
-- Migrasi tabel-tabel baru untuk sistem surat lengkap
-- Jalankan di phpMyAdmin atau MySQL CLI:
--   mysql -u root db_sistem_poin < migrate_surat.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── Pejabat Sekolah ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pejabat_sekolah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jabatan VARCHAR(100) NOT NULL UNIQUE,
    nama_pejabat VARCHAR(150) NOT NULL,
    nip VARCHAR(50) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO pejabat_sekolah (jabatan, nama_pejabat, nip) VALUES
('Kepala Sekolah', '-', '-'),
('Wakasek Kesiswaan', '-', '-'),
('Guru BK', '-', '-');

-- ── Auto-Numbering Surat ───────────────────────────────────
CREATE TABLE IF NOT EXISTS surat_counter (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jenis VARCHAR(50) NOT NULL,
    tahun INT NOT NULL,
    last_number INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_jenis_tahun (jenis, tahun)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Surat Pemanggilan Orang Tua ────────────────────────────
CREATE TABLE IF NOT EXISTS surat_pemanggilan_ortu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT NOT NULL,
    nomor VARCHAR(50) NULL,
    tanggal_surat DATE NOT NULL,
    hari_tanggal VARCHAR(100) NULL,
    pukul VARCHAR(50) NULL,
    tempat VARCHAR(100) NULL,
    keperluan TEXT NULL,
    created_by_user_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pemanggilan_siswa FOREIGN KEY (id_siswa) REFERENCES siswa(id_siswa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Surat DO ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS surat_do (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT NOT NULL,
    tanggal_surat DATE NOT NULL,
    nama_pengaju VARCHAR(150) NULL,
    alamat_pengaju TEXT NULL,
    telp_pengaju VARCHAR(50) NULL,
    alasan TEXT NULL,
    created_by_user_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_do_siswa FOREIGN KEY (id_siswa) REFERENCES siswa(id_siswa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Surat Pindah ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS surat_pindah (
    id_surat_pindah INT AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT NOT NULL,
    tanggal_pindah DATE NOT NULL,
    alasan_pindah TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pindah_siswa2 FOREIGN KEY (id_siswa) REFERENCES siswa(id_siswa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tambah kolom progress_level ke siswa jika belum ada ────
-- (sudah ada di proyek ini, baris ini tetap aman dijalankan ulang)
-- ALTER TABLE siswa ADD COLUMN IF NOT EXISTS progress_level TINYINT NOT NULL DEFAULT 0;
-- ALTER TABLE siswa ADD COLUMN IF NOT EXISTS poin_reset_at DATETIME NULL;
-- ALTER TABLE siswa ADD COLUMN IF NOT EXISTS last_stage_at DATETIME NULL;

-- ── Tracking Surat Terbit per Tahap (untuk auto-reset) ───────────
CREATE TABLE IF NOT EXISTS surat_terbit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT NOT NULL,
    tahap VARCHAR(50) NOT NULL,        -- 'hijau-kuning', 'kuning-merah', 'merah-akhir'
    jenis_surat VARCHAR(50) NOT NULL,  -- 'perjanjian_siswa', 'pemanggilan_ortu', 'perjanjian_ortu', 'do', 'pindah'
    id_surat_ref INT NOT NULL,         -- referensi ke tabel surat terkait
    tanggal_terbit DATE NOT NULL,
    created_by_user_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_siswa_tahap_jenis (id_siswa, tahap, jenis_surat),
    CONSTRAINT fk_terbit_siswa FOREIGN KEY (id_siswa) REFERENCES siswa(id_siswa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Selesai! Jalankan lalu refresh halaman.
-- ============================================================
