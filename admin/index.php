<?php
/**
 * index.php — Admin dashboard.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);
use function App\{pdo, e, require_user, require_admin};
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/auth.php';
require __DIR__ . '/../inc/helpers.php';

$u = \App\require_user();
$isAdmin = ($u['role'] === 'admin');
if (!$isAdmin) {
    header('Location: /admin/profile.php');
    exit;
}
$counts = ['users'=>null,'links'=>null];
if ($isAdmin) {
    $counts['users'] = (int)pdo()->query("SELECT COUNT(*) AS c FROM users")->fetch()['c'];
    $counts['links'] = (int)pdo()->query("SELECT COUNT(*) AS c FROM links")->fetch()['c'];
}
$pageTitle = 'Dashboard';
$bodyClass = 'min-h-screen bg-zinc-50 text-zinc-900 antialiased';
require __DIR__ . '/../inc/partials/head.php';
?>
<main class="mx-auto max-w-3xl px-4 pb-16 pt-6 sm:px-6 lg:px-8">
<?php
$nav_heading = 'Dashboard';
$nav_current = 'dashboard';
require __DIR__ . '/../inc/partials/admin_nav.php';
?>
  <?php if ($isAdmin): ?>
    <div class="grid gap-4 sm:grid-cols-2">
      <div class="card text-center">
        <p class="text-3xl font-semibold tracking-tight text-teal-700"><?= $counts['users'] ?></p>
        <p class="muted mt-1">Users</p>
      </div>
      <div class="card text-center">
        <p class="text-3xl font-semibold tracking-tight text-teal-700"><?= $counts['links'] ?></p>
        <p class="muted mt-1">Links</p>
      </div>
    </div>
  <?php else: ?>
    <div class="card">
      <p class="text-sm text-zinc-700">Welcome, <?= e($u['name']) ?>. Use the links above to manage your profile.</p>
    </div>
  <?php endif; ?>
</main></body></html>
