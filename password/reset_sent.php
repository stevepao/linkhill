<?php
/**
 * reset_sent.php — Reset link sent confirmation.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);
use function App\{config, e};
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/helpers.php';
$pageTitle = 'Reset link sent · ' . e(config()['app_name']);
$bodyClass = 'min-h-screen bg-zinc-50 text-zinc-900 antialiased';
require __DIR__ . '/../inc/partials/head.php';
?>
<main class="mx-auto flex min-h-[70vh] max-w-md flex-col justify-center px-4 py-12 sm:px-5">
  <div class="card">
  <h1 class="mb-4 text-2xl font-semibold tracking-tight text-zinc-900">Check your email</h1>
  <div class="alert"><p class="text-sm">If an account exists for that email, we sent a password reset link. It expires in 30 minutes.</p><p class="mt-3 text-sm"><a href="/admin/login.php" class="font-medium text-teal-800 underline">Back to sign in</a></p></div>
  </div>
</main></body></html>
