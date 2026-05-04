<?php
/**
 * login.php — Admin login.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);
use function App\{config, e, pdo, base_url, send_mail, webauthn_available, users_have_email_verified, rate_limit_check, rate_limit_identifier_with_email};
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/auth.php';
require __DIR__ . '/../inc/csrf.php';
require __DIR__ . '/../inc/helpers.php';
require __DIR__ . '/../inc/mail.php';
require __DIR__ . '/../inc/rate_limit.php';
require __DIR__ . '/../inc/webauthn.php';
require_once __DIR__ . '/../inc/email_verification.php';
\App\session_boot();
$passkeys_available = webauthn_available();

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    \App\csrf_verify();
    if (isset($_POST['email'], $_POST['password'])) {
        $res = \App\login($_POST['email'], $_POST['password']);
        if ($res === 'ok') {
            $u = \App\current_user();
            header('Location: ' . (($u['role'] ?? '') === 'admin' ? '/admin/' : '/admin/profile.php'));
            exit;
        } elseif ($res === 'mfa') {
            // fall through to TOTP form
        } elseif ($res === 'unverified') {
            $email = trim((string) $_POST['email']);
            if (users_have_email_verified() && $email !== '') {
                $stmt = pdo()->prepare("SELECT id FROM users WHERE email = ? AND email_verified_at IS NULL");
                $stmt->execute([$email]);
                $row = $stmt->fetch();
                if ($row && rate_limit_check('verification_resend', rate_limit_identifier_with_email($email), 3, 3600)) {
                    $tokenForLink = \App\email_verification_create((int) $row['id'], 60);
                    $origin = rtrim(base_url(), '/');
                    $verifyUrl = $origin . '/verify-email.php?token=' . $tokenForLink;
                    $appName = config()['app_name'] ?? 'Hillwork';
                    $bodyText = "Verify your email to activate your {$appName} account. Click the link below (valid 1 hour):\n\n" . $verifyUrl . "\n\nIf you did not request this, ignore this email.";
                    $bodyHtml = '<p>Verify your email to activate your ' . e($appName) . ' account. Click the link below (valid 1 hour):</p><p><a href="' . e($verifyUrl) . '">Verify email</a></p><p>If you did not request this, ignore this email.</p>';
                    send_mail($email, 'Verify your email - ' . $appName, $bodyText, $bodyHtml);
                    $err = 'Please verify your email before signing in. We\'ve sent a new verification link to your email address.';
                } else {
                    $err = 'Please verify your email before signing in. Check your inbox for the verification link. You can request another link in about an hour.';
                }
            } else {
                $err = 'Please verify your email before signing in. Check your inbox for the verification link.';
            }
        } else {
            $err = 'Invalid credentials.';
            sleep(1);
        }
    } elseif (isset($_POST['totp_code'])) {
        $uid = $_SESSION['pending_mfa_user_id'] ?? 0;
        if ($uid && \App\verify_totp_and_finish((int)$uid, $_POST['totp_code'])) {
            $u = \App\current_user();
            header('Location: ' . (($u['role'] ?? '') === 'admin' ? '/admin/' : '/admin/profile.php'));
            exit;
        } else {
            $err = 'Invalid code.';
            sleep(1);
        }
    }
}
$pending = isset($_SESSION['pending_mfa_user_id']);
$verified = isset($_GET['verified']) && $_GET['verified'] === '1';
$pageTitle = 'Login · ' . e(config()['app_name']);
$bodyClass = 'min-h-screen bg-zinc-50 text-zinc-900 antialiased';
require __DIR__ . '/../inc/partials/head.php';
?>
<main class="mx-auto flex min-h-[80vh] max-w-md flex-col justify-center px-4 py-12 sm:px-5">
  <div class="card">
  <h1 class="mb-6 text-2xl font-semibold tracking-tight text-zinc-900">Sign in</h1>
  <?php if ($verified): ?><div class="alert mb-4">Your email is verified. You can sign in below.</div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-error mb-4"><?= e($err) ?></div><?php endif; ?>
  <?php if (!$pending): ?>
  <form method="post" id="login-form" class="form-stack">
    <?= \App\csrf_field() ?>
    <label>Email<br><input type="email" name="email" id="login-email" required></label>
    <label>Password<br><input type="password" name="password" required></label>
    <button type="submit" class="btn-primary w-full sm:w-auto">Continue</button>
  </form>
  <p class="mt-4 text-sm"><a href="/password/forgot.php" class="font-medium text-teal-700 hover:text-teal-800">Forgot password?</a></p>
  <?php if ($passkeys_available): ?>
  <p class="mt-3">
    <button type="button" id="passkey-btn" class="btn-secondary" style="display:none">Sign in with a passkey</button>
  </p>
  <?php endif; ?>
  <meta name="csrf-token" content="<?= e(\App\csrf_token()) ?>">
  <meta name="app-base-path" content="<?= e(rtrim(parse_url(\App\base_url(), PHP_URL_PATH) ?: '', '/')) ?>">
  <script src="/assets/js/webauthn.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof window.WebAuthnHelper !== 'undefined' && window.WebAuthnHelper.supported && <?= $passkeys_available ? 'true' : 'false' ?>) {
        var btn = document.getElementById('passkey-btn');
        if (btn) { btn.style.display = 'inline-block'; window.WebAuthnHelper.initLoginPage(btn, document.getElementById('login-email')); if (window.location.search.indexOf('method=passkey') !== -1) btn.focus(); }
      }
    });
  </script>
  <?php else: ?>
  <form method="post" class="form-stack">
    <?= \App\csrf_field() ?>
    <p class="text-sm text-zinc-600">Enter your 6‑digit authentication code.</p>
    <label>Authenticator code<br><input type="text" name="totp_code" inputmode="numeric" pattern="[0-9]{6}" required></label>
    <button type="submit" class="btn-primary">Verify</button>
  </form>
  <?php endif; ?>
  <p class="mt-6 text-sm"><a href="/" class="font-medium text-teal-700 hover:text-teal-800">Home</a></p>
  </div>
</main></body></html>
