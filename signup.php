<?php
/**
 * signup.php — User registration.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);
use function App\{config, e, pdo, base_url, send_mail, rate_limit_check, rate_limit_identifier, csrf_token, csrf_field, csrf_verify, slugify_username, users_have_email_verified, users_have_webauthn_handle};
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/helpers.php';
require __DIR__ . '/inc/csrf.php';
require __DIR__ . '/inc/mail.php';
require __DIR__ . '/inc/rate_limit.php';
require_once __DIR__ . '/inc/email_verification.php';
\App\session_boot();

$msg = '';
$success = false;
$verificationSent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (!rate_limit_check('signup', rate_limit_identifier(), 10, 3600)) {
        $msg = 'Too many signup attempts. Please try again later.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        $username = slugify_username((string)($_POST['username'] ?? ''));
        $displayName = trim((string)($_POST['display_name'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirm'] ?? '');
        if ($email === '' || $username === '' || $displayName === '' || strlen($password) < 8) {
            $msg = 'Please fill all fields. Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $msg = 'Passwords do not match.';
        } else {
            try {
                $stmt = pdo()->prepare("SELECT 1 FROM users WHERE email = ? OR username = ?");
                $stmt->execute([$email, $username]);
                if ($stmt->fetch()) {
                    $msg = 'An account with that email or username already exists.';
                } else {
                    $cfg = config();
                    $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
                    $opts = $algo === PASSWORD_BCRYPT ? ['cost' => (int)($cfg['password_cost'] ?? 12)] : [];
                    $hash = password_hash($password, $algo, $opts);
                    $cols = "email, username, display_name, password_hash, role";
                    $placeholders = "?, ?, ?, ?, 'user'";
                    $args = [$email, $username, $displayName, $hash];
                    if (users_have_webauthn_handle()) {
                        $cols .= ", webauthn_user_handle";
                        $placeholders .= ", ?";
                        $args[] = random_bytes(32);
                    }
                    pdo()->prepare("INSERT INTO users ({$cols}) VALUES ({$placeholders})")->execute($args);
                    $userId = (int) pdo()->lastInsertId();
                    $appName = $cfg['app_name'] ?? 'Hillwork';
                    if (users_have_email_verified()) {
                        $tokenForLink = \App\email_verification_create($userId, 60);
                        $origin = rtrim(base_url(), '/');
                        $verifyUrl = $origin . '/verify-email.php?token=' . $tokenForLink;
                        $bodyText = "Verify your email to activate your {$appName} account. Click the link below (valid 1 hour):\n\n" . $verifyUrl . "\n\nIf you did not create an account, ignore this email.";
                        $bodyHtml = '<p>Verify your email to activate your ' . e($appName) . ' account. Click the link below (valid 1 hour):</p><p><a href="' . e($verifyUrl) . '">Verify email</a></p><p>If you did not create an account, ignore this email.</p>';
                        send_mail($email, 'Verify your email - ' . $appName, $bodyText, $bodyHtml);
                        $verificationSent = true;
                    }
                    $success = true;
                }
            } catch (\Throwable $e) {
                $msg = 'Something went wrong. Please try again later.';
                if (config()['dev_mode'] ?? false) {
                    $msg .= ' ' . e($e->getMessage());
                }
            }
        }
    }
}
$csrf = csrf_token();
$appName = e(config()['app_name'] ?? 'Hillwork');
$pageTitle = 'Create account · ' . $appName;
$bodyClass = 'min-h-screen bg-zinc-50 text-zinc-900 antialiased';
require __DIR__ . '/inc/partials/head.php';
?>
<main class="mx-auto flex min-h-[80vh] max-w-md flex-col justify-center px-4 py-12 sm:px-5">
  <div class="card">
  <h1 class="mb-6 text-2xl font-semibold tracking-tight text-zinc-900">Create free account</h1>
  <?php if ($success): ?>
    <div class="alert">
      <?php if ($verificationSent): ?>
        <p class="font-semibold">Check your email to verify your account.</p>
        <p class="mt-2 text-sm">We sent a verification link to <?= e($_POST['email'] ?? '') ?>. Click the link to activate your account, then sign in.</p>
      <?php else: ?>
        <p class="font-semibold">Account created.</p>
        <p class="mt-2 text-sm">You can now <a href="/login" class="font-medium text-teal-800 underline">log in</a>.</p>
      <?php endif; ?>
    </div>
    <p class="mt-4 text-sm"><a href="/login" class="font-medium text-teal-700 hover:text-teal-800">Log in</a></p>
  <?php else: ?>
    <?php if ($msg): ?><div class="alert alert-error mb-4"><?= e($msg) ?></div><?php endif; ?>
    <form method="post" class="form-stack">
      <?= csrf_field() ?>
      <label>Email<br><input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
      <label>Username (for your page URL)<br><input type="text" name="username" required minlength="3" maxlength="32" pattern="[a-z0-9_]+" title="Letters, numbers, underscores only" value="<?= e($_POST['username'] ?? '') ?>"></label>
      <label>Display name<br><input type="text" name="display_name" required maxlength="80" value="<?= e($_POST['display_name'] ?? '') ?>"></label>
      <label>Password (min 8 characters)<br><input type="password" name="password" required minlength="8"></label>
      <label>Confirm password<br><input type="password" name="password_confirm" required minlength="8"></label>
      <button type="submit" class="btn-primary w-full sm:w-auto">Create account</button>
    </form>
    <p class="mt-4 text-sm"><a href="/login" class="font-medium text-teal-700 hover:text-teal-800">Already have an account? Log in</a></p>
  <?php endif; ?>
  <p class="mt-6 text-sm"><a href="/" class="font-medium text-teal-700 hover:text-teal-800">Home</a></p>
  </div>
</main></body></html>
