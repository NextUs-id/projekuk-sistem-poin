<?php
/**
 * api/admin/kelas.php
 * ═══════════════════════════════════════════════════════════════
 * API untuk Kelola Kelas & Jurusan
 * 
 * Endpoints:
 *   GET    /api/admin/jurusans         → List semua jurusan
 *   POST   /api/admin/jurusans         → Tambah jurusan
 *   PUT    /api/admin/jurusans         → Edit jurusan
 *   DELETE /api/admin/jurusans?id=X     → Hapus jurusan
 *   
 *   GET    /api/admin/kelass           → List kelas (optional: ?tingkat=XI)
 *   POST   /api/admin/kelass           → Tambah kelas
 *   PUT    /api/admin/kelass           → Edit kelas
 *   DELETE /api/admin/kelass?id=X       → Hapus kelas
 *   
 *   GET    /api/admin/guru-wali        → List guru yang bisa jadi wali
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Pastikan session aktif
ensureSession();

// ═══════════════════════════════════════════════════════════════
//  JURUSAN ENDPOINTS
// ═══════════════════════════════════════════════════════════════

// GET /api/admin/jurusans
if ($path === '/api/admin/jurusans' && $method === 'GET') {
    requireRole(['admin']);
    
    $stmt = $pdo->query("SELECT * FROM jurusans ORDER BY kode ASC");
    $jurusans = $stmt->fetchAll();
    
    jsonResponse(['ok' => true, 'data' => $jurusans]);
}

// POST /api/admin/jurusans (Create)
if ($path === '/api/admin/jurusans' && $method === 'POST') {
    requireRole(['admin']);
    
    $body = jsonBody();
    $kode = strtoupper(trim((string) ($body['kode'] ?? '')));
    $nama = trim((string) ($body['nama'] ?? ''));
    
    if ($kode === '' || $nama === '') {
        jsonResponse(['ok' => false, 'error' => 'Kode dan Nama wajib diisi.'], 422);
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO jurusans (kode, nama) VALUES (?, ?)");
        $stmt->execute([$kode, $nama]);
        $id = (int) $pdo->lastInsertId();
        
        jsonResponse(['ok' => true, 'message' => 'Jurusan berhasil ditambahkan.', 'id' => $id]);
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate')) {
            jsonResponse(['ok' => false, 'error' => 'KodeJurusan sudah ada.'], 422);
        }
        jsonResponse(['ok' => false, 'error' => 'Gagal: ' . $e->getMessage()], 500);
    }
}

// PUT /api/admin/jurusans (Update)
if ($path === '/api/admin/jurusans' && $method === 'PUT') {
    requireRole(['admin']);
    
    $body = jsonBody();
    $id = (int) ($body['id'] ?? 0);
    $kode = strtoupper(trim((string) ($body['kode'] ?? '')));
    $nama = trim((string) ($body['nama'] ?? ''));
    
    if ($id <= 0 || $kode === '' || $nama === '') {
        jsonResponse(['ok' => false, 'error' => 'Data tidak lengkap.'], 422);
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE jurusans SET kode = ?, nama = ? WHERE id = ?");
        $stmt->execute([$kode, $nama, $id]);
        
        jsonResponse(['ok' => true, 'message' => 'Jurusan berhasil diperbarui.']);
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate')) {
            jsonResponse(['ok' => false, 'error' => 'KodeJurusan sudah ada.'], 422);
        }
        jsonResponse(['ok' => false, 'error' => 'Gagal: ' . $e->getMessage()], 500);
    }
}

// DELETE /api/admin/jurusans
if ($path === '/api/admin/jurusans' && $method === 'DELETE') {
    requireRole(['admin']);
    
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['ok' => false, 'error' => 'ID tidak valid.'], 422);
    }
    
    try {
        // Cek apakah masih ada kelas
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM kelass WHERE id_jurusan = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            jsonResponse(['ok' => false, 'error' => 'Tidak bisa hapus. Masih ada kelas denganJurusan ini.'], 422);
        }
        
        $stmt = $pdo->prepare("DELETE FROM jurusans WHERE id = ?");
        $stmt->execute([$id]);
        
        jsonResponse(['ok' => true, 'message' => 'Jurusan berhasil dihapus.']);
    } catch (PDOException $e) {
        jsonResponse(['ok' => false, 'error' => 'Gagal: ' . $e->getMessage()], 500);
    }
}

// ═══════════════════════════════════════════════════════════════
//  KELAS ENDPOINTS
// ═══════════════════════════════════════════════════════════════

// GET /api/admin/kelass
if ($path === '/api/admin/kelass' && $method === 'GET') {
    requireRole(['admin']);
    
    $tingkat = $_GET['tingkat'] ?? '';
    
    $sql = "
        SELECT k.id, k.tingkat, k.nomor, k.id_jurusan, k.id_wali_guru,
               j.kode AS kode_jurusan, j.nama AS nama_jurusans,
               CONCAT(k.tingkat, '-', j.kode, '-', k.nomor) AS nama_kelas,
               u.nama_asli AS nama_wali
        FROM kelass k
        JOIN jurusans j ON j.id = k.id_jurusan
        LEFT JOIN users u ON u.id = k.id_wali_guru
    ";
    
    if ($tingkat !== '') {
        $sql .= " WHERE k.tingkat = " . $pdo->quote($tingkat);
    }
    
    $sql .= " ORDER BY k.tingkat ASC, j.kode ASC, k.nomor ASC";
    
    $stmt = $pdo->query($sql);
    $kelass = $stmt->fetchAll();
    
    // Group by tingkat
    $grouped = ['X' => [], 'XI' => [], 'XII' => []];
    foreach ($kelass as $k) {
        $grouped[$k['tingkat']][] = $k;
    }
    
    jsonResponse(['ok' => true, 'data' => $kelass, 'grouped' => $grouped]);
}

// POST /api/admin/kelass (Create)
if ($path === '/api/admin/kelass' && $method === 'POST') {
    requireRole(['admin']);
    
    $body = jsonBody();
    $tingkat = strtoupper(trim((string) ($body['tingkat'] ?? '')));
    $idJurusan = (int) ($body['id_jurusan'] ?? 0);
    $nomor = (int) ($body['nomor'] ?? 1);
    $idWali = (int) ($body['id_wali_guru'] ?? 0);
    
    if (!in_array($tingkat, ['X', 'XI', 'XII']) || $idJurusan <= 0) {
        jsonResponse(['ok' => false, 'error' => 'Tingkat dan Jurusan wajib dipilih.'], 422);
    }
    
    if ($idWali > 0) {
        $cekWali = $pdo->prepare("SELECT id FROM kelass WHERE id_wali_guru = ?");
        $cekWali->execute([$idWali]);
        if ($cekWali->fetch()) {
            jsonResponse(['ok' => false, 'error' => 'Guru tersebut sudah menjadi wali di kelas lain.'], 422);
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO kelass (tingkat, id_jurusan, nomor, id_wali_guru) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$tingkat, $idJurusan, $nomor, $idWali > 0 ? $idWali : null]);
        $id = (int) $pdo->lastInsertId();
        
        // Sync ke users jika set wali
        if ($idWali > 0) {
            $stmtJur = $pdo->prepare("SELECT kode FROM jurusans WHERE id = ?");
            $stmtJur->execute([$idJurusan]);
            $kodeJ = $stmtJur->fetchColumn();
            $namaKelasStr = "{$tingkat}-{$kodeJ}-{$nomor}";
            
            $stmtUpd = $pdo->prepare("UPDATE users SET kelas_wali = ? WHERE id = ?");
            $stmtUpd->execute([$namaKelasStr, $idWali]);
        }
        
        $pdo->commit();
        jsonResponse(['ok' => true, 'message' => 'Kelas berhasil ditambahkan.', 'id' => $id]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        if (str_contains($e->getMessage(), 'Duplicate')) {
            jsonResponse(['ok' => false, 'error' => 'Kelas sudah ada (Tingkat-Jurusan-Nomor sama).'], 422);
        }
        jsonResponse(['ok' => false, 'error' => 'Gagal: ' . $e->getMessage()], 500);
    }
}

// PUT /api/admin/kelass (Update)
if ($path === '/api/admin/kelass' && $method === 'PUT') {
    requireRole(['admin']);
    
    $body = jsonBody();
    $id = (int) ($body['id'] ?? 0);
    $tingkat = strtoupper(trim((string) ($body['tingkat'] ?? '')));
    $idJurusan = (int) ($body['id_jurusan'] ?? 0);
    $nomor = (int) ($body['nomor'] ?? 1);
    $idWali = (int) ($body['id_wali_guru'] ?? 0);
    
    if ($id <= 0 || !in_array($tingkat, ['X', 'XI', 'XII']) || $idJurusan <= 0) {
        jsonResponse(['ok' => false, 'error' => 'Data tidak lengkap.'], 422);
    }
    
    if ($idWali > 0) {
        $cekWali = $pdo->prepare("SELECT id FROM kelass WHERE id_wali_guru = ? AND id != ?");
        $cekWali->execute([$idWali, $id]);
        if ($cekWali->fetch()) {
            jsonResponse(['ok' => false, 'error' => 'Guru tersebut sudah menjadi wali di kelas lain.'], 422);
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        // Cek wali lama untuk di-reset
        $stmtOld = $pdo->prepare("SELECT id_wali_guru FROM kelass WHERE id = ?");
        $stmtOld->execute([$id]);
        $oldWali = (int) $stmtOld->fetchColumn();
        
        $stmt = $pdo->prepare("
            UPDATE kelass 
            SET tingkat = ?, id_jurusan = ?, nomor = ?, id_wali_guru = ? 
            WHERE id = ?
        ");
        $stmt->execute([$tingkat, $idJurusan, $nomor, $idWali > 0 ? $idWali : null, $id]);
        
        // Reset users.kelas_wali lama jika berubah
        if ($oldWali > 0 && $oldWali !== $idWali) {
            $stmtReset = $pdo->prepare("UPDATE users SET kelas_wali = NULL WHERE id = ?");
            $stmtReset->execute([$oldWali]);
        }
        
        // Sync users.kelas_wali baru
        if ($idWali > 0) {
            $stmtJur = $pdo->prepare("SELECT kode FROM jurusans WHERE id = ?");
            $stmtJur->execute([$idJurusan]);
            $kodeJ = $stmtJur->fetchColumn();
            $namaKelasStr = "{$tingkat}-{$kodeJ}-{$nomor}";
            
            $stmtUpd = $pdo->prepare("UPDATE users SET kelas_wali = ? WHERE id = ?");
            $stmtUpd->execute([$namaKelasStr, $idWali]);
        }
        
        $pdo->commit();
        jsonResponse(['ok' => true, 'message' => 'Kelas berhasil diperbarui.']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        if (str_contains($e->getMessage(), 'Duplicate')) {
            jsonResponse(['ok' => false, 'error' => 'Kelas sudah ada (Tingkat-Jurusan-Nomor sama).'], 422);
        }
        jsonResponse(['ok' => false, 'error' => 'Gagal: ' . $e->getMessage()], 500);
    }
}

// DELETE /api/admin/kelass
if ($path === '/api/admin/kelass' && $method === 'DELETE') {
    requireRole(['admin']);
    
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['ok' => false, 'error' => 'ID tidak valid.'], 422);
    }
    
    try {
        $pdo->beginTransaction();
        
        // Cek wali lama untuk di-reset
        $stmtOld = $pdo->prepare("SELECT id_wali_guru FROM kelass WHERE id = ?");
        $stmtOld->execute([$id]);
        $oldWali = (int) $stmtOld->fetchColumn();
        
        if ($oldWali > 0) {
            $stmtReset = $pdo->prepare("UPDATE users SET kelas_wali = NULL WHERE id = ?");
            $stmtReset->execute([$oldWali]);
        }
        
        $stmt = $pdo->prepare("DELETE FROM kelass WHERE id = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        jsonResponse(['ok' => true, 'message' => 'Kelas berhasil dihapus.']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        jsonResponse(['ok' => false, 'error' => 'Gagal: ' . $e->getMessage()], 500);
    }
}

// ═══════════════════════════════════════════════════════════════
//  GURU WALI ENDPOINT
// ═══════════════════════════════════════════════════════════════

// GET /api/admin/guru-wali
if ($path === '/api/admin/guru-wali' && $method === 'GET') {
    requireRole(['admin']);
    
    $stmt = $pdo->query("
        SELECT id, username, nama_asli, role
        FROM users 
        WHERE role = 'guru' AND status = 'aktif' 
        ORDER BY nama_asli ASC
    ");
    $gurus = $stmt->fetchAll();
    
    jsonResponse(['ok' => true, 'data' => $gurus]);
}

// ═══════════════════════════════════════════════════════════════
//  FALLBACK
// ═══════════════════════════════════════════════════════════════

jsonResponse(['ok' => false, 'error' => 'Endpoint tidak ditemukan.'], 404);
