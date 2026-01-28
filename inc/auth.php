<?php
declare(strict_types=1);
namespace App;

use App\Totp;

require_once __DIR__ . '/totp.php';
require_once __DIR__ . '/password_reset.php';

function session_boot(): void {
    static $booted = false;
    if ($booted) return;
    $cfg = config();
    $params = session_get_cookie_params();
    session_name($cfg['session_name']);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => $params['path'] ?? '/',
        'domain'   => $params['domain'] ?? '',
        'secure'   => (bool)$cfg['cookie_secure'],
        'httponly' => true,
        'samesite' => $cfg['cookie_samesite'] ?? 'Lax',
    ]);
    // Use project-local session path (avoids 500 on IONOS/shared hosts where default path isn't writable)
    $sessDir = __DIR__ . '/../storage/sessions';
    if (!is_dir($sessDir)) {
        @mkdir($sessDir, 0700, true);
    }
    if (is_dir($sessDir) && is_writable($sessDir)) {
        session_save_path($sessDir);
    }
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $booted = true;
}

/** @return 'ok'|'mfa'|'fail' */
function login(string $email, string $password): string {
    session_boot();
    $stmt = pdo()->prepare("SELECT id,email,username,display_name,password_hash,role,mfa_enabled,user_session_version FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if (!$u || !password_verify($password, $u['password_hash'])) return 'fail';
    if ((int)$u['mfa_enabled'] === 1) {
        $_SESSION['pending_mfa_user_id'] = (int)$u['id'];
        return 'mfa';
    }
    finish_login((int)$u['id'], $u['email'], $u['username'], $u['role'], $u['display_name'], (int)($u['user_session_version'] ?? 0));
    return 'ok';
}

/** Set session and regenerate ID after successful auth (password or passkey). */
function finish_login(int $id, string $email, string $username, string $role, string $name, int $sessionVersion = 0): void {
    session_boot();
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    $_SESSION['user'] = [
        'id'       => $id,
        'email'    => $email,
        'username' => $username,
        'role'     => $role,
        'name'     => $name,
        'session_version' => $sessionVersion,
    ];
    pdo()->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?")->execute([$id]);
}

function verify_totp_and_finish(int $userId, string $code): bool {
    session_boot();
    $stmt = pdo()->prepare("SELECT id,email,username,display_name,role,mfa_secret,user_session_version FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $u = $stmt->fetch();
    if (!$u || empty($u['mfa_secret'])) return false;
    if (!Totp\verify($u['mfa_secret'], $code, 1)) return false;
    finish_login((int)$u['id'], $u['email'], $u['username'], $u['role'], $u['display_name'], (int)($u['user_session_version'] ?? 0));
    unset($_SESSION['pending_mfa_user_id']);
    return true;
}

function current_user(): ?array {
    session_boot();
    return $_SESSION['user'] ?? null;
}

function require_user(): array {
    session_boot();
    if (empty($_SESSION['user'])) {
        header('Location: /admin/login.php');
        exit;
    }
    $u = $_SESSION['user'];
    $dbVersion = get_user_session_version((int)$u['id']);
    $sessionVersion = (int)($u['session_version'] ?? 0);
    if ($dbVersion > $sessionVersion) {
        logout();
        header('Location: /admin/login.php?expired=1');
        exit;
    }
    return $u;
}

function require_admin(): array {
    $u = require_user();
    if (($u['role'] ?? 'user') !== 'admin') {
        http_response_code(403);
        echo "Forbidden";
        exit;
    }
    return $u;
}

function logout(): void {
    session_boot();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
          $params["path"], $params["domain"],
          $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
