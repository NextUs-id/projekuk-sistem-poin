<?php
/**
 * api/users.php
 * ═════════════
 * Fungsi: CRUD akun user (khusus Admin).
 * 
 * Endpoints:
 *   GET  /api/users                → Daftar semua user
 *   GET  /api/users/detail?id=X    → Detail satu user
 *   POST /api/users/create         → Buat user baru
 *   POST /api/users/update         → Edit user
 *   POST /api/users/delete         → Hapus user
 *   POST /api/users/reset-password → Reset password user
 *   POST /api/users/update-status  → Ubah status (aktif/nonaktif/DO)
 *   GET  /api/util/wali?kelas=X    → Cari guru wali untuk kelas tertentu
 *   GET  /api/util/kelas-options   → Daftar kelas yang ada
 */
declare(strict_types=1);

$pdo    = getDB();
$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path   = rtrim($path, '/') ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// ── GET /api/util/wali ─────────────────────────────────────────
if ($path === '/api/util/wali' && $method === 'GET') {
    requireRole(['admin']);

    $kelas = trim((string) ($_GET['kelas'] ?? ''));
    if ($kelas === '') {
        jsonResponse(['ok' => true, 'found' => false]);
    }

    $stmt = $pdo->prepare("SELECT id, nama_asli FROM users WHERE role = 'guru' AND status = 'aktif' AND kelas_wali = ? ORDER BY id ASC LIMIT 1");
    $stmt->execute([$kelas]);
    $row = $stmt->fetch();

    if (!is_array($row)) {
        jsonResponse(['ok' => true, 'found' => false]);
    }

    jsonResponse(['ok' => true, 'found' => true, 'id' => (int) $row['id'], 'nama' => (string) $row['nama_asli']]);
}

// ── GET /api/util/kelas-options ────────────────────────────────
if ($path === '/api/util/kelas-options' && $method === 'GET') {
    requireRole(['admin']);

    $stmt = $pdo->prepare("SELECT DISTINCT kelas_wali AS kelas FROM users WHERE role = 'guru' AND status = 'aktif' AND kelas_wali IS NOT NULL AND kelas_wali <> '' ORDER BY kelas_wali ASC");
    $stmt->execute();
    jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
}

// ── GET /api/users ─────────────────────────────────────────────
if ($path === '/api/users' && $method === 'GET') {
    requireRole(['admin']);

    $filterRole = trim((string) ($_GET['role'] ?? 'all'));
    $allowed    = ['all', 'admin', 'bk', 'guru', 'kepsek', 'siswa'];
    if (!in_array($filterRole, $allowed, true)) $filterRole = 'all';

    $sql    = 'SELECT id, username, nama_asli, role, status, created_at, last_login, last_activity FROM users';
    $params = [];
    if ($filterRole !== 'all') {
        $sql .= ' WHERE role = ?';
        $params[] = $filterRole;
    }
    $sql .= ' ORDER BY id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();

    $now    = time();
    $selfId = currentUserId();
    foreach ($users as &$u) {
        $la = $u['last_activity'] ? strtotime((string) $u['last_activity']) : null;
        // Gunakan abs() agar jika selisihnya negatif tetap dihitung jarak absolutnya
        $u['is_online'] = $la !== null && abs($now - $la) <= 300;
        $u['is_self']   = $selfId !== null && (int) $u['id'] === $selfId;
    }
    unset($u);

    jsonResponse(['ok' => true, 'data' => $users]);
}

// ── GET /api/users/detail ──────────────────────────────────────
if ($path === '/api/users/detail' && $method === 'GET') {
    requireRole(['admin']);

    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'ID tidak valid.'], 422);

    $stmt = $pdo->prepare('SELECT id, username, nama_asli, role, status, kelas_wali FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!is_array($user)) jsonResponse(['ok' => false, 'error' => 'User tidak ditemukan.'], 404);

    $siswa = null;
    if ((string) ($user['role'] ?? '') === 'siswa') {
        $stmtS = $pdo->prepare('SELECT id_siswa, nis, nama, kelas, alamat, nama_orang_tua, kontak_orang_tua, pekerjaan_orang_tua, wali_guru_id, progress_level, poin_reset_at FROM siswa WHERE nis = ? LIMIT 1');
        $stmtS->execute([(string) $user['username']]);
        $row = $stmtS->fetch();
        if (is_array($row)) {
            $siswa = $row;
            // Hitung total poin
            $idSiswa = (int) $row['id_siswa'];
            $hasReset = !empty($row['poin_reset_at']);
            $resetAt = $hasReset ? (string) $row['poin_reset_at'] : '1970-01-01 00:00:00';
            
            $stmtPoin = $pdo->prepare('
                SELECT
                    COALESCE((SELECT SUM(jp.poin) FROM pelanggaran p JOIN jenis_pelanggaran jp ON p.id_jenis = jp.id_jenis WHERE p.id_siswa = ? AND p.deleted_at IS NULL AND (? = 0 OR p.created_at > ?)), 0)
                    -
                    COALESCE((SELECT SUM(sp.jumlah_pengurangan) FROM surat_pengurangan_poin sp WHERE sp.id_siswa = ? AND (? = 0 OR sp.tanggal >= DATE(?))), 0) AS total_poin
            ');
            $stmtPoin->execute([
                $idSiswa, $hasReset ? 1 : 0, $resetAt,
                $idSiswa, $hasReset ? 1 : 0, $resetAt,
            ]);
            $totalPoin = (int) $stmtPoin->fetchColumn();
            $siswa['total_poin'] = max(0, $totalPoin);
        }
    }

    jsonResponse(['ok' => true, 'user' => $user, 'siswa' => $siswa]);
}

// ── POST /api/users/create ─────────────────────────────────────
if ($path === '/api/users/create' && $method === 'POST') {
    requireRole(['admin']);

    $body       = jsonBody();
    $username   = trim((string) ($body['username'] ?? ''));
    $namaAsli   = trim((string) ($body['nama_asli'] ?? ''));
    $password   = (string) ($body['password'] ?? '');
    $role       = (string) ($body['role'] ?? '');
    $status     = (string) ($body['status'] ?? 'aktif');
    $kelasWali  = trim((string) ($body['kelas_wali'] ?? ''));
    $kelasSiswa = trim((string) ($body['kelas'] ?? ''));
    $alamat     = trim((string) ($body['alamat'] ?? ''));
    $namaOrtu   = trim((string) ($body['nama_orang_tua'] ?? ''));
    $kontakOrtu = trim((string) ($body['kontak_orang_tua'] ?? ''));
    $pekerjaanOrtu = trim((string) ($body['pekerjaan_orang_tua'] ?? ''));

    $roles    = ['admin', 'bk', 'guru', 'kepsek', 'siswa'];
    $statuses = ['aktif', 'nonaktif', 'pindah', 'DO'];

    if ($username === '' || $namaAsli === '' || $password === '' || !in_array($role, $roles, true) || !in_array($status, $statuses, true)) {
        jsonResponse(['ok' => false, 'error' => 'Data belum lengkap atau tidak valid.'], 422);
    }

    if ($role !== 'guru') $kelasWali = '';

    $waliGuruId = 0;
    if ($role === 'siswa') {
        if ($kelasSiswa === '') jsonResponse(['ok' => false, 'error' => 'Kelas siswa wajib dipilih.'], 422);
        if ($kontakOrtu === '') jsonResponse(['ok' => false, 'error' => 'Kontak orang tua wajib diisi.'], 422);
        if (!ctype_digit($kontakOrtu)) jsonResponse(['ok' => false, 'error' => 'Kontak orang tua hanya boleh angka.'], 422);

        $stmtW = $pdo->prepare("SELECT id FROM users WHERE role = 'guru' AND status = 'aktif' AND kelas_wali = ? ORDER BY id ASC LIMIT 1");
        $stmtW->execute([$kelasSiswa]);
        $waliGuruId = (int) ($stmtW->fetchColumn() ?: 0);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO users (username, password, nama_asli, role, status, kelas_wali) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$username, $hash, $namaAsli, $role, $status, $kelasWali === '' ? null : $kelasWali]);
        $newId = (int) $pdo->lastInsertId();

        if ($role === 'siswa') {
            $stmtS = $pdo->prepare('INSERT INTO siswa (nama, nis, kelas, alamat, nama_orang_tua, kontak_orang_tua, pekerjaan_orang_tua, wali_guru_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmtS->execute([$namaAsli, $username, $kelasSiswa, $alamat === '' ? null : $alamat, $namaOrtu === '' ? null : $namaOrtu, $kontakOrtu === '' ? null : $kontakOrtu, $pekerjaanOrtu === '' ? null : $pekerjaanOrtu, $waliGuruId > 0 ? $waliGuruId : null]);
        }
        
        // Sync ke tabel kelass jika dia guru dan punya kelas_wali
        if ($role === 'guru' && $kelasWali !== '') {
            $parts = explode('-', $kelasWali); // e.g. "XI-RPL-1"
            if (count($parts) >= 3) {
                $t_tingkat = $parts[0];
                $t_kode_j = $parts[1];
                $t_nomor = (int) $parts[2];
                $stmtSync = $pdo->prepare("
                    UPDATE kelass k
                    JOIN jurusans j ON k.id_jurusan = j.id
                    SET k.id_wali_guru = ?
                    WHERE k.tingkat = ? AND j.kode = ? AND k.nomor = ?
                ");
                $stmtSync->execute([$newId, $t_tingkat, $t_kode_j, $t_nomor]);
            }
        }

        $pdo->commit();
        jsonResponse(['ok' => true, 'id' => $newId]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResponse(['ok' => false, 'error' => 'Gagal menyimpan user. Username mungkin sudah dipakai.'], 409);
    }
}

// ── POST /api/users/update ─────────────────────────────────────
if ($path === '/api/users/update' && $method === 'POST') {
    requireRole(['admin']);

    $body = jsonBody();
    $id   = (int) ($body['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'ID tidak valid.'], 422);

    $stmt = $pdo->prepare('SELECT id, username, nama_asli, role, status, kelas_wali FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!is_array($user)) jsonResponse(['ok' => false, 'error' => 'User tidak ditemukan.'], 404);

    $oldUsername = (string) ($user['username'] ?? '');
    $username    = trim((string) ($body['username'] ?? ''));
    $namaAsli    = trim((string) ($body['nama_asli'] ?? ''));
    $role        = (string) ($body['role'] ?? '');
    $status      = (string) ($body['status'] ?? '');
    $kelasWali   = trim((string) ($body['kelas_wali'] ?? ''));
    $kelasSiswa  = trim((string) ($body['kelas'] ?? ''));
    $alamat      = trim((string) ($body['alamat'] ?? ''));
    $namaOrtu    = trim((string) ($body['nama_orang_tua'] ?? ''));
    $kontakOrtu  = trim((string) ($body['kontak_orang_tua'] ?? ''));
    $pekerjaanOrtu = trim((string) ($body['pekerjaan_orang_tua'] ?? ''));

    $roles    = ['admin', 'bk', 'guru', 'kepsek', 'siswa'];
    $statuses = ['aktif', 'nonaktif', 'pindah', 'DO'];

    if ($username === '' || $namaAsli === '' || !in_array($role, $roles, true) || !in_array($status, $statuses, true)) {
        jsonResponse(['ok' => false, 'error' => 'Role atau status tidak valid.'], 422);
    }

    // Cek duplikat username
    $cek = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id <> ?');
    $cek->execute([$username, $id]);
    if ((int) $cek->fetchColumn() > 0) {
        jsonResponse(['ok' => false, 'error' => 'Username sudah dipakai user lain.'], 409);
    }

    if ($role !== 'guru') $kelasWali = '';

    $waliGuruId = 0;
    if ($role === 'siswa') {
        if ($kelasSiswa === '') jsonResponse(['ok' => false, 'error' => 'Kelas siswa wajib diisi.'], 422);
        if ($kontakOrtu === '') jsonResponse(['ok' => false, 'error' => 'Kontak orang tua wajib diisi.'], 422);
        if (!ctype_digit($kontakOrtu)) jsonResponse(['ok' => false, 'error' => 'Kontak orang tua hanya boleh angka.'], 422);

        $cekNis = $pdo->prepare('SELECT COUNT(*) FROM siswa WHERE nis = ? AND nis <> ?');
        $cekNis->execute([$username, $oldUsername]);
        if ((int) $cekNis->fetchColumn() > 0) {
            jsonResponse(['ok' => false, 'error' => 'NIS sudah dipakai siswa lain.'], 409);
        }

        $stmtW = $pdo->prepare("SELECT id FROM users WHERE role = 'guru' AND status = 'aktif' AND kelas_wali = ? ORDER BY id ASC LIMIT 1");
        $stmtW->execute([$kelasSiswa]);
        $waliGuruId = (int) ($stmtW->fetchColumn() ?: 0);
    }

        $pdo->beginTransaction();
    try {
        $upd = $pdo->prepare('UPDATE users SET username = ?, nama_asli = ?, role = ?, status = ?, kelas_wali = ? WHERE id = ?');
        $upd->execute([$username, $namaAsli, $role, $status, $kelasWali === '' ? null : $kelasWali, $id]);

        if ($role === 'siswa') {
            $stmtFind = $pdo->prepare('SELECT id_siswa FROM siswa WHERE nis = ? LIMIT 1');
            $stmtFind->execute([$oldUsername]);
            $rowS    = $stmtFind->fetch();
            $idSiswa = is_array($rowS) ? (int) ($rowS['id_siswa'] ?? 0) : 0;

            if ($idSiswa > 0) {
                $updS = $pdo->prepare('UPDATE siswa SET nama = ?, nis = ?, kelas = ?, alamat = ?, nama_orang_tua = ?, kontak_orang_tua = ?, pekerjaan_orang_tua = ?, wali_guru_id = ? WHERE id_siswa = ?');
                $updS->execute([$namaAsli, $username, $kelasSiswa, $alamat === '' ? null : $alamat, $namaOrtu === '' ? null : $namaOrtu, $kontakOrtu === '' ? null : $kontakOrtu, $pekerjaanOrtu === '' ? null : $pekerjaanOrtu, $waliGuruId > 0 ? $waliGuruId : null, $idSiswa]);
            } else {
                $insS = $pdo->prepare('INSERT INTO siswa (nama, nis, kelas, alamat, nama_orang_tua, kontak_orang_tua, pekerjaan_orang_tua, wali_guru_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $insS->execute([$namaAsli, $username, $kelasSiswa, $alamat === '' ? null : $alamat, $namaOrtu === '' ? null : $namaOrtu, $kontakOrtu === '' ? null : $kontakOrtu, $pekerjaanOrtu === '' ? null : $pekerjaanOrtu, $waliGuruId > 0 ? $waliGuruId : null]);
            }
        }
        
        // Sync ke tabel kelass jika dia guru
        $stmtClear = $pdo->prepare("UPDATE kelass SET id_wali_guru = NULL WHERE id_wali_guru = ?");
        $stmtClear->execute([$id]);

        if ($role === 'guru' && $kelasWali !== '') {
            $parts = explode('-', $kelasWali); // e.g. "XI-RPL-1"
            if (count($parts) >= 3) {
                $t_tingkat = $parts[0];
                $t_kode_j = $parts[1];
                $t_nomor = (int) $parts[2];
                $stmtSync = $pdo->prepare("
                    UPDATE kelass k
                    JOIN jurusans j ON k.id_jurusan = j.id
                    SET k.id_wali_guru = ?
                    WHERE k.tingkat = ? AND j.kode = ? AND k.nomor = ?
                ");
                $stmtSync->execute([$id, $t_tingkat, $t_kode_j, $t_nomor]);
            }
        }

        $pdo->commit();
        jsonResponse(['ok' => true]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResponse(['ok' => false, 'error' => 'Gagal menyimpan perubahan.'], 500);
    }
}

// ── POST /api/users/delete ─────────────────────────────────────
if ($path === '/api/users/delete' && $method === 'POST') {
    requireRole(['admin']);

    $body = jsonBody();
    $id   = (int) ($body['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'ID tidak valid.'], 422);

    $selfId = currentUserId();
    if ($selfId !== null && $id === $selfId) {
        jsonResponse(['ok' => false, 'error' => 'Tidak bisa menghapus akun sendiri.'], 403);
    }

    $stmtUser = $pdo->prepare('SELECT id, username, role FROM users WHERE id = ? LIMIT 1');
    $stmtUser->execute([$id]);
    $user = $stmtUser->fetch();
    if (!is_array($user)) jsonResponse(['ok' => false, 'error' => 'User tidak ditemukan.'], 404);

    $pdo->beginTransaction();
    try {
        if ((string) ($user['role'] ?? '') === 'siswa') {
            $nis = (string) ($user['username'] ?? '');
            if ($nis !== '') {
                $delS = $pdo->prepare('DELETE FROM siswa WHERE nis = ?');
                $delS->execute([$nis]);
            }
        }
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $pdo->commit();
        jsonResponse(['ok' => true]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResponse(['ok' => false, 'error' => 'Gagal menghapus user.'], 500);
    }
}

// ── POST /api/users/reset-password ─────────────────────────────
if ($path === '/api/users/reset-password' && $method === 'POST') {
    requireRole(['admin']);

    $body     = jsonBody();
    $id       = (int) ($body['id'] ?? $_GET['id'] ?? 0);
    $password = (string) ($body['password'] ?? '');

    if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'ID tidak valid.'], 422);
    if ($password === '') jsonResponse(['ok' => false, 'error' => 'Password baru wajib diisi.'], 422);

    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) jsonResponse(['ok' => false, 'error' => 'User tidak ditemukan.'], 404);

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $upd  = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
    $upd->execute([$hash, $id]);
    jsonResponse(['ok' => true]);
}

// ── POST /api/users/update-status ──────────────────────────────
if ($path === '/api/users/update-status' && $method === 'POST') {
    requireRole(['admin']);

    $body   = jsonBody();
    $id     = (int) ($body['id'] ?? $_GET['id'] ?? 0);
    $status = trim((string) ($body['status'] ?? ''));

    $allowedStatuses = ['aktif', 'nonaktif', 'pindah', 'DO'];
    if ($status === 'do') $status = 'DO';

    if ($id <= 0 || !in_array($status, $allowedStatuses, true)) {
        jsonResponse(['ok' => false, 'error' => 'Status tidak valid.'], 422);
    }

    $selfId = currentUserId();
    if ($selfId !== null && $id === $selfId && $status !== 'aktif') {
        jsonResponse(['ok' => false, 'error' => 'Tidak bisa menonaktifkan akun sendiri.'], 403);
    }

    $stmt = $pdo->prepare('SELECT id, username, nama_asli, status FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!is_array($user)) jsonResponse(['ok' => false, 'error' => 'User tidak ditemukan.'], 404);

    $from = (string) ($user['status'] ?? '');
    $upd  = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
    $upd->execute([$status, $id]);

    jsonResponse(['ok' => true, 'from' => $from, 'to' => $status]);
}

// ── Fallback ───────────────────────────────────────────────────
jsonResponse(['ok' => false, 'error' => 'Endpoint tidak ditemukan.'], 404);
