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
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Verify email · <?= $appName ?></title><link rel="stylesheet" href="/assets/css/styles.css"></head>
<body class="theme-light"><main class="container">
  <h1>Verify email</h1>
  <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
  <?php if (!$verified): ?>
    <p><a href="/login">Log in</a></p>
    <p><a href="/">Home</a></p>
  <?php endif; ?>
</main></body></html>
