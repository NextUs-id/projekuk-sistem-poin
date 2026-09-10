<?php
/**
 * api/siswa.php
 * ═════════════
 * Fungsi: Menampilkan data siswa dan detail poin.
 * 
 * Endpoints:
 *   GET /api/siswa           → Daftar semua siswa aktif
 *   GET /api/siswa/detail?id=X → Detail siswa + total poin + history pelanggaran
 */
declare(strict_types=1);

$pdo    = getDB();
$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path   = rtrim($path, '/') ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// ── GET /api/siswa ─────────────────────────────────────────────
if ($path === '/api/siswa' && $method === 'GET') {
    requireRole(['admin', 'bk', 'guru', 'kepsek']);

    $role      = currentRole() ?? '';
    $kelasWali = '';

    // Guru hanya bisa lihat siswa di kelas walinya
    if ($role === 'guru') {
        $stmtK = $pdo->prepare('SELECT kelas_wali FROM users WHERE id = ? LIMIT 1');
        $stmtK->execute([currentUserId()]);
        $row       = $stmtK->fetch();
        $kelasWali = trim((string) ($row['kelas_wali'] ?? ''));
    }

    $q = trim((string) ($_GET['q'] ?? ''));
    $searchCond = '';
    $params = [];

    if ($role === 'guru' && $kelasWali !== '') {
        $searchCond .= ' AND s.kelas = ? ';
        $params[] = $kelasWali;
    }

    if ($q !== '') {
        $searchCond .= ' AND (s.nis LIKE ? OR s.nama LIKE ? OR s.kelas LIKE ?) ';
        $lk = "%{$q}%";
        $params[] = $lk;
        $params[] = $lk;
        $params[] = $lk;
    }

    $stmt = $pdo->prepare("
        SELECT s.id_siswa, s.nis, s.nama, s.kelas
        FROM siswa s
        JOIN users u ON u.username = s.nis AND u.role = 'siswa' AND u.status = 'aktif'
        WHERE 1=1 {$searchCond}
        ORDER BY s.kelas ASC, s.nis ASC, s.nama ASC
        LIMIT 50
    ");
    $stmt->execute($params);

    jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
}

// ── GET /api/siswa/detail ──────────────────────────────────────
if ($path === '/api/siswa/detail' && $method === 'GET') {
    requireRole(['admin', 'bk', 'guru', 'kepsek', 'siswa']);

    $role    = currentRole() ?? '';
    $idParam = (string) ($_GET['id'] ?? '');
    $idSiswa = 0;

    // Jika siswa = hanya bisa lihat diri sendiri
    if ($role === 'siswa') {
        $nis      = (string) ($_SESSION['username'] ?? '');
        $stmtFind = $pdo->prepare('SELECT id_siswa FROM siswa WHERE nis = ? LIMIT 1');
        $stmtFind->execute([$nis]);
        $rowFind  = $stmtFind->fetch();
        $idSiswa  = is_array($rowFind) ? (int) ($rowFind['id_siswa'] ?? 0) : 0;
        if ($idSiswa <= 0) jsonResponse(['ok' => false, 'error' => 'Data siswa tidak ditemukan.'], 404);
    } else {
        if ($idParam !== '') {
            $stmtFind = $pdo->prepare('SELECT id_siswa FROM siswa WHERE id_siswa = ? OR nis = ? LIMIT 1');
            $stmtFind->execute([(int)$idParam, $idParam]);
            $rowFind = $stmtFind->fetch();
            $idSiswa = is_array($rowFind) ? (int) ($rowFind['id_siswa'] ?? 0) : 0;
        }
    }

    if ($idSiswa <= 0) jsonResponse(['ok' => false, 'error' => 'ID siswa tidak valid atau tidak ditemukan.'], 422);

    // Cek akses guru (hanya kelas wali)
    if ($role === 'guru') {
        $stmtK = $pdo->prepare('SELECT kelas_wali FROM users WHERE id = ? LIMIT 1');
        $stmtK->execute([currentUserId()]);
        $row       = $stmtK->fetch();
        $kelasWali = trim((string) ($row['kelas_wali'] ?? ''));
        if ($kelasWali !== '') {
            $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM siswa WHERE id_siswa = ? AND kelas = ?');
            $stmtCheck->execute([$idSiswa, $kelasWali]);
            if ($stmtCheck->fetchColumn() == 0) {
                jsonResponse(['ok' => false, 'error' => 'Akses ditolak.'], 403);
            }
        }
    }

    // Ambil data siswa
    $stmtS = $pdo->prepare("
        SELECT s.*, u.status as user_status
        FROM siswa s
        LEFT JOIN users u ON u.username = s.nis
        WHERE s.id_siswa = ?
        LIMIT 1
    ");
    $stmtS->execute([$idSiswa]);
    $siswa = $stmtS->fetch();
    if (!$siswa) jsonResponse(['ok' => false, 'error' => 'Siswa tidak ditemukan.'], 404);

    // Hitung total poin (pelanggaran - pengurangan, sejak reset terakhir)
    $resetAt  = (string) ($siswa['poin_reset_at'] ?? '');
    $hasReset = $resetAt !== '';

    $stmtPoin = $pdo->prepare('
        SELECT
            COALESCE((SELECT SUM(jp.poin) FROM pelanggaran p JOIN jenis_pelanggaran jp ON p.id_jenis = jp.id_jenis WHERE p.id_siswa = ? AND p.deleted_at IS NULL AND (? = 0 OR p.created_at > ?)), 0)
            -
            COALESCE((SELECT SUM(sp.jumlah_pengurangan) FROM surat_pengurangan_poin sp WHERE sp.id_siswa = ? AND (? = 0 OR sp.tanggal >= DATE(?))), 0) AS total_poin
    ');
    $stmtPoin->execute([
        $idSiswa, $hasReset ? 1 : 0, $hasReset ? $resetAt : '1970-01-01 00:00:00',
        $idSiswa, $hasReset ? 1 : 0, $hasReset ? $resetAt : '1970-01-01 00:00:00',
    ]);
    $totalPoin = (int) $stmtPoin->fetchColumn();

    // History pelanggaran (10 terakhir)
    $userId = currentUserId();
    $stmtH  = $pdo->prepare('
        SELECT p.id_pelanggaran, p.tanggal, p.keterangan, p.created_by_user_id, jp.nama_jenis, jp.poin, u.nama_asli AS dibuat_oleh
        FROM pelanggaran p
        JOIN jenis_pelanggaran jp ON p.id_jenis = jp.id_jenis
        LEFT JOIN users u ON p.created_by_user_id = u.id
        WHERE p.id_siswa = ? AND p.deleted_at IS NULL AND (? = 0 OR p.created_at > ?)
        ORDER BY p.tanggal DESC, p.id_pelanggaran DESC
        LIMIT 10
    ');
    $stmtH->execute([$idSiswa, $hasReset ? 1 : 0, $hasReset ? $resetAt : '1970-01-01 00:00:00']);
    $historyRaw = $stmtH->fetchAll();

    $history = [];
    foreach ($historyRaw as $h) {
        $canDelete = false;
        if ($role === 'admin' || $role === 'bk') {
            $canDelete = true;
        } elseif ($role === 'guru' && $userId !== null) {
            $canDelete = (int) ($h['created_by_user_id'] ?? 0) === (int) $userId;
        }
        $h['can_delete'] = $canDelete;
        $history[]       = $h;
    }

    jsonResponse(['ok' => true, 'siswa' => $siswa, 'total_poin' => $totalPoin, 'history' => $history]);
}

// ── Fallback ───────────────────────────────────────────────────
jsonResponse(['ok' => false, 'error' => 'Endpoint tidak ditemukan.'], 404);
