<?php
/**
 * verify-email.php — Email verification handler.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);
use function App\{e, config, users_have_email_verified};
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/email_verification.php';

$token = trim((string)($_GET['token'] ?? ''));
$error = '';
$verified = false;
if ($token !== '' && users_have_email_verified()) {
    $found = \App\email_verification_find_valid($token);
    if ($found) {
        \App\email_verification_mark_used($found['id']);
        \App\email_verification_set_user_verified($found['user_id']);
        $verified = true;
        header('Location: /login?verified=1');
        exit;
    }
    $error = 'This verification link is invalid or has expired.';
} elseif ($token !== '') {
    $error = 'Verification is not available. Please contact support.';
}
$appName = e(config()['app_name'] ?? 'Hillwork');
$pageTitle = 'Verify email · ' . $appName;
$bodyClass = 'min-h-screen bg-zinc-50 text-zinc-900 antialiased';
require __DIR__ . '/inc/partials/head.php';
?>
<main class="mx-auto flex min-h-[70vh] max-w-md flex-col justify-center px-4 py-12 sm:px-5">
  <div class="card">
  <h1 class="mb-4 text-2xl font-semibold tracking-tight text-zinc-900">Verify email</h1>
  <?php if ($error): ?><div class="alert alert-error mb-4"><?= e($error) ?></div><?php endif; ?>
  <?php if (!$verified): ?>
    <p class="text-sm"><a href="/login" class="font-medium text-teal-700 hover:text-teal-800">Log in</a></p>
    <p class="mt-2 text-sm"><a href="/" class="font-medium text-teal-700 hover:text-teal-800">Home</a></p>
  <?php endif; ?>
  </div>
</main></body></html>
