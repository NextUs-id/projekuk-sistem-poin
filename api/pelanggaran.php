<?php
/**
 * api/pelanggaran.php
 * ═══════════════════
 * Fungsi: Mencatat dan menghapus pelanggaran siswa.
 * 
 * Endpoints:
 *   POST /api/pelanggaran/store  → Catat pelanggaran baru
 *   POST /api/pelanggaran/delete → Soft-delete pelanggaran (wajib alasan)
 */
declare(strict_types=1);

$pdo    = getDB();
$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path   = rtrim($path, '/') ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// ── GET /api/pelanggaran/recent ────────────────────────────────
// Ambil 10 pelanggaran terbaru (untuk dashboard)
if ($path === '/api/pelanggaran/recent' && $method === 'GET') {
    requireRole(['admin', 'bk', 'guru', 'kepsek']);

    $idSiswa = (int) ($_GET['siswa_id'] ?? 0);
    $limit   = max(1, min(50, (int) ($_GET['limit'] ?? 10)));

    if ($idSiswa > 0) {
        $stmt = $pdo->prepare("
            SELECT p.id_pelanggaran, p.tanggal, p.keterangan,
                   s.nama AS nama_siswa, s.nis, s.kelas,
                   jp.nama_jenis, jp.poin
            FROM pelanggaran p
            JOIN siswa s ON p.id_siswa = s.id_siswa
            JOIN jenis_pelanggaran jp ON p.id_jenis = jp.id_jenis
            WHERE p.deleted_at IS NULL AND p.id_siswa = ?
            ORDER BY p.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$idSiswa, $limit]);
    } else {
        $stmt = $pdo->prepare("
            SELECT p.id_pelanggaran, p.tanggal, p.keterangan,
                   s.nama AS nama_siswa, s.nis, s.kelas,
                   jp.nama_jenis, jp.poin
            FROM pelanggaran p
            JOIN siswa s ON p.id_siswa = s.id_siswa
            JOIN jenis_pelanggaran jp ON p.id_jenis = jp.id_jenis
            WHERE p.deleted_at IS NULL
            ORDER BY p.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
    }
    $data = $stmt->fetchAll();
    jsonResponse(['ok' => true, 'data' => $data]);
}

// ── POST /api/pelanggaran/store ────────────────────────────────
if ($path === '/api/pelanggaran/store' && $method === 'POST') {
    requireRole(['admin', 'bk', 'guru']);

    $role          = currentRole() ?? '';
    $currentUserId = requireAuth();

    $body       = jsonBody();
    $idSiswa    = (int) ($body['id_siswa'] ?? 0);
    $idJenis    = (int) ($body['id_jenis'] ?? 0);
    $tanggal    = (string) ($body['tanggal'] ?? '');
    $keterangan = trim((string) ($body['keterangan'] ?? ''));

    if ($idSiswa <= 0 || $idJenis <= 0 || $tanggal === '') {
        jsonResponse(['ok' => false, 'error' => 'Data belum lengkap.'], 422);
    }

    // Cek akses guru (hanya kelas wali)
    if ($role === 'guru') {
        $stmtK = $pdo->prepare('SELECT kelas_wali FROM users WHERE id = ? LIMIT 1');
        $stmtK->execute([$currentUserId]);
        $row       = $stmtK->fetch();
        $kelasWali = trim((string) ($row['kelas_wali'] ?? ''));
        if ($kelasWali !== '') {
            $cek = $pdo->prepare('SELECT COUNT(*) FROM siswa WHERE id_siswa = ? AND kelas = ?');
            $cek->execute([$idSiswa, $kelasWali]);
            if ((int) $cek->fetchColumn() === 0) {
                jsonResponse(['ok' => false, 'error' => 'Akses ditolak untuk siswa di luar kelas wali.'], 403);
            }
        }
    }

    // Cek jenis pelanggaran
    $jenisStmt = $pdo->prepare('SELECT nama_jenis, poin FROM jenis_pelanggaran WHERE id_jenis = ? LIMIT 1');
    $jenisStmt->execute([$idJenis]);
    $jenisRow = $jenisStmt->fetch();
    if (!is_array($jenisRow)) jsonResponse(['ok' => false, 'error' => 'Jenis pelanggaran tidak ditemukan.'], 404);

    $metadata = [
        'jenis'      => (string) ($jenisRow['nama_jenis'] ?? ''),
        'poin'       => (int) ($jenisRow['poin'] ?? 0),
        'tanggal'    => $tanggal,
        'keterangan' => $keterangan,
    ];

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO pelanggaran (id_siswa, id_jenis, tanggal, keterangan, created_by_user_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$idSiswa, $idJenis, $tanggal, $keterangan === '' ? null : $keterangan, $currentUserId]);
        $newId = (int) $pdo->lastInsertId();

        $ins = $pdo->prepare('INSERT INTO log_aktivitas (aksi, actor_user_id, actor_role, siswa_id, pelanggaran_id, alasan, metadata) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $ins->execute(['tambah_pelanggaran', $currentUserId, $role, $idSiswa, $newId, null, json_encode($metadata, JSON_UNESCAPED_UNICODE)]);

        $pdo->commit();
        jsonResponse(['ok' => true, 'id' => $newId, 'message' => 'Pelanggaran berhasil dicatat.']);
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResponse(['ok' => false, 'error' => 'Gagal menyimpan pelanggaran.'], 500);
    }
}

// ── POST /api/pelanggaran/delete ───────────────────────────────
if ($path === '/api/pelanggaran/delete' && $method === 'POST') {
    requireRole(['admin', 'bk', 'guru']);

    $role          = currentRole() ?? '';
    $currentUserId = requireAuth();

    $body           = jsonBody();
    $idPelanggaran  = (int) ($body['id_pelanggaran'] ?? 0);
    $alasan         = trim((string) ($body['alasan'] ?? ''));

    if ($idPelanggaran <= 0 || $alasan === '') {
        jsonResponse(['ok' => false, 'error' => 'Alasan wajib diisi.'], 422);
    }

    $stmt = $pdo->prepare('
        SELECT p.id_pelanggaran, p.id_siswa, p.id_jenis, p.tanggal, p.keterangan, p.created_by_user_id, p.deleted_at,
               s.kelas, s.nis, s.nama, jp.nama_jenis, jp.poin
        FROM pelanggaran p
        JOIN siswa s ON p.id_siswa = s.id_siswa
        JOIN jenis_pelanggaran jp ON p.id_jenis = jp.id_jenis
        WHERE p.id_pelanggaran = ? LIMIT 1
    ');
    $stmt->execute([$idPelanggaran]);
    $row = $stmt->fetch();
    if (!is_array($row)) jsonResponse(['ok' => false, 'error' => 'Data pelanggaran tidak ditemukan.'], 404);
    if (!empty($row['deleted_at'])) jsonResponse(['ok' => false, 'error' => 'Pelanggaran sudah dihapus.'], 409);

    // Cek akses guru
    if ($role === 'guru') {
        if ((int) ($row['created_by_user_id'] ?? 0) !== (int) $currentUserId) {
            jsonResponse(['ok' => false, 'error' => 'Anda hanya boleh menghapus pelanggaran yang Anda input.'], 403);
        }
        $stmtK = $pdo->prepare('SELECT kelas_wali FROM users WHERE id = ? LIMIT 1');
        $stmtK->execute([$currentUserId]);
        $rowK      = $stmtK->fetch();
        $kelasWali = trim((string) ($rowK['kelas_wali'] ?? ''));
        if ($kelasWali !== '' && (string) ($row['kelas'] ?? '') !== $kelasWali) {
            jsonResponse(['ok' => false, 'error' => 'Akses ditolak untuk siswa di luar kelas wali.'], 403);
        }
    }

    $metadata = [
        'jenis'              => (string) ($row['nama_jenis'] ?? ''),
        'poin'               => (int) ($row['poin'] ?? 0),
        'tanggal'            => (string) ($row['tanggal'] ?? ''),
        'keterangan'         => (string) ($row['keterangan'] ?? ''),
        'created_by_user_id' => (int) ($row['created_by_user_id'] ?? 0),
        'siswa_nis'          => (string) ($row['nis'] ?? ''),
        'siswa_nama'         => (string) ($row['nama'] ?? ''),
    ];

    $pdo->beginTransaction();
    try {
        $upd = $pdo->prepare('UPDATE pelanggaran SET deleted_at = NOW(), deleted_by_user_id = ?, delete_reason = ? WHERE id_pelanggaran = ?');
        $upd->execute([$currentUserId, $alasan, $idPelanggaran]);

        $ins = $pdo->prepare('INSERT INTO log_aktivitas (aksi, actor_user_id, actor_role, siswa_id, pelanggaran_id, alasan, metadata) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $ins->execute(['hapus_pelanggaran', $currentUserId, $role, (int) ($row['id_siswa'] ?? 0), $idPelanggaran, $alasan, json_encode($metadata, JSON_UNESCAPED_UNICODE)]);

        $pdo->commit();
        jsonResponse(['ok' => true]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResponse(['ok' => false, 'error' => 'Gagal menghapus pelanggaran.'], 500);
    }
}

// ── GET /api/pelanggaran/rekap ────────────────────────────────
if ($path === '/api/pelanggaran/rekap' && $method === 'GET') {
    requireRole(['admin', 'bk']);

    $tanggalMulai = isset($_GET['tanggal_mulai']) ? trim((string) $_GET['tanggal_mulai']) : '';
    $tanggalSelesai = isset($_GET['tanggal_selesai']) ? trim((string) $_GET['tanggal_selesai']) : '';
    $kelas = isset($_GET['kelas']) ? trim((string) $_GET['kelas']) : '';
    $jenis = isset($_GET['jenis']) ? trim((string) $_GET['jenis']) : '';

    $where = ['p.deleted_at IS NULL'];
    $params = [];

    if ($tanggalMulai !== '') {
        $where[] = 'p.tanggal >= ?';
        $params[] = $tanggalMulai;
    }
    if ($tanggalSelesai !== '') {
        $where[] = 'p.tanggal <= ?';
        $params[] = $tanggalSelesai;
    }
    if ($kelas !== '' && $kelas !== 'all') {
        $where[] = 's.kelas = ?';
        $params[] = $kelas;
    }
    if ($jenis !== '' && $jenis !== 'all') {
        $where[] = 'jp.id_jenis = ?';
        $params[] = (int) $jenis;
    }

    $whereSQL = 'WHERE ' . implode(' AND ', $where);

    // Fetch data
    $sql = "
        SELECT p.id_pelanggaran, p.tanggal, p.keterangan, p.created_at,
               s.id_siswa, s.nis, s.nama, s.kelas,
               jp.id_jenis, jp.nama_jenis, jp.poin, jp.kategori
        FROM pelanggaran p
        JOIN siswa s ON p.id_siswa = s.id_siswa
        JOIN jenis_pelanggaran jp ON p.id_jenis = jp.id_jenis
        {$whereSQL}
        ORDER BY p.tanggal DESC, p.id_pelanggaran DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    // Count total
    $countSQL = "SELECT COUNT(*) FROM pelanggaran p JOIN siswa s ON p.id_siswa = s.id_siswa JOIN jenis_pelanggaran jp ON p.id_jenis = jp.id_jenis {$whereSQL}";
    $stmtCount = $pdo->prepare($countSQL);
    $stmtCount->execute($params);
    $totalCount = (int) $stmtCount->fetchColumn();

    // By jenis
    $byJenisSQL = "
        SELECT jp.nama_jenis, COUNT(*) as jumlah
        FROM pelanggaran p
        JOIN siswa s ON p.id_siswa = s.id_siswa
        JOIN jenis_pelanggaran jp ON p.id_jenis = jp.id_jenis
        {$whereSQL}
        GROUP BY jp.id_jenis, jp.nama_jenis
        ORDER BY jumlah DESC
    ";
    $stmtByJenis = $pdo->prepare($byJenisSQL);
    $stmtByJenis->execute($params);
    $byJenis = $stmtByJenis->fetchAll();

    // By kelas
    $byClassSQL = "
        SELECT s.kelas, COUNT(*) as jumlah
        FROM pelanggaran p
        JOIN siswa s ON p.id_siswa = s.id_siswa
        JOIN jenis_pelanggaran jp ON p.id_jenis = jp.id_jenis
        {$whereSQL}
        GROUP BY s.kelas
        ORDER BY jumlah DESC
    ";
    $stmtByClass = $pdo->prepare($byClassSQL);
    $stmtByClass->execute($params);
    $byClass = $stmtByClass->fetchAll();

    // By bulan
    $byMonthSQL = "
        SELECT DATE_FORMAT(p.tanggal, '%Y-%m') as bulan, COUNT(*) as jumlah
        FROM pelanggaran p
        JOIN siswa s ON p.id_siswa = s.id_siswa
        JOIN jenis_pelanggaran jp ON p.id_jenis = jp.id_jenis
        {$whereSQL}
        GROUP BY DATE_FORMAT(p.tanggal, '%Y-%m')
        ORDER BY bulan DESC
    ";
    $stmtByMonth = $pdo->prepare($byMonthSQL);
    $stmtByMonth->execute($params);
    $byMonth = $stmtByMonth->fetchAll();

    // Jenis list for filter dropdown
    $jenisList = $pdo->query("SELECT id_jenis, nama_jenis, poin FROM jenis_pelanggaran ORDER BY nama_jenis ASC")->fetchAll();
    $kelasList = $pdo->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas ASC")->fetchAll(PDO::FETCH_COLUMN);

    jsonResponse([
        'ok' => true,
        'data' => $data,
        'summary' => [
            'total' => $totalCount,
            'by_jenis' => $byJenis ?: [],
            'by_class' => $byClass ?: [],
            'by_month' => $byMonth ?: []
        ],
        'filters' => [
            'jenis_list' => $jenisList ?: [],
            'kelas_list' => $kelasList ?: []
        ]
    ]);
}

// ── Fallback ───────────────────────────────────────────────────
jsonResponse(['ok' => false, 'error' => 'Endpoint tidak ditemukan.'], 404);
