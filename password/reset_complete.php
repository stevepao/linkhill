<?php
/**
 * reset_complete.php — Password reset complete.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);
use function App\{config, e};
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/helpers.php';
$pageTitle = 'Password updated · ' . e(config()['app_name']);
$bodyClass = 'min-h-screen bg-zinc-50 text-zinc-900 antialiased';
require __DIR__ . '/../inc/partials/head.php';
?>
<main class="mx-auto flex min-h-[70vh] max-w-md flex-col justify-center px-4 py-12 sm:px-5">
  <div class="card">
  <h1 class="mb-4 text-2xl font-semibold tracking-tight text-zinc-900">Password updated</h1>
  <div class="alert"><p class="text-sm">Your password has been changed. Other sessions have been signed out. You can now sign in with your new password.</p><p class="mt-3 text-sm"><a href="/admin/login.php" class="font-medium text-teal-800 underline">Sign in</a></p></div>
  </div>
</main></body></html>
