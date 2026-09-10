<?php
/**
 * api/surat.php
 * ═════════════
 * Fungsi: Semua endpoint terkait surat BK.
 *         Upload/verifikasi, surat orang tua, pemanggilan, perjanjian,
 *         pengurangan poin, DO, pindah, dan utilitas.
 *
 * Endpoints:
 *   === UPLOAD & VERIFIKASI (existing) ===
 *   GET  /api/surat/list          → Daftar siswa + upload surat
 *   POST /api/surat/upload        → Upload file surat
 *   POST /api/surat/verify        → Verifikasi surat + auto-promosi
 *   POST /api/surat/verify-do     → Verifikasi DO (level 2→3)
 *
 *   === SURAT ORANG TUA ===
 *   GET  /api/surat/orangtua      → List surat orang tua
 *   POST /api/surat/orangtua      → Buat surat orang tua
 *   POST /api/surat/orangtua/status → Update status kirim
 *
 *   === PEMANGGILAN ORANG TUA ===
 *   GET  /api/surat/pemanggilan       → List surat pemanggilan
 *   POST /api/surat/pemanggilan       → Buat surat pemanggilan
 *   GET  /api/surat/pemanggilan/print → Data cetak surat
 *
 *   === PERJANJIAN ===
 *   GET  /api/surat/perjanjian       → List surat perjanjian
 *   POST /api/surat/perjanjian       → Buat surat perjanjian
 *   GET  /api/surat/perjanjian/print → Data cetak surat
 *
 *   === PENGURANGAN POIN ===
 *   GET  /api/surat/pengurangan → List pengurangan poin
 *   POST /api/surat/pengurangan → Catat pengurangan
 *
 *   === DO ===
 *   GET  /api/surat/do   → List surat DO
 *   POST /api/surat/do   → Buat surat DO
 *   GET  /api/surat/do/print → Data cetak
 *
 *   === PINDAH ===
 *   GET  /api/surat/pindah → List surat pindah
 *   POST /api/surat/pindah → Buat surat pindah
 *
 *   === UTILITAS ===
 *   GET  /api/surat/pejabat         → Data pejabat sekolah
 *   GET  /api/surat/siswa-eligible  → Siswa eligible (poin ≥ 30)
 */
declare(strict_types=1);

$pdo    = getDB();
$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path   = rtrim($path, '/') ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// ═══════════════════════════════════════════════════════════════
//  INTERNAL HELPERS
// ═══════════════════════════════════════════════════════════════

/**
 * Query sub-select untuk menghitung total poin siswa (poin pelanggaran - pengurangan).
 * Dipakai berulang di berbagai query.
 */
function poinSubQuery(): string
{
    return "(
        COALESCE((
            SELECT SUM(jp2.poin) FROM pelanggaran p2
            JOIN jenis_pelanggaran jp2 ON jp2.id_jenis = p2.id_jenis
            WHERE p2.id_siswa = s.id_siswa AND p2.deleted_at IS NULL
              AND (s.poin_reset_at IS NULL OR p2.created_at > s.poin_reset_at)
        ), 0)
        -
        COALESCE((
            SELECT SUM(sp2.jumlah_pengurangan) FROM surat_pengurangan_poin sp2
            WHERE sp2.id_siswa = s.id_siswa
              AND (s.poin_reset_at IS NULL OR sp2.tanggal >= DATE(s.poin_reset_at))
        ), 0)
    )";
}

/**
 * Auto-numbering surat.
 */
function nextSuratNumber(PDO $pdo, string $jenis, int $tahun): int
{
    $pdo->exec("INSERT INTO surat_counter (jenis, tahun, last_number)
                VALUES ('{$jenis}', {$tahun}, 1)
                ON DUPLICATE KEY UPDATE last_number = last_number + 1");
    $stmt = $pdo->prepare('SELECT last_number FROM surat_counter WHERE jenis = ? AND tahun = ?');
    $stmt->execute([$jenis, $tahun]);
    return (int) $stmt->fetchColumn();
}

/**
 * Cek dan promosi level siswa berdasarkan kelengkapan surat upload terverifikasi.
 */
function checkAndPromoteSiswa(PDO $pdo, int $idSiswa): array
{
    $stmt = $pdo->prepare('SELECT nis, progress_level FROM siswa WHERE id_siswa = ? LIMIT 1');
    $stmt->execute([$idSiswa]);
    $siswa = $stmt->fetch();
    if (!$siswa) return ['ok' => false, 'promoted' => false];

    $level     = (int) $siswa['progress_level'];
    $tahap     = '';
    $needs     = [];
    $nextLevel = $level + 1;

    if ($level === 0) {
        $tahap = 'hijau-kuning';
        $needs = ['perjanjian_siswa', 'pemanggilan_ortu'];
    } elseif ($level === 1) {
        $tahap = 'kuning-merah';
        $needs = ['perjanjian_siswa', 'pemanggilan_ortu', 'perjanjian_ortu'];
    } elseif ($level === 2) {
        $tahap = 'merah-akhir';
        $needs = ['do'];
    } else {
        return ['ok' => true, 'promoted' => false];
    }

    $stmtV = $pdo->prepare('SELECT jenis FROM surat_upload WHERE id_siswa = ? AND tahap = ? AND verified_at IS NOT NULL');
    $stmtV->execute([$idSiswa, $tahap]);
    $verifiedArr = $stmtV->fetchAll(PDO::FETCH_COLUMN);

    foreach ($needs as $n) {
        if (!in_array($n, $verifiedArr, true)) {
            return ['ok' => true, 'promoted' => false, 'missing' => $n];
        }
    }

    $pdo->prepare('UPDATE siswa SET progress_level = ?, poin_reset_at = NOW(), last_stage_at = NOW() WHERE id_siswa = ?')
        ->execute([$nextLevel, $idSiswa]);

    if ($nextLevel >= 3) {
        $pdo->prepare("UPDATE users SET status = 'DO' WHERE role = 'siswa' AND username = ?")
            ->execute([(string) $siswa['nis']]);
    }

    $pdo->prepare('INSERT INTO log_aktivitas (aksi, actor_user_id, actor_role, siswa_id, alasan, metadata) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute(['naik_level', currentUserId(), currentRole(), $idSiswa, 'Otomatis naik level setelah verifikasi dokumen lengkap.', json_encode(['from' => $level, 'to' => $nextLevel, 'tahap' => $tahap])]);

    return ['ok' => true, 'promoted' => true];
}

/**
 * Helper kompresi gambar.
 */
function compressAndSaveImage(string $sourcePath, string $destPath, int $quality = 75, int $maxWidth = 1200): bool
{
    if (!extension_loaded('gd')) return false;
    $imageType = @exif_imagetype($sourcePath);
    $image = null;
    switch ($imageType) {
        case IMAGETYPE_JPEG: $image = @imagecreatefromjpeg($sourcePath); break;
        case IMAGETYPE_PNG:  $image = @imagecreatefrompng($sourcePath); break;
        default: return false;
    }
    if (!$image) return false;

    $width  = imagesx($image);
    $height = imagesy($image);
    if ($width > $maxWidth) {
        $newWidth  = $maxWidth;
        $newHeight = (int) (($height / $width) * $newWidth);
        $resized   = imagecreatetruecolor($newWidth, $newHeight);
        if ($imageType === IMAGETYPE_PNG) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);
        $image = $resized;
    }
    $result = imagejpeg($image, $destPath, $quality);
    imagedestroy($image);
    return $result;
}

/**
 * Catat surat terbit ke log dan cek apakah bisa auto-reset.
 * Mengembalikan array dengan status reset.
 */
function catatSuratTerbit(PDO $pdo, int $idSiswa, string $jenisSurat, int $idSuratRef, string $tanggal): array
{
    $stmt = $pdo->prepare('SELECT nis, progress_level, last_stage_at FROM siswa WHERE id_siswa = ? LIMIT 1');
    $stmt->execute([$idSiswa]);
    $siswa = $stmt->fetch();
    if (!$siswa) return ['ok' => false, 'reset' => false, 'error' => 'Siswa tidak ditemukan'];

    $level = (int) $siswa['progress_level'];
    $tahap = '';

    if ($level === 0) {
        $tahap = 'hijau-kuning';
    } elseif ($level === 1) {
        $tahap = 'kuning-merah';
    } elseif ($level === 2) {
        $tahap = 'merah-akhir';
    } else {
        return ['ok' => true, 'reset' => false, 'message' => 'Siswa sudah di level akhir'];
    }

    $userId = currentUserId();
    $role = currentRole() ?? 'bk';

    try {
        $pdo->prepare('
            INSERT INTO surat_terbit_log (id_siswa, tahap, jenis_surat, id_surat_ref, tanggal_terbit, created_by_user_id)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE id_surat_ref = VALUES(id_surat_ref), tanggal_terbit = VALUES(tanggal_terbit), created_by_user_id = VALUES(created_by_user_id)
        ')->execute([$idSiswa, $tahap, $jenisSurat, $idSuratRef, $tanggal, $userId]);
    } catch (Throwable $e) {
        return ['ok' => false, 'reset' => false, 'error' => 'Gagal mencatat surat: ' . $e->getMessage()];
    }

    return checkAndAutoReset($pdo, $idSiswa, $level, $siswa['nis']);
}

/**
 * Cek apakah semua surat rekomendasi sudah terbit, jika ya auto reset.
 */
function checkAndAutoReset(PDO $pdo, int $idSiswa, int $currentLevel, string $nis): array
{
    $tahap = '';
    $needs = [];

    if ($currentLevel === 0) {
        $tahap = 'hijau-kuning';
        $needs = ['pemanggilan_ortu', 'perjanjian_siswa'];
    } elseif ($currentLevel === 1) {
        $tahap = 'kuning-merah';
        $needs = ['pemanggilan_ortu', 'perjanjian_siswa'];
    } elseif ($currentLevel === 2) {
        $tahap = 'merah-akhir';
        $needs = ['pemanggilan_ortu', 'do', 'pindah'];
    } else {
        return ['ok' => true, 'reset' => false, 'message' => 'Siswa sudah di level akhir'];
    }

    $stmt = $pdo->prepare('SELECT jenis_surat FROM surat_terbit_log WHERE id_siswa = ? AND tahap = ?');
    $stmt->execute([$idSiswa, $tahap]);
    $issuedArr = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $missing = [];
    foreach ($needs as $n) {
        if (!in_array($n, $issuedArr, true)) {
            $missing[] = $n;
        }
    }

    if (!empty($missing)) {
        return ['ok' => true, 'reset' => false, 'missing' => $missing];
    }

    $nextLevel = $currentLevel + 1;
    $userId = currentUserId();
    $role = currentRole() ?? 'bk';

    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE siswa SET progress_level = ?, poin_reset_at = NOW(), last_stage_at = NOW() WHERE id_siswa = ?')
            ->execute([$nextLevel, $idSiswa]);

        $statusUpdate = 'aktif';
        $alasanLog = '';

        if ($nextLevel >= 3) {
            $pdo->prepare("UPDATE users SET status = 'DO' WHERE role = 'siswa' AND username = ?")
                ->execute([$nis]);
            $statusUpdate = 'DO';
            $alasanLog = 'Surat DO/Pindah diterbitkan. Siswa berstatus DO dan akun dinonaktifkan.';
        } else {
            $alasanLog = 'Semua surat rekomendasi level ' . ($currentLevel === 0 ? 'Hijau' : 'Kuning') . ' telah terbit. Poin direset dan naik ke level ' . ($nextLevel === 1 ? 'Kuning' : 'Merah');
        }

        $pdo->prepare('INSERT INTO log_aktivitas (aksi, actor_user_id, actor_role, siswa_id, alasan, metadata) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute(['auto_reset_level', $userId, $role, $idSiswa, $alasanLog, json_encode(['from' => $currentLevel, 'to' => $nextLevel, 'tahap' => $tahap, 'nis' => $nis], JSON_UNESCAPED_UNICODE)]);

        $pdo->commit();
        return ['ok' => true, 'reset' => true, 'from' => $currentLevel, 'to' => $nextLevel, 'message' => $alasanLog];
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['ok' => false, 'reset' => false, 'error' => 'Gagal auto reset: ' . $e->getMessage()];
    }
}

/**
 * Ambil rekomendasi surat berdasarkan level siswa.
 */
function getRekomendasiSurat(PDO $pdo, int $idSiswa): array
{
    $stmt = $pdo->prepare('SELECT nis, progress_level FROM siswa WHERE id_siswa = ? LIMIT 1');
    $stmt->execute([$idSiswa]);
    $siswa = $stmt->fetch();
    if (!$siswa) return ['ok' => false, 'rekomendasi' => [], 'error' => 'Siswa tidak ditemukan'];

    $level = (int) $siswa['progress_level'];
    $needs = [];
    $tahap = '';

    if ($level === 0) {
        $tahap = 'hijau-kuning';
        $needs = [
            ['jenis' => 'pemanggilan_ortu', 'label' => 'Surat Pemanggilan Orang Tua'],
            ['jenis' => 'perjanjian_siswa', 'label' => 'Surat Perjanjian Siswa'],
        ];
    } elseif ($level === 1) {
        $tahap = 'kuning-merah';
        $needs = [
            ['jenis' => 'pemanggilan_ortu', 'label' => 'Surat Pemanggilan Orang Tua'],
            ['jenis' => 'perjanjian_siswa', 'label' => 'Surat Perjanjian Siswa'],
        ];
    } elseif ($level === 2) {
        $tahap = 'merah-akhir';
        $needs = [
            ['jenis' => 'pemanggilan_ortu', 'label' => 'Surat Pemanggilan Orang Tua'],
            ['jenis' => 'do',               'label' => 'Surat DO (Drop Out)'],
            ['jenis' => 'pindah',            'label' => 'Surat Keterangan Pindah'],
        ];
    } else {
        return ['ok' => true, 'level' => $level, 'rekomendasi' => [], 'message' => 'Siswa sudah di level akhir'];
    }

    $stmtIssued = $pdo->prepare('SELECT jenis_surat FROM surat_terbit_log WHERE id_siswa = ? AND tahap = ?');
    $stmtIssued->execute([$idSiswa, $tahap]);
    $issuedArr = $stmtIssued->fetchAll(PDO::FETCH_COLUMN);

    $result = [];
    foreach ($needs as $n) {
        $result[] = [
            'jenis' => $n['jenis'],
            'label' => $n['label'],
            'terbit' => in_array($n['jenis'], $issuedArr, true),
            'pilih_satu' => $n['pilih_satu'] ?? false
        ];
    }

    return ['ok' => true, 'level' => $level, 'tahap' => $tahap, 'rekomendasi' => $result];
}


// ═══════════════════════════════════════════════════════════════
//  UPLOAD & VERIFIKASI (existing endpoints — preserved)
// ═══════════════════════════════════════════════════════════════

// ── GET /api/surat/list ────────────────────────────────────────
if ($path === '/api/surat/list' && $method === 'GET') {
    requireRole(['admin', 'bk']);

    $poinSQL = poinSubQuery();
    $stmt = $pdo->prepare("
        SELECT s.id_siswa, s.nis, s.nama, s.kelas, s.progress_level,
               {$poinSQL} AS total_poin
        FROM siswa s
        JOIN users u ON u.username = s.nis AND u.role = 'siswa' AND u.status = 'aktif'
        HAVING total_poin >= 30 AND progress_level < 3
        ORDER BY total_poin DESC
    ");
    $stmt->execute();
    $siswaButuh = $stmt->fetchAll();

    // Aggregasi status surat yang SUDAH DITERBITKAN (bukan upload)
    $siswaIds = array_column($siswaButuh, 'id_siswa');
    $issuedSummary = [];
    
    if (!empty($siswaIds)) {
        $idsStr = implode(',', array_map('intval', $siswaIds));
        
        // Cek Surat Orang Tua (via pelanggaran)
        $stmtOT = $pdo->query("SELECT p.id_siswa, COUNT(*) as jml FROM surat_orang_tua so JOIN pelanggaran p ON p.id_pelanggaran = so.id_pelanggaran WHERE p.id_siswa IN ($idsStr) GROUP BY p.id_siswa");
        $countsOT = $stmtOT->fetchAll(PDO::FETCH_KEY_PAIR);

        // Cek Surat Pemanggilan
        $stmtSP = $pdo->query("SELECT id_siswa, COUNT(*) as jml FROM surat_pemanggilan_ortu WHERE id_siswa IN ($idsStr) GROUP BY id_siswa");
        $countsSP = $stmtSP->fetchAll(PDO::FETCH_KEY_PAIR);

        // Cek Surat Perjanjian
        $stmtSJ = $pdo->query("SELECT id_siswa, COUNT(*) as jml FROM surat_perjanjian WHERE id_siswa IN ($idsStr) GROUP BY id_siswa");
        $countsSJ = $stmtSJ->fetchAll(PDO::FETCH_KEY_PAIR);

        // Cek Surat DO
        $stmtDO = $pdo->query("SELECT id_siswa, COUNT(*) as jml FROM surat_do WHERE id_siswa IN ($idsStr) GROUP BY id_siswa");
        $countsDO = $stmtDO->fetchAll(PDO::FETCH_KEY_PAIR);

        foreach ($siswaButuh as $s) {
            $id = (int)$s['id_siswa'];
            $issuedSummary[$id] = [
                'orangtua' => ($countsOT[$id] ?? 0) > 0,
                'pemanggilan_ortu' => ($countsSP[$id] ?? 0) > 0,
                'perjanjian' => ($countsSJ[$id] ?? 0) > 0,
                'do' => ($countsDO[$id] ?? 0) > 0,
            ];
        }
    }

    $stmtU = $pdo->prepare('SELECT * FROM surat_upload');
    $stmtU->execute();
    $uploads = $stmtU->fetchAll();

    jsonResponse([
        'ok' => true, 
        'siswa' => $siswaButuh, 
        'uploads' => $uploads,
        'issued' => $issuedSummary
    ]);
}

// ── POST /api/surat/upload ─────────────────────────────────────
if ($path === '/api/surat/upload' && $method === 'POST') {
    requireRole(['admin', 'bk']);

    $idSiswa = (int) ($_POST['id_siswa'] ?? 0);
    $jenis   = trim((string) ($_POST['jenis'] ?? ''));
    $tahap   = trim((string) ($_POST['tahap'] ?? ''));
    $tanggal = (string) ($_POST['tanggal'] ?? '');

    if ($idSiswa <= 0 || $jenis === '' || $tahap === '' || $tanggal === '') {
        jsonResponse(['ok' => false, 'error' => 'Data tidak lengkap.'], 422);
    }
    if (!isset($_FILES['file']) || (int) $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['ok' => false, 'error' => 'File tidak valid.'], 422);
    }

    $tmpPath  = $_FILES['file']['tmp_name'];
    $origName = $_FILES['file']['name'];
    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'], true)) {
        jsonResponse(['ok' => false, 'error' => 'Format file harus JPG, PNG, atau PDF.'], 415);
    }

    $stmtN = $pdo->prepare('SELECT nis FROM siswa WHERE id_siswa = ? LIMIT 1');
    $stmtN->execute([$idSiswa]);
    $nis = (string) ($stmtN->fetchColumn() ?: '');
    if ($nis === '') jsonResponse(['ok' => false, 'error' => 'Siswa tidak ditemukan.'], 404);

    $safeNis    = preg_replace('/[^a-zA-Z0-9]/', '_', $nis);
    $uploadDir  = dirname(__DIR__) . '/public/uploads/surat';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);

    if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
        $storedName = $safeNis . '_' . $jenis . '_' . $tahap . '_' . time() . '.jpg';
        $destPath   = $uploadDir . '/' . $storedName;
        if (!compressAndSaveImage($tmpPath, $destPath, 70, 1200)) {
            if (!move_uploaded_file($tmpPath, $destPath)) {
                jsonResponse(['ok' => false, 'error' => 'Gagal menyimpan file.'], 500);
            }
        }
    } else {
        $storedName = $safeNis . '_' . $jenis . '_' . $tahap . '_' . time() . '.pdf';
        $destPath   = $uploadDir . '/' . $storedName;
        if (!move_uploaded_file($tmpPath, $destPath)) {
            jsonResponse(['ok' => false, 'error' => 'Gagal menyimpan file.'], 500);
        }
    }

    $userId = currentUserId();
    $role   = currentRole() ?? '';

    try {
        $stmt = $pdo->prepare('
            INSERT INTO surat_upload (id_siswa, nis, jenis, tahap, tanggal, stored_name, mime, size_bytes, uploaded_by_user_id, uploaded_by_role)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                tanggal = VALUES(tanggal), stored_name = VALUES(stored_name), mime = VALUES(mime),
                size_bytes = VALUES(size_bytes), uploaded_by_user_id = VALUES(uploaded_by_user_id),
                uploaded_by_role = VALUES(uploaded_by_role), uploaded_at = NOW(),
                verified_at = NULL, verified_by_user_id = NULL
        ');
        $stmt->execute([
            $idSiswa, $nis, $jenis, $tahap, $tanggal, $storedName,
            (str_ends_with($storedName, '.pdf') ? 'application/pdf' : 'image/jpeg'),
            filesize($destPath), $userId, $role,
        ]);
        jsonResponse(['ok' => true, 'stored_name' => $storedName]);
    } catch (Throwable $e) {
        jsonResponse(['ok' => false, 'error' => 'Gagal mencatat data upload ke database.'], 500);
    }
}

// ── POST /api/surat/verify ─────────────────────────────────────
if ($path === '/api/surat/verify' && $method === 'POST') {
    requireRole(['admin', 'bk']);
    $body     = jsonBody();
    $idUpload = (int) ($body['id'] ?? 0);
    if ($idUpload <= 0) jsonResponse(['ok' => false, 'error' => 'ID tidak valid.'], 422);

    $userId = currentUserId();
    $role   = currentRole() ?? '';

    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE surat_upload SET verified_at = NOW(), verified_by_user_id = ?, verified_by_role = ? WHERE id = ?')
            ->execute([$userId, $role, $idUpload]);
        $stmtS  = $pdo->prepare('SELECT id_siswa FROM surat_upload WHERE id = ?');
        $stmtS->execute([$idUpload]);
        $idSiswa = (int) $stmtS->fetchColumn();
        $promo = checkAndPromoteSiswa($pdo, $idSiswa);
        $pdo->commit();
        jsonResponse(['ok' => true, 'promoted' => $promo['ok'] && $promo['promoted']]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResponse(['ok' => false, 'error' => 'Gagal verifikasi surat.'], 500);
    }
}

// ── POST /api/surat/verify-do ──────────────────────────────────
if ($path === '/api/surat/verify-do' && $method === 'POST') {
    requireRole(['admin', 'bk']);
    $body    = jsonBody();
    $idSiswa = (int) ($body['id_siswa'] ?? 0);
    if ($idSiswa <= 0) jsonResponse(['ok' => false, 'error' => 'ID siswa tidak valid.'], 422);

    $stmt = $pdo->prepare('SELECT nis, progress_level, last_stage_at FROM siswa WHERE id_siswa = ? LIMIT 1');
    $stmt->execute([$idSiswa]);
    $siswa = $stmt->fetch();
    if (!$siswa) jsonResponse(['ok' => false, 'error' => 'Siswa tidak ditemukan.'], 404);

    $level = (int) $siswa['progress_level'];
    if ($level !== 2) jsonResponse(['ok' => false, 'error' => 'Siswa belum di level Merah.'], 422);

    $lastStageAt = $siswa['last_stage_at'] ? (string) $siswa['last_stage_at'] : '1970-01-01 00:00:00';
    $stmtCheck   = $pdo->prepare('SELECT id FROM surat_do WHERE id_siswa = ? AND created_at >= ? LIMIT 1');
    $stmtCheck->execute([$idSiswa, $lastStageAt]);
    if (!(bool) $stmtCheck->fetchColumn()) {
        jsonResponse(['ok' => false, 'error' => 'Surat DO belum diterbitkan.'], 422);
    }

    $userId = currentUserId();
    $role   = currentRole() ?? '';

    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE siswa SET progress_level = 3, poin_reset_at = NOW(), last_stage_at = NOW() WHERE id_siswa = ?')
            ->execute([$idSiswa]);
        $nis = (string) ($siswa['nis'] ?? '');
        if ($nis !== '') {
            $pdo->prepare("UPDATE users SET status = 'DO' WHERE role = 'siswa' AND username = ?")
                ->execute([$nis]);
        }
        $pdo->prepare('INSERT INTO log_aktivitas (aksi, actor_user_id, actor_role, siswa_id, alasan, metadata) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute(['verifikasi_do', $userId, $role, $idSiswa, 'Surat DO diverifikasi. Siswa resmi berstatus DO dan akun dinonaktifkan.',
                json_encode(['from' => 2, 'to' => 3, 'tahap' => 'merah-akhir', 'nis' => $nis], JSON_UNESCAPED_UNICODE)]);
        $pdo->commit();
        jsonResponse(['ok' => true, 'message' => 'Siswa berhasil di-DO dan akun dinonaktifkan.']);
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResponse(['ok' => false, 'error' => 'Gagal memproses verifikasi DO.'], 500);
    }
}


// ═══════════════════════════════════════════════════════════════
//  UTILITAS
// ═══════════════════════════════════════════════════════════════

// ── GET /api/surat/pejabat ─────────────────────────────────────
if ($path === '/api/surat/pejabat' && $method === 'GET') {
    requireRole(['admin', 'bk']);
    $rows = $pdo->query('SELECT jabatan, nama_pejabat, nip FROM pejabat_sekolah')->fetchAll();
    jsonResponse(['ok' => true, 'data' => $rows]);
}

// ── GET /api/surat/siswa-eligible ──────────────────────────────
// Parameter opsional: ?level=0,1 (filter progress_level, default semua < 3)
//                     ?min_poin=30 (default 30)
if ($path === '/api/surat/siswa-eligible' && $method === 'GET') {
    requireRole(['admin', 'bk', 'guru']);

    $minPoin = (int) ($_GET['min_poin'] ?? 30);
    $levelFilter = isset($_GET['level']) ? array_map('intval', explode(',', $_GET['level'])) : [];
    $poinSQL = poinSubQuery();

    $whereLevel = '';
    if (!empty($levelFilter)) {
        $in = implode(',', $levelFilter);
        $whereLevel = "WHERE s.progress_level IN ({$in})";
    } else {
        $whereLevel = "WHERE s.progress_level < 3";
    }

    $stmt = $pdo->prepare("
        SELECT s.id_siswa, s.nis, s.nama, s.kelas, s.progress_level,
               {$poinSQL} AS total_poin
        FROM siswa s
        JOIN users u ON u.username = s.nis AND u.role = 'siswa' AND u.status = 'aktif'
        {$whereLevel}
        HAVING total_poin >= {$minPoin}
        ORDER BY s.kelas ASC, s.nis ASC, s.nama ASC
    ");
    $stmt->execute();
    jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
}


// ═══════════════════════════════════════════════════════════════
//  SURAT ORANG TUA
// ═══════════════════════════════════════════════════════════════

// ── GET /api/surat/orangtua ────────────────────────────────────
if ($path === '/api/surat/orangtua' && $method === 'GET') {
    requireRole(['admin', 'bk']);
    $rows = $pdo->query(
        'SELECT so.id_surat_orangtua, so.tanggal_cetak, so.status_kirim,
                p.id_pelanggaran, p.tanggal, s.nis, s.nama, s.kelas, jp.nama_jenis
         FROM surat_orang_tua so
         JOIN pelanggaran p ON p.id_pelanggaran = so.id_pelanggaran
         JOIN siswa s ON s.id_siswa = p.id_siswa
         JOIN jenis_pelanggaran jp ON jp.id_jenis = p.id_jenis
         ORDER BY so.id_surat_orangtua DESC'
    )->fetchAll();
    jsonResponse(['ok' => true, 'data' => $rows]);
}

// ── POST /api/surat/orangtua ───────────────────────────────────
if ($path === '/api/surat/orangtua' && $method === 'POST') {
    requireRole(['admin', 'bk']);
    $body = jsonBody();
    $idSiswa     = (int) ($body['id_siswa'] ?? 0);
    $tanggalCetak = (string) ($body['tanggal_cetak'] ?? '');
    $statusKirim  = trim((string) ($body['status_kirim'] ?? ''));
    $validStatus  = ['Belum Terkirim', 'Pending', 'Terkirim'];

    if ($idSiswa <= 0 || $tanggalCetak === '' || !in_array($statusKirim, $validStatus, true)) {
        jsonResponse(['ok' => false, 'error' => 'Data tidak lengkap.'], 422);
    }

    // Cek progress level
    $stmt = $pdo->prepare('SELECT progress_level FROM siswa WHERE id_siswa = ? LIMIT 1');
    $stmt->execute([$idSiswa]);
    $progressLevel = (int) ($stmt->fetchColumn() ?: 0);
    if ($progressLevel !== 0 && $progressLevel !== 1) {
        jsonResponse(['ok' => false, 'error' => 'Siswa sudah melewati tahap Kuning.'], 422);
    }

    // Cari pelanggaran terakhir
    $stmt = $pdo->prepare('SELECT id_pelanggaran FROM pelanggaran WHERE id_siswa = ? AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$idSiswa]);
    $idPelanggaran = (int) ($stmt->fetchColumn() ?: 0);
    if ($idPelanggaran <= 0) {
        jsonResponse(['ok' => false, 'error' => 'Siswa belum memiliki pelanggaran.'], 422);
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO surat_orang_tua (id_pelanggaran, tanggal_cetak, status_kirim) VALUES (?, ?, ?)');
        $stmt->execute([$idPelanggaran, $tanggalCetak, $statusKirim]);
        $idSuratBaru = (int) $pdo->lastInsertId();

        $pdo->commit();

        $autoReset = false;
        $resetInfo = null;

        if ($statusKirim === 'Terkirim') {
            $resetResult = catatSuratTerbit($pdo, $idSiswa, 'pemanggilan_ortu', $idSuratBaru, $tanggalCetak);
            $autoReset = $resetResult['reset'] ?? false;
            $resetInfo = $resetResult['reset'] ? [
                'from_level' => $resetResult['from'],
                'to_level' => $resetResult['to'],
                'message' => $resetResult['message']
            ] : ($resetResult['missing'] ?? null);
        }

        jsonResponse([
            'ok' => true,
            'message' => 'Surat orang tua berhasil dibuat.',
            'id' => $idSuratBaru,
            'auto_reset' => $autoReset,
            'reset_info' => $resetInfo
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        jsonResponse(['ok' => false, 'error' => 'Gagal menyimpan surat: ' . $e->getMessage()], 500);
    }
}

// ── POST /api/surat/orangtua/status ────────────────────────────
if ($path === '/api/surat/orangtua/status' && $method === 'POST') {
    requireRole(['admin', 'bk']);
    $body = jsonBody();
    $idSurat     = (int) ($body['id_surat_orangtua'] ?? 0);
    $statusKirim = trim((string) ($body['status_kirim'] ?? ''));
    $validStatus = ['Belum Terkirim', 'Pending', 'Terkirim', 'Batal Terkirim'];

    if ($idSurat <= 0 || !in_array($statusKirim, $validStatus, true)) {
        jsonResponse(['ok' => false, 'error' => 'Data tidak valid.'], 422);
    }

    $pdo->beginTransaction();
    try {
        // Ambil data surat + siswa
        $stmt = $pdo->prepare(
            'SELECT so.status_kirim, s.id_siswa, s.progress_level
             FROM surat_orang_tua so
             JOIN pelanggaran p ON p.id_pelanggaran = so.id_pelanggaran
             JOIN siswa s ON s.id_siswa = p.id_siswa
             WHERE so.id_surat_orangtua = ? LIMIT 1'
        );
        $stmt->execute([$idSurat]);
        $suratRow = $stmt->fetch();
        if (!$suratRow) throw new RuntimeException('Surat tidak ditemukan.');

        $statusSebelumnya = trim((string) ($suratRow['status_kirim'] ?? ''));
        $idSiswa          = (int) $suratRow['id_siswa'];
        $progressLevel    = (int) $suratRow['progress_level'];

        // Update status
        $pdo->prepare('UPDATE surat_orang_tua SET status_kirim = ? WHERE id_surat_orangtua = ?')
            ->execute([$statusKirim, $idSurat]);

        $autoReset = false;
        $resetInfo = null;

        // Naik Level: status baru = Terkirim, sebelumnya bukan Terkirim
        if ($statusKirim === 'Terkirim' && $statusSebelumnya !== 'Terkirim' && ($progressLevel === 0 || $progressLevel === 1)) {
            $resetResult = catatSuratTerbit($pdo, $idSiswa, 'pemanggilan_ortu', $idSurat, date('Y-m-d'));
            $autoReset = $resetResult['reset'] ?? false;
            $resetInfo = $resetResult['reset'] ? [
                'from_level' => $resetResult['from'],
                'to_level' => $resetResult['to'],
                'message' => $resetResult['message']
            ] : ($resetResult['missing'] ?? null);
        }

        $pdo->commit();
        jsonResponse([
            'ok' => true,
            'message' => 'Status surat berhasil diperbarui.',
            'auto_reset' => $autoReset,
            'reset_info' => $resetInfo
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        jsonResponse(['ok' => false, 'error' => 'Gagal: ' . $e->getMessage()], 500);
    }
}


// ═══════════════════════════════════════════════════════════════
//  PEMANGGILAN ORANG TUA
// ═══════════════════════════════════════════════════════════════

// ── GET /api/surat/pemanggilan ─────────────────────────────────
if ($path === '/api/surat/pemanggilan' && $method === 'GET') {
    requireRole(['admin', 'bk']);
    $rows = $pdo->query(
        'SELECT spo.id, spo.nomor, spo.tanggal_surat, s.nis, s.nama, s.kelas
         FROM surat_pemanggilan_ortu spo
         JOIN siswa s ON s.id_siswa = spo.id_siswa
         ORDER BY spo.id DESC'
    )->fetchAll();
    jsonResponse(['ok' => true, 'data' => $rows]);
}

// ── POST /api/surat/pemanggilan ────────────────────────────────
if ($path === '/api/surat/pemanggilan' && $method === 'POST') {
    requireRole(['admin', 'bk']);
    $body = jsonBody();
    $idSiswa      = (int) ($body['id_siswa'] ?? 0);
    $tanggalSurat = (string) ($body['tanggal_surat'] ?? '');
    $hariTanggal  = trim((string) ($body['hari_tanggal'] ?? ''));
    $pukul        = trim((string) ($body['pukul'] ?? ''));
    $tempat       = trim((string) ($body['tempat'] ?? ''));
    $keperluan    = trim((string) ($body['keperluan'] ?? ''));

    if ($idSiswa <= 0 || $tanggalSurat === '') {
        jsonResponse(['ok' => false, 'error' => 'Data belum lengkap.'], 422);
    }

    $tahun = (int) date('Y', strtotime($tanggalSurat));
    if ($tahun <= 0) $tahun = (int) date('Y');
    $noUrut = nextSuratNumber($pdo, 'pemanggilan_ortu', $tahun);
    $nomor  = str_pad((string) $noUrut, 3, '0', STR_PAD_LEFT) . '/SPO/' . $tahun;

    $stmt = $pdo->prepare(
        'INSERT INTO surat_pemanggilan_ortu (id_siswa, nomor, tanggal_surat, hari_tanggal, pukul, tempat, keperluan, created_by_user_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $idSiswa, $nomor, $tanggalSurat,
        $hariTanggal === '' ? null : $hariTanggal,
        $pukul === '' ? null : $pukul,
        $tempat === '' ? null : $tempat,
        $keperluan === '' ? null : $keperluan,
        currentUserId(),
    ]);

    $idSuratBaru = (int) $pdo->lastInsertId();

    $resetResult = catatSuratTerbit($pdo, $idSiswa, 'pemanggilan_ortu', $idSuratBaru, $tanggalSurat);

    jsonResponse([
        'ok' => true,
        'message' => 'Surat pemanggilan berhasil dibuat.',
        'id' => $idSuratBaru,
        'nomor' => $nomor,
        'auto_reset' => $resetResult['reset'] ?? false,
        'reset_info' => $resetResult['reset'] ? [
            'from_level' => $resetResult['from'],
            'to_level' => $resetResult['to'],
            'message' => $resetResult['message']
        ] : null
    ]);
}

// ── GET /api/surat/pemanggilan/print ───────────────────────────
if ($path === '/api/surat/pemanggilan/print' && $method === 'GET') {
    requireRole(['admin', 'bk']);
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'ID tidak valid.'], 422);

    $stmt = $pdo->prepare(
        'SELECT spo.id, spo.nomor, spo.tanggal_surat, spo.hari_tanggal, spo.pukul, spo.tempat, spo.keperluan,
                s.nis, s.nama, s.kelas
         FROM surat_pemanggilan_ortu spo
         JOIN siswa s ON s.id_siswa = spo.id_siswa
         WHERE spo.id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) jsonResponse(['ok' => false, 'error' => 'Surat tidak ditemukan.'], 404);

    $pejabat = [];
    foreach ($pdo->query('SELECT jabatan, nama_pejabat, nip FROM pejabat_sekolah')->fetchAll() as $p) {
        $pejabat[$p['jabatan']] = $p;
    }

    jsonResponse(['ok' => true, 'surat' => $row, 'pejabat' => $pejabat]);
}


// ═══════════════════════════════════════════════════════════════
//  SURAT PERJANJIAN
// ═══════════════════════════════════════════════════════════════

// ── GET /api/surat/perjanjian ──────────────────────────────────
if ($path === '/api/surat/perjanjian' && $method === 'GET') {
    requireRole(['admin', 'bk']);
    $rows = $pdo->query(
        'SELECT sp.id_perjanjian, sp.tanggal_perjanjian, sp.isi_perjanjian, s.nis, s.nama, s.kelas
         FROM surat_perjanjian sp
         JOIN siswa s ON s.id_siswa = sp.id_siswa
         ORDER BY sp.id_perjanjian DESC'
    )->fetchAll();
    jsonResponse(['ok' => true, 'data' => $rows]);
}

// ── POST /api/surat/perjanjian ─────────────────────────────────
if ($path === '/api/surat/perjanjian' && $method === 'POST') {
    requireRole(['admin', 'bk']);
    $body = jsonBody();
    $idSiswa = (int) ($body['id_siswa'] ?? 0);
    $tanggal = (string) ($body['tanggal_perjanjian'] ?? '');
    $isi     = trim((string) ($body['isi_perjanjian'] ?? ''));
    $jenisSurat = (string) ($body['jenis_surat'] ?? 'perjanjian_siswa');

    if ($idSiswa <= 0 || $tanggal === '' || $isi === '') {
        jsonResponse(['ok' => false, 'error' => 'Data belum lengkap.'], 422);
    }

    $jenisRecord = ($jenisSurat === 'perjanjian_ortu') ? 'perjanjian_ortu' : 'perjanjian_siswa';
    $nomor = 'SP-' . date('Y') . '-' . nextSuratNumber($pdo, $jenisRecord, (int) date('Y'));

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO surat_perjanjian (id_siswa, tanggal_perjanjian, isi_perjanjian, nomor, ortu_nama, ortu_pekerjaan, ortu_alamat, ortu_hp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $idSiswa, $tanggal, $isi, $nomor,
            $body['ortu_nama'] ?? null,
            $body['ortu_pekerjaan'] ?? null,
            $body['ortu_alamat'] ?? null,
            $body['ortu_hp'] ?? null
        ]);
        $idSuratBaru = (int) $pdo->lastInsertId();

        $pdo->commit();

        $resetResult = catatSuratTerbit($pdo, $idSiswa, $jenisRecord, $idSuratBaru, $tanggal);

        jsonResponse([
            'ok' => true,
            'message' => 'Surat perjanjian berhasil dibuat.',
            'id' => $idSuratBaru,
            'nomor' => $nomor,
            'auto_reset' => $resetResult['reset'] ?? false,
            'reset_info' => $resetResult['reset'] ? [
                'from_level' => $resetResult['from'],
                'to_level' => $resetResult['to'],
                'message' => $resetResult['message']
            ] : ($resetResult['missing'] ?? null)
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        jsonResponse(['ok' => false, 'error' => 'Gagal: ' . $e->getMessage()], 500);
    }
}

// ── GET /api/surat/perjanjian/print ────────────────────────────
if ($path === '/api/surat/perjanjian/print' && $method === 'GET') {
    requireRole(['admin', 'bk']);
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'ID tidak valid.'], 422);

    $stmt = $pdo->prepare(
        'SELECT sp.id_perjanjian, sp.tanggal_perjanjian, sp.isi_perjanjian, sp.nomor, 
                sp.ortu_nama, sp.ortu_pekerjaan, sp.ortu_alamat, sp.ortu_hp,
                s.nis, s.nama, s.kelas, s.program_keahlian
         FROM surat_perjanjian sp
         JOIN siswa s ON s.id_siswa = sp.id_siswa
         WHERE sp.id_perjanjian = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) jsonResponse(['ok' => false, 'error' => 'Surat tidak ditemukan.'], 404);

    $pejabat = [];
    foreach ($pdo->query('SELECT jabatan, nama_pejabat, nip FROM pejabat_sekolah')->fetchAll() as $p) {
        $pejabat[$p['jabatan']] = $p;
    }

    jsonResponse(['ok' => true, 'surat' => $row, 'pejabat' => $pejabat]);
}


// ═══════════════════════════════════════════════════════════════
//  REKAPITULASI SURAT PERJANJIAN
// ═══════════════════════════════════════════════════════════════

// ── GET /api/surat/perjanjian/rekap ──────────────────────────────
if ($path === '/api/surat/perjanjian/rekap' && $method === 'GET') {
    requireRole(['admin', 'bk']);

    $tanggalMulai   = isset($_GET['tanggal_mulai']) ? trim((string) $_GET['tanggal_mulai']) : '';
    $tanggalSelesai = isset($_GET['tanggal_selesai']) ? trim((string) $_GET['tanggal_selesai']) : '';
    $kelas          = isset($_GET['kelas']) ? trim((string) $_GET['kelas']) : '';

    $where = [];
    $params = [];

    if ($tanggalMulai !== '') {
        $where[] = 'sp.tanggal_perjanjian >= ?';
        $params[] = $tanggalMulai;
    }
    if ($tanggalSelesai !== '') {
        $where[] = 'sp.tanggal_perjanjian <= ?';
        $params[] = $tanggalSelesai;
    }
    if ($kelas !== '' && $kelas !== 'all') {
        $where[] = 's.kelas = ?';
        $params[] = $kelas;
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT sp.id_perjanjian, sp.tanggal_perjanjian, sp.isi_perjanjian,
               s.id_siswa, s.nis, s.nama, s.kelas,
               stl.tahap, stl.tanggal_terbit
        FROM surat_perjanjian sp
        JOIN siswa s ON s.id_siswa = sp.id_siswa
        LEFT JOIN surat_terbit_log stl ON stl.id_surat_ref = sp.id_perjanjian
            AND stl.jenis_surat IN ('perjanjian_siswa', 'perjanjian_ortu')
        {$whereSQL}
        ORDER BY sp.tanggal_perjanjian DESC, sp.id_perjanjian DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    $countSQL = "SELECT COUNT(*) FROM surat_perjanjian sp JOIN siswa s ON s.id_siswa = sp.id_siswa {$whereSQL}";
    $stmtCount = $pdo->prepare($countSQL);
    $stmtCount->execute($params);
    $totalCount = (int) $stmtCount->fetchColumn();

    $byClassSQL = "
        SELECT s.kelas, COUNT(*) as jumlah
        FROM surat_perjanjian sp
        JOIN siswa s ON s.id_siswa = sp.id_siswa
        {$whereSQL}
        GROUP BY s.kelas
        ORDER BY s.kelas
    ";
    $stmtByClass = $pdo->prepare($byClassSQL);
    $stmtByClass->execute($params);
    $byClass = $stmtByClass->fetchAll(PDO::FETCH_KEY_PAIR);

    $byMonthSQL = "
        SELECT DATE_FORMAT(sp.tanggal_perjanjian, '%Y-%m') as bulan, COUNT(*) as jumlah
        FROM surat_perjanjian sp
        JOIN siswa s ON s.id_siswa = sp.id_siswa
        {$whereSQL}
        GROUP BY DATE_FORMAT(sp.tanggal_perjanjian, '%Y-%m')
        ORDER BY bulan DESC
    ";
    $stmtByMonth = $pdo->prepare($byMonthSQL);
    $stmtByMonth->execute($params);
    $byMonth = $stmtByMonth->fetchAll();

    $kelasList = $pdo->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas")->fetchAll(PDO::FETCH_COLUMN);

    jsonResponse([
        'ok' => true,
        'data' => $data,
        'summary' => [
            'total' => $totalCount,
            'by_class' => $byClass ?: [],
            'by_month' => $byMonth ?: []
        ],
        'filters' => [
            'kelas_list' => $kelasList ?: []
        ]
    ]);
}

// ── GET /api/siswa/surat ──────────────────────────────────────
if ($path === '/api/siswa/surat' && $method === 'GET') {
    requireRole(['admin', 'bk', 'siswa']);
    
    $idSiswa = (int) ($_GET['id'] ?? 0);
    $role = currentRole();
    
    if ($role === 'siswa') {
        $nis = $_SESSION['username'] ?? '';
        $stmtFind = $pdo->prepare('SELECT id_siswa FROM siswa WHERE nis = ? LIMIT 1');
        $stmtFind->execute([$nis]);
        $idSiswa = (int) ($stmtFind->fetchColumn() ?: 0);
    }
    
    if ($idSiswa <= 0) jsonResponse(['ok' => false, 'error' => 'ID siswa tidak valid.'], 422);

    $sql = "
        SELECT 'perjanjian' AS jenis, id_perjanjian AS id, nomor, tanggal_perjanjian AS tanggal, isi_perjanjian AS keterangan
        FROM surat_perjanjian
        WHERE id_siswa = ?
        UNION ALL
        SELECT 'pemanggilan' AS jenis, id, nomor, tanggal_surat AS tanggal, keperluan AS keterangan
        FROM surat_pemanggilan_ortu
        WHERE id_siswa = ?
        UNION ALL
        SELECT 'do' AS jenis, id, NULL AS nomor, tanggal_surat AS tanggal, alasan AS keterangan
        FROM surat_do
        WHERE id_siswa = ?
        UNION ALL
        SELECT 'pindah' AS jenis, id_surat_pindah AS id, NULL AS nomor, tanggal_pindah AS tanggal, alasan_pindah AS keterangan
        FROM surat_pindah
        WHERE id_siswa = ?
        ORDER BY tanggal DESC, id DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idSiswa, $idSiswa, $idSiswa, $idSiswa]);
    jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
}


// ═══════════════════════════════════════════════════════════════
//  PENGURANGAN POIN
// ═══════════════════════════════════════════════════════════════

// ── GET /api/surat/pengurangan ─────────────────────────────────
if ($path === '/api/surat/pengurangan' && $method === 'GET') {
    requireRole(['admin', 'bk']);
    $rows = $pdo->query(
        'SELECT sp.id_pengurangan, sp.tanggal, sp.jumlah_pengurangan, sp.keterangan, s.nis, s.nama
         FROM surat_pengurangan_poin sp
         JOIN siswa s ON s.id_siswa = sp.id_siswa
         ORDER BY sp.id_pengurangan DESC'
    )->fetchAll();
    jsonResponse(['ok' => true, 'data' => $rows]);
}

// ── POST /api/surat/pengurangan ────────────────────────────────
if ($path === '/api/surat/pengurangan' && $method === 'POST') {
    requireRole(['admin', 'bk']);
    $body = jsonBody();
    $idSiswa = (int) ($body['id_siswa'] ?? 0);
    $tanggal = (string) ($body['tanggal'] ?? '');
    $jumlah  = (int) ($body['jumlah_pengurangan'] ?? 0);
    $ket     = trim((string) ($body['keterangan'] ?? ''));

    if ($idSiswa <= 0 || $tanggal === '' || $jumlah <= 0) {
        jsonResponse(['ok' => false, 'error' => 'Data belum lengkap.'], 422);
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO surat_pengurangan_poin (id_siswa, tanggal, jumlah_pengurangan, keterangan) VALUES (?, ?, ?, ?)');
        $stmt->execute([$idSiswa, $tanggal, $jumlah, $ket === '' ? null : $ket]);
        $newId = (int) $pdo->lastInsertId();

        $pdo->prepare('INSERT INTO log_aktivitas (aksi, actor_user_id, actor_role, siswa_id, alasan, metadata) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute(['pengurangan_poin', currentUserId(), currentRole() ?? 'bk', $idSiswa, null,
                json_encode(['tanggal' => $tanggal, 'jumlah_pengurangan' => $jumlah, 'keterangan' => $ket, 'pengurangan_id' => $newId], JSON_UNESCAPED_UNICODE)]);

        $pdo->commit();
        jsonResponse(['ok' => true, 'message' => 'Pengurangan poin berhasil dicatat.']);
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResponse(['ok' => false, 'error' => 'Gagal menyimpan pengurangan poin.'], 500);
    }
}


// ═══════════════════════════════════════════════════════════════
//  SURAT DO
// ═══════════════════════════════════════════════════════════════

// ── GET /api/surat/do ──────────────────────────────────────────
if ($path === '/api/surat/do' && $method === 'GET') {
    requireRole(['admin', 'bk']);
    $rows = $pdo->query(
        'SELECT sd.id, sd.tanggal_surat, s.nis, s.nama, s.kelas
         FROM surat_do sd
         JOIN siswa s ON s.id_siswa = sd.id_siswa
         ORDER BY sd.id DESC'
    )->fetchAll();
    jsonResponse(['ok' => true, 'data' => $rows]);
}

// ── POST /api/surat/do ─────────────────────────────────────────
if ($path === '/api/surat/do' && $method === 'POST') {
    requireRole(['admin', 'bk']);
    $body = jsonBody();
    $idSiswa      = (int) ($body['id_siswa'] ?? 0);
    $tanggalSurat = (string) ($body['tanggal_surat'] ?? '');
    $namaPengaju  = trim((string) ($body['nama_pengaju'] ?? ''));
    $alamatPengaju= trim((string) ($body['alamat_pengaju'] ?? ''));
    $telpPengaju  = trim((string) ($body['telp_pengaju'] ?? ''));
    $alasan       = trim((string) ($body['alasan'] ?? ''));

    if ($idSiswa <= 0 || $tanggalSurat === '') {
        jsonResponse(['ok' => false, 'error' => 'Data belum lengkap.'], 422);
    }

    // Cek siswa harus level Merah
    $stmt = $pdo->prepare('SELECT nis, progress_level FROM siswa WHERE id_siswa = ? LIMIT 1');
    $stmt->execute([$idSiswa]);
    $siswa = $stmt->fetch();
    if (!$siswa) jsonResponse(['ok' => false, 'error' => 'Siswa tidak ditemukan.'], 404);
    if ((int) $siswa['progress_level'] !== 2) {
        jsonResponse(['ok' => false, 'error' => 'Siswa belum di tahap Merah.'], 422);
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO surat_do (id_siswa, tanggal_surat, nama_pengaju, alamat_pengaju, telp_pengaju, alasan, created_by_user_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $idSiswa, $tanggalSurat,
            $namaPengaju === '' ? null : $namaPengaju,
            $alamatPengaju === '' ? null : $alamatPengaju,
            $telpPengaju === '' ? null : $telpPengaju,
            $alasan === '' ? null : $alasan,
            currentUserId(),
        ]);

        $idSuratBaru = (int) $pdo->lastInsertId();

        $pdo->commit();

        $resetResult = catatSuratTerbit($pdo, $idSiswa, 'do', $idSuratBaru, $tanggalSurat);

        jsonResponse([
            'ok' => true,
            'message' => 'Surat DO berhasil diterbitkan. Siswa berstatus DO.',
            'id' => $idSuratBaru,
            'auto_reset' => $resetResult['reset'] ?? false,
            'reset_info' => $resetResult['reset'] ? [
                'from_level' => $resetResult['from'],
                'to_level' => $resetResult['to'],
                'message' => $resetResult['message']
            ] : null
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        jsonResponse(['ok' => false, 'error' => 'Gagal: ' . $e->getMessage()], 500);
    }
}

// ── GET /api/surat/do/print ────────────────────────────────────
if ($path === '/api/surat/do/print' && $method === 'GET') {
    requireRole(['admin', 'bk']);
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'ID tidak valid.'], 422);

    $stmt = $pdo->prepare(
        'SELECT sd.id, sd.tanggal_surat, sd.nama_pengaju, sd.alamat_pengaju, sd.telp_pengaju, sd.alasan,
                s.nis, s.nama, s.kelas, s.program_keahlian
         FROM surat_do sd
         JOIN siswa s ON s.id_siswa = sd.id_siswa
         WHERE sd.id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) jsonResponse(['ok' => false, 'error' => 'Surat tidak ditemukan.'], 404);

    $pejabat = [];
    foreach ($pdo->query('SELECT jabatan, nama_pejabat, nip FROM pejabat_sekolah')->fetchAll() as $p) {
        $pejabat[$p['jabatan']] = $p;
    }
    jsonResponse(['ok' => true, 'surat' => $row, 'pejabat' => $pejabat]);
}


// ═══════════════════════════════════════════════════════════════
//  SURAT PINDAH
// ═══════════════════════════════════════════════════════════════

// ── GET /api/surat/pindah ──────────────────────────────────────
if ($path === '/api/surat/pindah' && $method === 'GET') {
    requireRole(['admin', 'bk']);
    $rows = $pdo->query(
        'SELECT sp.id_surat_pindah, sp.tanggal_pindah, sp.alasan_pindah, s.nis, s.nama
         FROM surat_pindah sp
         JOIN siswa s ON s.id_siswa = sp.id_siswa
         ORDER BY sp.id_surat_pindah DESC'
    )->fetchAll();
    jsonResponse(['ok' => true, 'data' => $rows]);
}

// ── GET /api/surat/pindah/print ────────────────────────────────
if ($path === '/api/surat/pindah/print' && $method === 'GET') {
    requireRole(['admin', 'bk']);
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'ID tidak valid.'], 422);

    $stmt = $pdo->prepare(
        'SELECT sp.id_surat_pindah, sp.tanggal_pindah, sp.alasan_pindah, s.nis, s.nama, s.kelas, s.program_keahlian
         FROM surat_pindah sp
         JOIN siswa s ON s.id_siswa = sp.id_siswa
         WHERE sp.id_surat_pindah = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) jsonResponse(['ok' => false, 'error' => 'Surat tidak ditemukan.'], 404);

    $pejabat = [];
    foreach ($pdo->query('SELECT jabatan, nama_pejabat, nip FROM pejabat_sekolah')->fetchAll() as $p) {
        $pejabat[$p['jabatan']] = $p;
    }

    jsonResponse(['ok' => true, 'surat' => $row, 'pejabat' => $pejabat]);
}

// ── POST /api/surat/pindah ─────────────────────────────────────
if ($path === '/api/surat/pindah' && $method === 'POST') {
    requireRole(['admin', 'bk']);
    $body = jsonBody();
    $idSiswa = (int) ($body['id_siswa'] ?? 0);
    $tanggal = (string) ($body['tanggal_pindah'] ?? '');
    $alasan  = trim((string) ($body['alasan_pindah'] ?? ''));

    if ($idSiswa <= 0 || $tanggal === '' || $alasan === '') {
        jsonResponse(['ok' => false, 'error' => 'Data belum lengkap.'], 422);
    }

    $stmt = $pdo->prepare('SELECT nis, progress_level FROM siswa WHERE id_siswa = ? LIMIT 1');
    $stmt->execute([$idSiswa]);
    $siswa = $stmt->fetch();
    if (!$siswa) jsonResponse(['ok' => false, 'error' => 'Siswa tidak ditemukan.'], 404);

    $progressLevel = (int) ($siswa['progress_level'] ?? 0);
    if ($progressLevel !== 1 && $progressLevel !== 2) {
        jsonResponse(['ok' => false, 'error' => 'Siswa belum berada di tahap Kuning/Merah.'], 422);
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO surat_pindah (id_siswa, tanggal_pindah, alasan_pindah) VALUES (?, ?, ?)')
            ->execute([$idSiswa, $tanggal, $alasan]);

        $idSuratBaru = (int) $pdo->lastInsertId();

        $pdo->commit();

        $resetResult = catatSuratTerbit($pdo, $idSiswa, 'pindah', $idSuratBaru, $tanggal);

        jsonResponse([
            'ok' => true,
            'message' => 'Surat pindah berhasil diterbitkan.',
            'id' => $idSuratBaru,
            'auto_reset' => $resetResult['reset'] ?? false,
            'reset_info' => $resetResult['reset'] ? [
                'from_level' => $resetResult['from'],
                'to_level' => $resetResult['to'],
                'message' => $resetResult['message']
            ] : null
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        jsonResponse(['ok' => false, 'error' => 'Gagal: ' . $e->getMessage()], 500);
    }
}


// ═══════════════════════════════════════════════════════════════
//  NEXUS INTELLIGENCE - REKOMENDASI & AUTO RESET
// ═══════════════════════════════════════════════════════════════

// ── GET /api/surat/rekomendasi ───────────────────────────────────
if ($path === '/api/surat/rekomendasi' && $method === 'GET') {
    requireRole(['admin', 'bk']);
    $idSiswa = (int) ($_GET['id_siswa'] ?? 0);
    if ($idSiswa <= 0) jsonResponse(['ok' => false, 'error' => 'ID siswa tidak valid.'], 422);

    $result = getRekomendasiSurat($pdo, $idSiswa);
    jsonResponse($result);
}

// ── GET /api/surat/rekomendasi-list ─────────────────────────────
if ($path === '/api/surat/rekomendasi-list' && $method === 'GET') {
    requireRole(['admin', 'bk']);

    $poinSQL = poinSubQuery();
    $stmt = $pdo->prepare("
        SELECT s.id_siswa, s.nis, s.nama, s.kelas, s.progress_level,
               {$poinSQL} AS total_poin
        FROM siswa s
        JOIN users u ON u.username = s.nis AND u.role = 'siswa' AND u.status = 'aktif'
        WHERE s.progress_level < 3 AND ({$poinSQL}) >= 30
        ORDER BY total_poin DESC
    ");
    $stmt->execute();
    $siswaList = $stmt->fetchAll();

    $result = [];
    foreach ($siswaList as $s) {
        $rekom = getRekomendasiSurat($pdo, (int) $s['id_siswa']);
        $result[] = [
            'id_siswa' => $s['id_siswa'],
            'nis' => $s['nis'],
            'nama' => $s['nama'],
            'kelas' => $s['kelas'],
            'progress_level' => $s['progress_level'],
            'total_poin' => (int) $s['total_poin'],
            'rekomendasi' => $rekom['rekomendasi'] ?? [],
            'tahap' => $rekom['tahap'] ?? '',
            'semua_terbit' => empty(array_filter($rekom['rekomendasi'] ?? [], fn($r) => !$r['terbit']))
        ];
    }

    jsonResponse(['ok' => true, 'data' => $result]);
}


// ═══════════════════════════════════════════════════════════════
//  REKAPITULASI SURAT (ALL TYPES)
// ═══════════════════════════════════════════════════════════════

// ── GET /api/surat/rekap ─────────────────────────────────────────
if ($path === '/api/surat/rekap' && $method === 'GET') {
    requireRole(['admin', 'bk']);

    $tanggalMulai   = isset($_GET['tanggal_mulai']) ? trim((string) $_GET['tanggal_mulai']) : '';
    $tanggalSelesai = isset($_GET['tanggal_selesai']) ? trim((string) $_GET['tanggal_selesai']) : '';
    $kelas          = isset($_GET['kelas']) ? trim((string) $_GET['kelas']) : '';
    $jenisSurat     = isset($_GET['jenis_surat']) ? trim((string) $_GET['jenis_surat']) : '';

    /**
     * Helper: build WHERE clause + params array for a specific table.
     * @param string $dateCol  e.g. 'spo.tanggal_surat'
     * @param string $kelasCol e.g. 's.kelas'
     */
    $buildWhere = function (string $dateCol, string $kelasCol) use ($tanggalMulai, $tanggalSelesai, $kelas): array {
        $where  = [];
        $params = [];
        if ($tanggalMulai !== '')              { $where[] = "{$dateCol} >= ?";  $params[] = $tanggalMulai; }
        if ($tanggalSelesai !== '')             { $where[] = "{$dateCol} <= ?";  $params[] = $tanggalSelesai; }
        if ($kelas !== '' && $kelas !== 'all') { $where[] = "{$kelasCol} = ?"; $params[] = $kelas; }
        return [
            'sql'    => $where ? 'WHERE ' . implode(' AND ', $where) : '',
            'params' => $params,
        ];
    };

    // ── Count per type ────────────────────────────────────────────
    $counts = [];

    if ($jenisSurat === '' || $jenisSurat === 'all' || $jenisSurat === 'pemanggilan_ortu') {
        ['sql' => $wSQL, 'params' => $wParams] = $buildWhere('spo.tanggal_surat', 's.kelas');
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM surat_pemanggilan_ortu spo JOIN siswa s ON s.id_siswa = spo.id_siswa {$wSQL}");
        $stmt->execute($wParams);
        $counts['pemanggilan_ortu'] = (int) $stmt->fetchColumn();
    }

    if ($jenisSurat === '' || $jenisSurat === 'all' || in_array($jenisSurat, ['perjanjian_siswa', 'perjanjian_ortu'])) {
        ['sql' => $wSQL, 'params' => $wParams] = $buildWhere('sp.tanggal_perjanjian', 's.kelas');
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM surat_perjanjian sp JOIN siswa s ON s.id_siswa = sp.id_siswa {$wSQL}");
        $stmt->execute($wParams);
        $counts['perjanjian_siswa'] = (int) $stmt->fetchColumn();
    }

    if ($jenisSurat === '' || $jenisSurat === 'all' || $jenisSurat === 'do') {
        ['sql' => $wSQL, 'params' => $wParams] = $buildWhere('sd.tanggal_surat', 's.kelas');
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM surat_do sd JOIN siswa s ON s.id_siswa = sd.id_siswa {$wSQL}");
        $stmt->execute($wParams);
        $counts['do'] = (int) $stmt->fetchColumn();
    }

    if ($jenisSurat === '' || $jenisSurat === 'all' || $jenisSurat === 'pindah') {
        ['sql' => $wSQL, 'params' => $wParams] = $buildWhere('sp.tanggal_pindah', 's.kelas');
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM surat_pindah sp JOIN siswa s ON s.id_siswa = sp.id_siswa {$wSQL}");
        $stmt->execute($wParams);
        $counts['pindah'] = (int) $stmt->fetchColumn();
    }

    // ── Fetch main data ───────────────────────────────────────────
    $unionQueries = [];
    $allParams    = [];

    if ($jenisSurat === '' || $jenisSurat === 'all' || $jenisSurat === 'pemanggilan_ortu') {
        ['sql' => $wSQL, 'params' => $wParams] = $buildWhere('spo.tanggal_surat', 's.kelas');
        $unionQueries[] = "
            SELECT 'pemanggilan_ortu' AS jenis, spo.id AS id_surat,
                   spo.tanggal_surat AS tanggal, s.id_siswa, s.nis, s.nama, s.kelas,
                   spo.nomor, spo.keperluan AS detail, NULL AS isi_perjanjian, NULL AS alasan,
                   stl.tahap, stl.tanggal_terbit
            FROM surat_pemanggilan_ortu spo
            JOIN siswa s ON s.id_siswa = spo.id_siswa
            LEFT JOIN surat_terbit_log stl ON stl.id_surat_ref = spo.id AND stl.jenis_surat = 'pemanggilan_ortu'
            {$wSQL}
        ";
        foreach ($wParams as $p) $allParams[] = $p;
    }

    if ($jenisSurat === '' || $jenisSurat === 'all' || in_array($jenisSurat, ['perjanjian_siswa', 'perjanjian_ortu'])) {
        ['sql' => $wSQL, 'params' => $wParams] = $buildWhere('sp.tanggal_perjanjian', 's.kelas');
        $unionQueries[] = "
            SELECT 'perjanjian_siswa' AS jenis, sp.id_perjanjian AS id_surat,
                   sp.tanggal_perjanjian AS tanggal, s.id_siswa, s.nis, s.nama, s.kelas,
                   NULL AS nomor, sp.isi_perjanjian AS detail, NULL AS isi_perjanjian, NULL AS alasan,
                   stl.tahap, stl.tanggal_terbit
            FROM surat_perjanjian sp
            JOIN siswa s ON s.id_siswa = sp.id_siswa
            LEFT JOIN surat_terbit_log stl ON stl.id_surat_ref = sp.id_perjanjian
                AND stl.jenis_surat IN ('perjanjian_siswa', 'perjanjian_ortu')
            {$wSQL}
        ";
        foreach ($wParams as $p) $allParams[] = $p;
    }

    if ($jenisSurat === '' || $jenisSurat === 'all' || $jenisSurat === 'do') {
        ['sql' => $wSQL, 'params' => $wParams] = $buildWhere('sd.tanggal_surat', 's.kelas');
        $unionQueries[] = "
            SELECT 'do' AS jenis, sd.id AS id_surat,
                   sd.tanggal_surat AS tanggal, s.id_siswa, s.nis, s.nama, s.kelas,
                   NULL AS nomor, sd.alasan AS detail, NULL AS isi_perjanjian, NULL AS alasan,
                   stl.tahap, stl.tanggal_terbit
            FROM surat_do sd
            JOIN siswa s ON s.id_siswa = sd.id_siswa
            LEFT JOIN surat_terbit_log stl ON stl.id_surat_ref = sd.id AND stl.jenis_surat = 'do'
            {$wSQL}
        ";
        foreach ($wParams as $p) $allParams[] = $p;
    }

    if ($jenisSurat === '' || $jenisSurat === 'all' || $jenisSurat === 'pindah') {
        ['sql' => $wSQL, 'params' => $wParams] = $buildWhere('sp.tanggal_pindah', 's.kelas');
        $unionQueries[] = "
            SELECT 'pindah' AS jenis, sp.id_surat_pindah AS id_surat,
                   sp.tanggal_pindah AS tanggal, s.id_siswa, s.nis, s.nama, s.kelas,
                   NULL AS nomor, sp.alasan_pindah AS detail, NULL AS isi_perjanjian, NULL AS alasan,
                   stl.tahap, stl.tanggal_terbit
            FROM surat_pindah sp
            JOIN siswa s ON s.id_siswa = sp.id_siswa
            LEFT JOIN surat_terbit_log stl ON stl.id_surat_ref = sp.id_surat_pindah AND stl.jenis_surat = 'pindah'
            {$wSQL}
        ";
        foreach ($wParams as $p) $allParams[] = $p;
    }

    $data = [];
    if (!empty($unionQueries)) {
        $unionSQL = implode(' UNION ALL ', $unionQueries) . ' ORDER BY tanggal DESC, id_surat DESC';
        $stmt = $pdo->prepare($unionSQL);
        $stmt->execute($allParams);
        $data = $stmt->fetchAll();
    }

    // Summary stats
    $totalFiltered = count($data);

    // By class
    $byClass = [];
    foreach ($data as $row) {
        $k = $row['kelas'];
        if (!isset($byClass[$k])) $byClass[$k] = 0;
        $byClass[$k]++;
    }
    ksort($byClass);

    // By month
    $byMonth = [];
    foreach ($data as $row) {
        if ($row['tanggal']) {
            $bulan = date('Y-m', strtotime($row['tanggal']));
            if (!isset($byMonth[$bulan])) $byMonth[$bulan] = 0;
            $byMonth[$bulan]++;
        }
    }
    arsort($byMonth);

    // By jenis
    $byJenis = [];
    foreach ($data as $row) {
        $j = $row['jenis'];
        if (!isset($byJenis[$j])) $byJenis[$j] = 0;
        $byJenis[$j]++;
    }

    // Get unique classes
    $kelasList = $pdo->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas")->fetchAll(PDO::FETCH_COLUMN);

    jsonResponse([
        'ok' => true,
        'data' => $data,
        'summary' => [
            'total' => $totalFiltered,
            'by_class' => $byClass,
            'by_month' => array_map(fn($v, $k) => ['bulan' => $k, 'jumlah' => $v], $byMonth, array_keys($byMonth)),
            'by_jenis' => $byJenis,
            'counts_per_type' => $counts,
        ],
        'filters' => [
            'kelas_list' => $kelasList ?: []
        ]
    ]);
}


// ═══════════════════════════════════════════════════════════════
//  FALLBACK
// ═══════════════════════════════════════════════════════════════

jsonResponse(['ok' => false, 'error' => 'Endpoint tidak ditemukan.'], 404);
