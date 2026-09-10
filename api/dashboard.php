<?php
/**
 * api/dashboard.php
 * ═════════════════
 * Fungsi: Menyediakan data dashboard yang berbeda sesuai role user.
 * 
 * Endpoint:
 *   GET /api/dashboard → Return data sesuai role yang login
 */
declare(strict_types=1);

$pdo = getDB();

$userId = requireAuth();
$role   = currentRole() ?? '';
touchActivity($pdo);

$data = [
    'role' => $role,
    'nama' => (string) ($_SESSION['nama_asli'] ?? $_SESSION['username'] ?? ''),
];

try {
    // Gunakan log_aktivitas agar angka statistik sinkron dengan apa yang dilihat user di log (Request by user)
    $count30Sql = "SELECT COUNT(*) FROM log_aktivitas WHERE aksi = 'tambah_pelanggaran' AND created_at >= (NOW() - INTERVAL 30 DAY)";
    $count30 = (int) $pdo->query($count30Sql)->fetchColumn();

    // ── Admin ──────────────────────────────────────────────────
    if ($role === 'admin') {
        $data['total_users']       = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $data['total_online']      = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE last_activity IS NOT NULL AND last_activity >= (NOW() - INTERVAL 5 MINUTE)")->fetchColumn();
        $data['total_siswa_aktif'] = (int) $pdo->query("SELECT COUNT(*) FROM siswa s JOIN users u ON u.username = s.nis AND u.role = 'siswa' AND u.status = 'aktif'")->fetchColumn();
        $data['total_jenis']       = (int) $pdo->query('SELECT COUNT(*) FROM jenis_pelanggaran')->fetchColumn();
        $data['pelanggaran_30_hari'] = $count30;
        $data['waktu_server']      = date('Y-m-d H:i:s');
    }

    // ── BK ─────────────────────────────────────────────────────
    if ($role === 'bk') {
        $data['surat_belum'] = (int) $pdo->query("SELECT COUNT(*) FROM surat_orang_tua WHERE LOWER(status_kirim) NOT LIKE '%terkirim%'")->fetchColumn();
        $data['pelanggaran_30_hari'] = $count30;
    }

    // ── Guru ───────────────────────────────────────────────────
    if ($role === 'guru') {
        $stmtK = $pdo->prepare('SELECT kelas_wali FROM users WHERE id = ? LIMIT 1');
        $stmtK->execute([$userId]);
        $row = $stmtK->fetch();
        $data['kelas_wali'] = trim((string) ($row['kelas_wali'] ?? ''));
        $data['pelanggaran_30_hari'] = $count30;
    }

    // ── Siswa ──────────────────────────────────────────────────
    if ($role === 'siswa') {
        $nis    = (string) ($_SESSION['username'] ?? '');
        $stmtS  = $pdo->prepare('SELECT id_siswa, nis, nama, kelas, progress_level, poin_reset_at FROM siswa WHERE nis = ? LIMIT 1');
        $stmtS->execute([$nis]);
        $siswaRow = $stmtS->fetch();

        if (is_array($siswaRow)) {
            $idSiswa  = (int) $siswaRow['id_siswa'];
            $resetAt  = (string) ($siswaRow['poin_reset_at'] ?? '');
            $hasReset = $resetAt !== '';

            $stmtP = $pdo->prepare('SELECT COALESCE(SUM(jp.poin), 0) FROM pelanggaran p JOIN jenis_pelanggaran jp ON jp.id_jenis = p.id_jenis WHERE p.id_siswa = ? AND p.deleted_at IS NULL AND (? = 0 OR p.created_at > ?)');
            $stmtP->execute([$idSiswa, $hasReset ? 1 : 0, $hasReset ? $resetAt : '1970-01-01 00:00:00']);
            $poinPelanggaran = (int) $stmtP->fetchColumn();

            $stmtR = $pdo->prepare('SELECT COALESCE(SUM(jumlah_pengurangan), 0) FROM surat_pengurangan_poin WHERE id_siswa = ? AND (? = 0 OR tanggal >= DATE(?))');
            $stmtR->execute([$idSiswa, $hasReset ? 1 : 0, $hasReset ? $resetAt : '1970-01-01 00:00:00']);
            $poinReward = (int) $stmtR->fetchColumn();

            $data['siswa']      = $siswaRow;
            $data['total_poin'] = $poinPelanggaran - $poinReward;
        }
    }

    // ── Kepsek ─────────────────────────────────────────────────
    if ($role === 'kepsek') {
        $data['total_siswa_aktif']   = (int) $pdo->query("SELECT COUNT(*) FROM siswa s JOIN users u ON u.username = s.nis AND u.role = 'siswa' AND u.status = 'aktif'")->fetchColumn();
        $data['pelanggaran_30_hari'] = $count30;
    }
} catch (Throwable $e) {
    $data['debug_error'] = $e->getMessage();
}

jsonResponse(['ok' => true, 'data' => $data]);
