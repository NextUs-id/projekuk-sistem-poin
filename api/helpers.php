<?php
/**
 * api/helpers.php
 * ═══════════════
 * Fungsi: Kumpulan fungsi bantu yang dipakai oleh SEMUA file API.
 * 
 * Isi:
 *   - jsonResponse()  → Kirim response JSON
 *   - jsonBody()      → Ambil body JSON dari POST request
 *   - requireAuth()   → Pastikan user sudah login
 *   - requireRole()   → Pastikan role user sesuai
 *   - currentUserId() → Ambil ID user yang sedang login
 *   - currentRole()   → Ambil role user yang sedang login
 */
declare(strict_types=1);

// Set timezone sesuai lokasi Andrew (Makassar/Bali/WITA - +08:00)
date_default_timezone_set('Asia/Makassar');

// ── Session ────────────────────────────────────────────────────

function ensureSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

// ── Response ───────────────────────────────────────────────────

/**
 * Kirim response JSON lalu hentikan script.
 * 
 * @param array $data   Data yang dikirim (harus punya key 'ok')
 * @param int   $status HTTP status code (200, 400, 401, dll)
 */
function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Request Body ───────────────────────────────────────────────

/**
 * Ambil body JSON dari POST/PUT request.
 * Fallback ke $_POST jika content-type bukan JSON.
 */
function jsonBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return $_POST;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $_POST;
}

// ── Auth Helpers ───────────────────────────────────────────────

/**
 * Ambil user_id dari session. null jika belum login.
 */
function currentUserId(): ?int
{
    ensureSession();
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/**
 * Ambil role dari session. null jika belum login.
 */
function currentRole(): ?string
{
    ensureSession();
    return isset($_SESSION['role']) ? strtolower((string) $_SESSION['role']) : null;
}

/**
 * Pastikan user sudah login.
 * Jika belum login → langsung response 401 dan berhenti.
 * Jika sudah → return user_id.
 */
function requireAuth(): int
{
    $userId = currentUserId();
    if ($userId === null) {
        jsonResponse(['ok' => false, 'error' => 'Sesi login tidak valid.'], 401);
    }
    return $userId;
}

/**
 * Pastikan role user termasuk dalam daftar yang diizinkan.
 * Jika tidak sesuai → langsung response 403 dan berhenti.
 * 
 * Contoh: requireRole(['admin', 'bk'])
 * 
 * @param string[] $allowed Daftar role yang boleh akses
 */
function requireRole(array $allowed): void
{
    requireAuth();
    $role = currentRole() ?? '';
    if (!in_array($role, $allowed, true)) {
        jsonResponse(['ok' => false, 'error' => 'Akses ditolak.'], 403);
    }
}

/**
 * Update last_activity user (untuk deteksi online).
 */
function touchActivity(PDO $pdo): void
{
    $userId = currentUserId();
    if ($userId === null) {
        return;
    }

    // Cek apakah akun masih aktif
    $check = $pdo->prepare('SELECT status, role FROM users WHERE id = ? LIMIT 1');
    $check->execute([$userId]);
    $row = $check->fetch();

    if (is_array($row)) {
        $status = (string) ($row['status'] ?? '');
        $role   = strtolower((string) ($row['role'] ?? ''));
        if ($status !== 'aktif' && !($role === 'siswa' && $status === 'DO')) {
            // Akun sudah tidak aktif → paksa logout
            ensureSession();
            $_SESSION = [];
            session_destroy();
            jsonResponse(['ok' => false, 'error' => 'Akun tidak aktif.'], 401);
        }
    }

    $stmt = $pdo->prepare('UPDATE users SET last_activity = NOW() WHERE id = ?');
    $stmt->execute([$userId]);
}
