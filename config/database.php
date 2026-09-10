<?php
/**
 * config/database.php
 * ═══════════════════
 * Fungsi: Membuat koneksi ke database MySQL menggunakan PDO.
 * 
 * Cara pakai:
 *   require_once __DIR__ . '/../config/database.php';
 *   $pdo = getDB();
 */
declare(strict_types=1);

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // ── Konfigurasi Database ──
    $host = '127.0.0.1';
    $port = '3306';
    $name = 'db_sistem_poin';
    $user = 'root';
    $pass = '';

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        $pdo->exec("SET time_zone = '+08:00'");
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Koneksi database gagal.']);
        exit;
    }

    return $pdo;
}
