<?php
/**
 * api/auth.php
 * ════════════
 * Fungsi: Menangani login, logout, dan cek status login.
 * 
 * Endpoints:
 *   POST /api/login   → Login dengan username + password
 *   POST /api/logout  → Logout (hapus session)
 *   GET  /api/me      → Cek siapa yang sedang login
 */
declare(strict_types=1);

$pdo    = getDB();
$path   = $GLOBALS['path']   ?? ($_SERVER['REQUEST_URI'] ?? '');
$path   = parse_url($path, PHP_URL_PATH) ?? '/';
$path   = rtrim($path, '/') ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// ── GET /api/me ────────────────────────────────────────────────
if ($path === '/api/me' && $method === 'GET') {
    $userId = currentUserId();

    if ($userId === null) {
        jsonResponse(['ok' => true, 'authenticated' => false]);
    }

    jsonResponse([
        'ok'            => true,
        'authenticated' => true,
        'user'          => [
            'id'        => $userId,
            'username'  => (string) ($_SESSION['username'] ?? ''),
            'nama_asli' => (string) ($_SESSION['nama_asli'] ?? ''),
            'role'      => (string) (currentRole() ?? ''),
        ],
    ]);
}

// ── POST /api/login ────────────────────────────────────────────
if ($path === '/api/login' && $method === 'POST') {
    $body     = jsonBody();
    $username = trim((string) ($body['username'] ?? ''));
    $password = (string) ($body['password'] ?? '');

    if ($username === '' || $password === '') {
        jsonResponse(['ok' => false, 'error' => 'Username dan password wajib diisi!'], 400);
    }

    // Cari user di database
    $stmt = $pdo->prepare('SELECT id, username, password, nama_asli, role, status FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!is_array($user)) {
        jsonResponse(['ok' => false, 'error' => 'Username atau password salah.'], 401);
    }

    // Cek status akun
    $status = (string) ($user['status'] ?? '');
    if ($status === 'DO') {
        jsonResponse(['ok' => false, 'error' => 'Akun ini sudah berstatus DO (Drop Out). Tidak dapat login.'], 403);
    }
    if ($status !== 'aktif') {
        jsonResponse(['ok' => false, 'error' => 'Akun dinonaktifkan admin, silahkan hubungi admin.'], 403);
    }

    // Verifikasi password
    if (!password_verify($password, (string) $user['password'])) {
        jsonResponse(['ok' => false, 'error' => 'Username atau password salah.'], 401);
    }

    // Buat session
    ensureSession();
    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['username']  = (string) $user['username'];
    $_SESSION['nama_asli'] = (string) $user['nama_asli'];
    $_SESSION['role']      = strtolower((string) $user['role']);

    // Update last_login
    $stmt = $pdo->prepare('UPDATE users SET last_login = NOW(), last_activity = NOW() WHERE id = ?');
    $stmt->execute([(int) $user['id']]);

    jsonResponse(['ok' => true]);
}

// ── POST /api/logout ───────────────────────────────────────────
if ($path === '/api/logout' && $method === 'POST') {
    ensureSession();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            (bool) ($params['secure'] ?? false),
            (bool) ($params['httponly'] ?? false)
        );
    }

    session_destroy();
    jsonResponse(['ok' => true]);
}

// ── Fallback ───────────────────────────────────────────────────
jsonResponse(['ok' => false, 'error' => 'Endpoint tidak ditemukan.'], 404);
