<?php
/**
 * Admin page header + primary tab navigation.
 * Requires: $me (user row), $nav_heading (page title), $nav_current
 * ('dashboard'|'profile'|'links'|'security'|'users').
 */
declare(strict_types=1);
use function App\e;
$me = $me ?? [];
$nav_heading = $nav_heading ?? 'Admin';
$nav_current = $nav_current ?? '';
$isAdmin = (($me['role'] ?? '') === 'admin');
?>
<header class="admin-page-header mb-10">
  <h1 class="mb-8 text-3xl font-semibold tracking-tight text-zinc-900"><?= e((string) $nav_heading) ?></h1>
  <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <nav class="inline-flex max-w-full flex-wrap gap-0.5 rounded-xl bg-zinc-100/90 p-1 ring-1 ring-zinc-200/70" aria-label="Admin sections">
      <?php if ($isAdmin): ?>
        <a href="/admin/" class="<?= $nav_current === 'dashboard' ? 'tab tab-active' : 'tab' ?>">Dashboard</a>
      <?php endif; ?>
      <a href="/admin/profile.php" class="<?= $nav_current === 'profile' ? 'tab tab-active' : 'tab' ?>">Profile</a>
      <a href="/admin/links.php" class="<?= $nav_current === 'links' ? 'tab tab-active' : 'tab' ?>">Links</a>
      <a href="/admin/security/" class="<?= $nav_current === 'security' ? 'tab tab-active' : 'tab' ?>">Security</a>
      <?php if ($isAdmin): ?>
        <a href="/admin/users.php" class="<?= $nav_current === 'users' ? 'tab tab-active' : 'tab' ?>">Users</a>
      <?php endif; ?>
    </nav>
    <a href="/admin/logout.php" class="admin-logout">Logout</a>
  </div>
</header>
