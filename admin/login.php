<?php
declare(strict_types=1);
use function App\{config, e, webauthn_available};
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/auth.php';
require __DIR__ . '/../inc/csrf.php';
require __DIR__ . '/../inc/helpers.php';
require __DIR__ . '/../inc/webauthn.php';
\App\session_boot();
$passkeys_available = webauthn_available();

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
  <form method="post" id="login-form">
    <?= \App\csrf_field() ?>
    <label>Email<br><input type="email" name="email" id="login-email" required></label>
    <label>Password<br><input type="password" name="password" required></label>
    <button type="submit">Continue</button>
  </form>
  <p style="margin-top:16px;"><a href="/password/forgot.php">Forgot password?</a></p>
  <?php if ($passkeys_available): ?>
  <p style="margin-top:12px;">
    <button type="button" id="passkey-btn" style="display:none;">Sign in with a passkey</button>
  </p>
  <?php endif; ?>
  <meta name="csrf-token" content="<?= e(\App\csrf_token()) ?>">
  <script src="/assets/js/webauthn.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof window.WebAuthnHelper !== 'undefined' && window.WebAuthnHelper.supported && <?= $passkeys_available ? 'true' : 'false' ?>) {
        var btn = document.getElementById('passkey-btn');
        if (btn) { btn.style.display = 'inline-block'; window.WebAuthnHelper.initLoginPage(btn, document.getElementById('login-email')); }
      }
    });
  </script>
  <?php else: ?>
  <form method="post">
    <?= \App\csrf_field() ?>
    <p>Enter your 6‑digit authentication code.</p>
    <label>Authenticator code<br><input type="text" name="totp_code" inputmode="numeric" pattern="[0-9]{6}" required></label>
    <button type="submit">Verify</button>
  </form>
  <?php endif; ?>
</main></body></html>
