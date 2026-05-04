<?php
/**
 * reset.php — Password reset handler.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
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
$pageTitle = 'Set new password · ' . e(config()['app_name']);
$bodyClass = 'min-h-screen bg-zinc-50 text-zinc-900 antialiased';
require __DIR__ . '/../inc/partials/head.php';
?>
<main class="mx-auto flex min-h-[70vh] max-w-md flex-col justify-center px-4 py-12 sm:px-5">
  <div class="card">
  <h1 class="mb-4 text-2xl font-semibold tracking-tight text-zinc-900">Set new password</h1>
  <?php if ($err && !$showForm): ?>
    <div class="alert alert-error mb-4"><?= e($err) ?></div>
    <p class="text-sm text-zinc-600"><a href="/password/forgot.php" class="font-medium text-teal-700 hover:text-teal-800">Request a new link</a> or <a href="/admin/login.php" class="font-medium text-teal-700 hover:text-teal-800">Sign in</a>.</p>
  <?php elseif ($showForm): ?>
    <?php if ($err): ?><div class="alert alert-error mb-4"><?= e($err) ?></div><?php endif; ?>
    <form method="post" class="form-stack">
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <label>New password (min 8 characters)<br><input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
      <label>Confirm password<br><input type="password" name="password_confirm" required minlength="8" autocomplete="new-password"></label>
      <button type="submit" class="btn-primary w-full sm:w-auto">Update password</button>
    </form>
  <?php endif; ?>
  </div>
</main></body></html>
