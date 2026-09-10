<?php
/**
 * export_db.php
 * ═════════════
 * Generator SQL dump lengkap — struktur + data.
 * Jalankan sekali: php export_db.php
 * Output: db_sistem_poin_export.sql (siap import di laptop lain)
 *
 * Cara pakai di laptop baru:
 *   mysql -u root -p < db_sistem_poin_export.sql
 *   (atau import via phpMyAdmin)
 */
declare(strict_types=1);

// ── Konfigurasi ──────────────────────────────────────────────────
$host   = '127.0.0.1';
$port   = '3306';
$dbName = 'db_sistem_poin';
$user   = 'root';
$pass   = '';
$output = __DIR__ . '/db_sistem_poin_export.sql';
// ─────────────────────────────────────────────────────────────────

echo "🔌 Menghubungkan ke database '{$dbName}'...\n";

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $pdo->exec("SET time_zone = '+08:00'");
    echo "✅ Berhasil terhubung!\n\n";
} catch (PDOException $e) {
    die("❌ Gagal koneksi: " . $e->getMessage() . "\n");
}

// ── Header SQL ───────────────────────────────────────────────────
$now  = date('Y-m-d H:i:s');
$sql  = "-- ============================================================\n";
$sql .= "-- SiPoin — Sistem Poin Pelanggaran\n";
$sql .= "-- SMK TI Bali Global Denpasar\n";
$sql .= "-- Full Database Export\n";
$sql .= "-- Generated: {$now}\n";
$sql .= "-- ============================================================\n\n";

$sql .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
$sql .= "SET time_zone = '+08:00';\n";
$sql .= "SET FOREIGN_KEY_CHECKS = 0;\n";
$sql .= "SET NAMES utf8mb4;\n\n";

$sql .= "-- Buat database jika belum ada\n";
$sql .= "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
$sql .= "USE `{$dbName}`;\n\n";
$sql .= "-- ============================================================\n\n";

// ── Ambil semua tabel ────────────────────────────────────────────
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "📋 Ditemukan " . count($tables) . " tabel: " . implode(', ', $tables) . "\n\n";

foreach ($tables as $table) {
    echo "  📁 Mengekspor tabel: {$table}...\n";

    // -- Struktur tabel (CREATE TABLE) --
    $sql .= "-- ── Tabel: `{$table}` ────────────────────────────────────\n";
    $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";

    $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
    $createSql  = $createStmt['Create Table'];

    // Pastikan charset konsisten
    if (!str_contains($createSql, 'CHARSET=')) {
        $createSql .= " DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    }

    $sql .= $createSql . ";\n\n";

    // -- Data tabel (INSERT INTO) --
    $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll();
    $count = count($rows);

    if ($count === 0) {
        $sql .= "-- (tabel kosong, tidak ada data)\n\n";
        echo "     └ 0 baris (kosong)\n";
        continue;
    }

    // Ambil kolom
    $columns = array_keys($rows[0]);
    $colList  = implode(', ', array_map(fn($c) => "`{$c}`", $columns));

    $sql .= "INSERT INTO `{$table}` ({$colList}) VALUES\n";

    $valueSets = [];
    foreach ($rows as $row) {
        $vals = array_map(function ($val) use ($pdo) {
            if ($val === null) return 'NULL';
            if (is_numeric($val) && !str_starts_with((string)$val, '0')) return $val;
            // Escape string — manual karena PDO::quote tidak bisa dipanggil static
            return "'" . str_replace(
                ["\\", "'", "\n", "\r", "\x00", "\x1a"],
                ["\\\\", "\\'", "\\n", "\\r", "\\0", "\\Z"],
                (string)$val
            ) . "'";
        }, array_values($row));

        $valueSets[] = '(' . implode(', ', $vals) . ')';
    }

    // Chunk per 500 baris supaya tidak terlalu panjang satu INSERT
    $chunks = array_chunk($valueSets, 500);
    $firstChunk = true;
    foreach ($chunks as $chunk) {
        if (!$firstChunk) {
            $sql .= "INSERT INTO `{$table}` ({$colList}) VALUES\n";
        }
        $sql .= implode(",\n", $chunk) . ";\n\n";
        $firstChunk = false;
    }

    echo "     └ {$count} baris\n";
}

// ── Footer ───────────────────────────────────────────────────────
$sql .= "SET FOREIGN_KEY_CHECKS = 1;\n\n";
$sql .= "-- ============================================================\n";
$sql .= "-- Export selesai.\n";
$sql .= "-- Untuk import: mysql -u root -p < db_sistem_poin_export.sql\n";
$sql .= "-- Atau gunakan phpMyAdmin: Import > pilih file ini\n";
$sql .= "-- ============================================================\n";

// ── Tulis ke file ────────────────────────────────────────────────
file_put_contents($output, $sql);
$sizeKb = round(filesize($output) / 1024, 1);

echo "\n✅ SELESAI!\n";
echo "📄 File output : db_sistem_poin_export.sql\n";
echo "📦 Ukuran file : {$sizeKb} KB\n";
echo "📍 Lokasi      : {$output}\n\n";
echo "Cara import di laptop lain:\n";
echo "  Option 1 (CLI) : mysql -u root -p < db_sistem_poin_export.sql\n";
echo "  Option 2 (Web) : phpMyAdmin → Import → pilih file tersebut\n";
