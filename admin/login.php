<?php
declare(strict_types=1);
use function App\{config, e};
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/auth.php';
require __DIR__ . '/../inc/csrf.php';
require __DIR__ . '/../inc/helpers.php';
\App\session_boot();

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    \App\csrf_verify();
    if (isset($_POST['email'], $_POST['password'])) {
        $res = \App\login($_POST['email'], $_POST['password']);
        if ($res === 'ok') {
            header('Location: /admin/');
            exit;
        } elseif ($res === 'mfa') {
            // fall through to TOTP form
        } else {
            $err = 'Invalid credentials.';
            sleep(1);
        }
    } elseif (isset($_POST['totp_code'])) {
        $uid = $_SESSION['pending_mfa_user_id'] ?? 0;
        if ($uid && \App\verify_totp_and_finish((int)$uid, $_POST['totp_code'])) {
            header('Location: /admin/');
            exit;
        } else {
            $err = 'Invalid code.';
            sleep(1);
        }
    }
}
$pending = isset($_SESSION['pending_mfa_user_id']);
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Login · <?= e(config()['app_name']) ?></title><link rel="stylesheet" href="/assets/css/styles.css"></head>
<body class="theme-light"><main class="container narrow">
  <h1>Sign in</h1>
  <?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>
  <?php if (!$pending): ?>
  <form method="post">
    <?= \App\csrf_field() ?>
    <label>Email<br><input type="email" name="email" required></label>
    <label>Password<br><input type="password" name="password" required></label>
    <button type="submit">Continue</button>
  </form>
  <?php else: ?>
  <form method="post">
    <?= \App\csrf_field() ?>
    <p>Enter your 6‑digit authentication code.</p>
    <label>Authenticator code<br><input type="text" name="totp_code" inputmode="numeric" pattern="[0-9]{6}" required></label>
    <button type="submit">Verify</button>
  </form>
  <?php endif; ?>
</main></body></html>
