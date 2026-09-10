<?php
/**
 * api/log.php
 * ═══════════
 * Fungsi: Menampilkan log aktivitas sistem (audit trail).
 * 
 * Endpoint:
 *   GET /api/log → 200 log terakhir (siapa melakukan apa, kapan)
 */
declare(strict_types=1);

$pdo = getDB();
requireRole(['admin', 'bk', 'guru', 'kepsek']);

try {
    $stmt = $pdo->prepare('
        SELECT l.id, l.aksi, l.alasan, l.created_at, l.actor_role, l.metadata,
               u.nama_asli AS actor_nama,
               s.nis AS siswa_nis, s.nama AS siswa_nama
        FROM log_aktivitas l
        LEFT JOIN users u ON l.actor_user_id = u.id
        LEFT JOIN siswa s ON l.siswa_id = s.id_siswa
        ORDER BY l.created_at DESC, l.id DESC
        LIMIT 200
    ');
    $stmt->execute();
    jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Tabel log_aktivitas belum tersedia.'], 500);
}
