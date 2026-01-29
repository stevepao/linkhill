<?php
declare(strict_types=1);
use function App\{config, e, pdo, csrf_verify, csrf_field, password_reset_find_valid, password_reset_mark_used, bump_user_session_version, rate_limit_check, rate_limit_identifier};
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/auth.php';
require __DIR__ . '/../inc/helpers.php';
require __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/password_reset.php';
require __DIR__ . '/../inc/rate_limit.php';
\App\session_boot();

$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
$resetData = $token !== '' ? password_reset_find_valid($token) : null;
$err = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $resetData !== null) {
    \App\csrf_verify();
    if (!rate_limit_check('password_reset_confirm', rate_limit_identifier(), 10, 3600)) {
        $err = 'Too many attempts. Try again later.';
    } else {
        $newPassword = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirm'] ?? '');
        if (strlen($newPassword) < 8) {
            $err = 'Password must be at least 8 characters.';
        } elseif ($newPassword !== $confirm) {
            $err = 'Passwords do not match.';
        } else {
            $cfg = config();
            $algo = PASSWORD_ARGON2ID;
            if (!defined('PASSWORD_ARGON2ID') || !password_get_info(password_hash('x', PASSWORD_ARGON2ID))['algo']) {
                $algo = PASSWORD_BCRYPT;
            }
            $cost = (int)($cfg['password_cost'] ?? 12);
            $opts = $algo === PASSWORD_BCRYPT ? ['cost' => $cost] : [];
            $hash = password_hash($newPassword, $algo, $opts);
            $pdo = pdo();
            $pdo->prepare("UPDATE users SET password_hash = ?, password_updated_at = NOW() WHERE id = ?")->execute([$hash, $resetData['user_id']]);
            bump_user_session_version($resetData['user_id']);
            password_reset_mark_used($resetData['id']);
            $done = true;
            header('Location: /password/reset_complete.php');
            exit;
        }
    }
}

if ($token !== '' && $resetData === null && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $err = 'This reset link is invalid or has expired.';
}
$showForm = $resetData !== null && !$done;
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Set new password · <?= e(config()['app_name']) ?></title><link rel="stylesheet" href="/assets/css/styles.css"></head>
<body class="theme-light"><main class="container">
  <h1>Set new password</h1>
  <?php if ($err && !$showForm): ?>
    <div class="alert alert-error"><?= e($err) ?></div>
    <p><a href="/password/forgot.php">Request a new link</a> or <a href="/admin/login.php">Sign in</a>.</p>
  <?php elseif ($showForm): ?>
    <?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <label>New password (min 8 characters)<br><input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
      <label>Confirm password<br><input type="password" name="password_confirm" required minlength="8" autocomplete="new-password"></label>
      <button type="submit">Update password</button>
    </form>
  <?php endif; ?>
</main></body></html>
