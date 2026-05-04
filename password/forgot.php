<?php
/**
 * forgot.php — Forgot password form.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);
use function App\{config, e, pdo, base_url, send_mail, rate_limit_check, rate_limit_identifier_with_email, password_reset_create, csrf_token, csrf_field, csrf_verify};
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/auth.php';
require __DIR__ . '/../inc/helpers.php';
require __DIR__ . '/../inc/csrf.php';
require __DIR__ . '/../inc/mail.php';
require __DIR__ . '/../inc/rate_limit.php';
require_once __DIR__ . '/../inc/password_reset.php';
\App\session_boot();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim((string)($_POST['email'] ?? ''));
    if ($email === '') {
        $msg = 'Please enter your email address.';
    } else {
        $identifier = rate_limit_identifier_with_email($email);
        if (!rate_limit_check('password_reset_request', $identifier, 5, 3600)) {
            $msg = 'Too many requests. Please try again later.';
        } else {
            $stmt = pdo()->prepare("SELECT id, email FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $u = $stmt->fetch();
            if ($u) {
                $tokenForLink = password_reset_create((int)$u['id'], 30);
                $origin = base_url();
                $resetUrl = $origin . '/password/reset.php?token=' . $tokenForLink;
                $appName = config()['app_name'] ?? 'LinkHub';
                $bodyText = "You requested a password reset. Click the link below (valid 30 minutes):\n\n" . $resetUrl . "\n\nIf you did not request this, ignore this email.";
                $bodyHtml = '<p>You requested a password reset. Click the link below (valid 30 minutes):</p><p><a href="' . e($resetUrl) . '">Reset password</a></p><p>If you did not request this, ignore this email.</p>';
                $sent = send_mail($u['email'], 'Password reset - ' . $appName, $bodyText, $bodyHtml);
                if (!$sent && (config()['dev_mode'] ?? false)) {
                    error_log('[LinkHub password reset] ' . $resetUrl);
                }
            }
            header('Location: /password/reset_sent.php');
            exit;
        }
    }
}
$success = false;
$pageTitle = 'Forgot password · ' . e(config()['app_name']);
$bodyClass = 'min-h-screen bg-zinc-50 text-zinc-900 antialiased';
require __DIR__ . '/../inc/partials/head.php';
?>
<main class="mx-auto flex min-h-[70vh] max-w-md flex-col justify-center px-4 py-12 sm:px-5">
  <div class="card">
  <h1 class="mb-4 text-2xl font-semibold tracking-tight text-zinc-900">Forgot password</h1>
  <?php if ($msg): ?><div class="alert alert-error mb-4"><?= e($msg) ?></div><?php endif; ?>
  <form method="post" class="form-stack">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <label>Email<br><input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
    <button type="submit" class="btn-primary w-full sm:w-auto">Send reset link</button>
  </form>
  <p class="mt-6 text-sm"><a href="/admin/login.php" class="font-medium text-teal-700 hover:text-teal-800">Back to sign in</a></p>
  </div>
</main></body></html>
