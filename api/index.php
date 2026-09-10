<?php
/**
 * api/index.php
 * ═════════════
 * Fungsi: ROUTER utama — menerima semua request dan mengarahkan
 *         ke file API yang sesuai.
 * 
 * Cara kerja:
 *   1. Baca URL path dan HTTP method
 *   2. Jika /api/... → panggil file API yang tepat
 *   3. Jika file statis (html/css/js) → sajikan langsung
 *   4. Jika tidak ditemukan → 404
 * 
 * Jalankan server:
 *   php -S localhost:8080 -t public api/index.php
 */
declare(strict_types=1);

// ── Parse Request ──────────────────────────────────────────────

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path       = parse_url($requestUri, PHP_URL_PATH) ?? '/';
$path       = rtrim($path, '/') ?: '/';
$method     = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// ── CORS Headers (untuk development) ──────────────────────────

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Load Dependencies ──────────────────────────────────────────

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/database.php';

// ── API Routes ─────────────────────────────────────────────────

if (str_starts_with($path, '/api/')) {
    ensureSession();

    // -- Auth --
    if ($path === '/api/login'  && $method === 'POST') { require __DIR__ . '/auth.php'; exit; }
    if ($path === '/api/logout' && $method === 'POST') { require __DIR__ . '/auth.php'; exit; }
    if ($path === '/api/me'     && $method === 'GET')   { require __DIR__ . '/auth.php'; exit; }

    // -- Users (Admin) --
    if (str_starts_with($path, '/api/users')) { require __DIR__ . '/users.php'; exit; }

    // -- Siswa --
    if (str_starts_with($path, '/api/siswa')) { require __DIR__ . '/siswa.php'; exit; }

    // -- Jenis Pelanggaran --
    if (str_starts_with($path, '/api/jenis')) { require __DIR__ . '/jenis.php'; exit; }

    // -- Pelanggaran --
    if (str_starts_with($path, '/api/pelanggaran')) { require __DIR__ . '/pelanggaran.php'; exit; }

    // -- Surat --
    if (str_starts_with($path, '/api/surat')) { require __DIR__ . '/surat.php'; exit; }

    // -- Dashboard --
    if ($path === '/api/dashboard' && $method === 'GET') { require __DIR__ . '/dashboard.php'; exit; }

    // -- Log Aktivitas --
    if ($path === '/api/log' && $method === 'GET') { require __DIR__ . '/log.php'; exit; }

    // -- Utility --
    if ($path === '/api/util/wali'          && $method === 'GET') { require __DIR__ . '/users.php'; exit; }
    if ($path === '/api/util/kelas-options' && $method === 'GET') { require __DIR__ . '/users.php'; exit; }

    // -- Admin: Kelas & Jurusan --
    if (str_starts_with($path, '/api/admin/')) { require __DIR__ . '/admin/kelas.php'; exit; }

    // -- API Not Found --
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'API tidak ditemukan.']);
    exit;
}

// ── Static Files (HTML, CSS, JS, images) ───────────────────────

$publicDir  = __DIR__ . '/../public';
$staticPath = $publicDir . $path;

// Jika root "/" → serve index.html
if ($path === '/') {
    $staticPath = $publicDir . '/index.html';
}

// Jika path tanpa extension → coba tambah .html
if (!pathinfo($path, PATHINFO_EXTENSION) && is_file($staticPath . '.html')) {
    $staticPath = $staticPath . '.html';
}

if (is_file($staticPath)) {
    $ext = strtolower(pathinfo($staticPath, PATHINFO_EXTENSION));

    $contentTypes = [
        'html' => 'text/html; charset=utf-8',
        'css'  => 'text/css; charset=utf-8',
        'js'   => 'application/javascript; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'pdf'  => 'application/pdf',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
    ];

    if (isset($contentTypes[$ext])) {
        header('Content-Type: ' . $contentTypes[$ext]);
    }

    readfile($staticPath);
    exit;
}

// ── 404 ────────────────────────────────────────────────────────

http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><title>404</title></head><body><h1>404 - Halaman tidak ditemukan</h1><p><a href="/">Kembali ke beranda</a></p></body></html>';
