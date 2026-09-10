<?php
/**
 * api/jenis.php
 * ═════════════
 * Fungsi: CRUD jenis pelanggaran (kategori + poin).
 * 
 * Endpoints:
 *   GET  /api/jenis          → Daftar semua jenis pelanggaran
 *   GET  /api/jenis/grouped  → Grouped by kategori (SS, KS, PBM, dll)
 *   POST /api/jenis/store    → Tambah jenis baru
 *   POST /api/jenis/update   → Edit jenis
 *   POST /api/jenis/delete   → Hapus jenis
 */
declare(strict_types=1);

$pdo    = getDB();
$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path   = rtrim($path, '/') ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// ── GET /api/jenis ─────────────────────────────────────────────
if ($path === '/api/jenis' && $method === 'GET') {
    requireRole(['admin', 'bk', 'guru', 'kepsek']);

    $stmt = $pdo->prepare(
        'SELECT jp.id_jenis, jp.kode_kategori, jp.nama_jenis, jp.deskripsi, jp.poin,
                (SELECT COUNT(*) FROM pelanggaran p WHERE p.id_jenis = jp.id_jenis AND p.deleted_at IS NULL) AS jumlah_pakai
         FROM jenis_pelanggaran jp
         ORDER BY jp.id_jenis DESC'
    );
    $stmt->execute();
    jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
}

// ── GET /api/jenis/grouped ─────────────────────────────────────
if ($path === '/api/jenis/grouped' && $method === 'GET') {
    requireRole(['admin', 'bk', 'guru', 'kepsek']);

    $stmt = $pdo->prepare('SELECT id_jenis, nama_jenis, poin, kode_kategori FROM jenis_pelanggaran ORDER BY kode_kategori ASC, nama_jenis ASC');
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $kategoriLabels = [
        'SS'  => 'SS - Seragam Sekolah',
        'KS'  => 'KS - Kehadiran Di Sekolah',
        'PBM' => 'PBM - Proses Belajar Mengajar',
        'PNN' => 'PNN - Perilaku Negatif',
        'PB'  => 'PB - Perundungan/Bullying',
        'KB'  => 'KB - Kebersihan',
        'UB'  => 'UB - Upacara Bendera',
    ];

    $grouped = [];
    foreach ($rows as $j) {
        $k = trim((string) ($j['kode_kategori'] ?? ''));
        if ($k === '') $k = 'LAIN';
        if (!isset($grouped[$k])) {
            $grouped[$k] = [
                'kode'  => $k,
                'label' => $kategoriLabels[$k] ?? ($k === 'LAIN' ? 'Lainnya' : $k),
                'items' => [],
            ];
        }
        $grouped[$k]['items'][] = $j;
    }

    jsonResponse(['ok' => true, 'data' => array_values($grouped)]);
}

// ── POST /api/jenis/store ──────────────────────────────────────
if ($path === '/api/jenis/store' && $method === 'POST') {
    requireRole(['admin']);

    $body      = jsonBody();
    $kode      = trim((string) ($body['kode_kategori'] ?? ''));
    $nama      = trim((string) ($body['nama_jenis'] ?? ''));
    $deskripsi = trim((string) ($body['deskripsi'] ?? ''));
    $poin      = (int) ($body['poin'] ?? 0);

    $allowedKategori = ['', 'SS', 'KS', 'PBM', 'PNN', 'PB', 'KB', 'UB'];
    if (!in_array($kode, $allowedKategori, true)) $kode = '';
    if ($nama === '') jsonResponse(['ok' => false, 'error' => 'Nama jenis wajib diisi.'], 422);

    try {
        $stmt = $pdo->prepare('INSERT INTO jenis_pelanggaran (kode_kategori, nama_jenis, deskripsi, poin) VALUES (?, ?, ?, ?)');
        $stmt->execute([$kode === '' ? null : $kode, $nama, $deskripsi === '' ? null : $deskripsi, $poin]);
        jsonResponse(['ok' => true, 'id' => (int) $pdo->lastInsertId()]);
    } catch (Throwable $e) {
        jsonResponse(['ok' => false, 'error' => 'Gagal menambah jenis pelanggaran.'], 500);
    }
}

// ── POST /api/jenis/update ─────────────────────────────────────
if ($path === '/api/jenis/update' && $method === 'POST') {
    requireRole(['admin']);

    $body      = jsonBody();
    $id        = (int) ($body['id_jenis'] ?? $_GET['id'] ?? 0);
    $kode      = trim((string) ($body['kode_kategori'] ?? ''));
    $nama      = trim((string) ($body['nama_jenis'] ?? ''));
    $deskripsi = trim((string) ($body['deskripsi'] ?? ''));
    $poin      = (int) ($body['poin'] ?? 0);

    if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'ID tidak valid.'], 422);
    if ($nama === '') jsonResponse(['ok' => false, 'error' => 'Nama jenis wajib diisi.'], 422);

    $allowedKategori = ['', 'SS', 'KS', 'PBM', 'PNN', 'PB', 'KB', 'UB'];
    if (!in_array($kode, $allowedKategori, true)) $kode = '';

    try {
        $stmt = $pdo->prepare('UPDATE jenis_pelanggaran SET kode_kategori = ?, nama_jenis = ?, deskripsi = ?, poin = ? WHERE id_jenis = ?');
        $stmt->execute([$kode === '' ? null : $kode, $nama, $deskripsi === '' ? null : $deskripsi, $poin, $id]);
        jsonResponse(['ok' => true]);
    } catch (Throwable $e) {
        jsonResponse(['ok' => false, 'error' => 'Gagal menyimpan perubahan.'], 500);
    }
}

// ── POST /api/jenis/delete ─────────────────────────────────────
if ($path === '/api/jenis/delete' && $method === 'POST') {
    requireRole(['admin']);

    $body = jsonBody();
    $id   = (int) ($body['id_jenis'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'ID tidak valid.'], 422);

    try {
        $stmt = $pdo->prepare('DELETE FROM jenis_pelanggaran WHERE id_jenis = ?');
        $stmt->execute([$id]);
        jsonResponse(['ok' => true]);
    } catch (Throwable $e) {
        jsonResponse(['ok' => false, 'error' => 'Gagal menghapus. Jenis ini mungkin masih dipakai.'], 409);
    }
}

// ── Fallback ───────────────────────────────────────────────────
jsonResponse(['ok' => false, 'error' => 'Endpoint tidak ditemukan.'], 404);
