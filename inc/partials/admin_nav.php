<?php
/**
 * Admin page header: page title + section tabs (left, scroll if needed) + account actions (right).
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
  <div class="flex items-center justify-between gap-3">
    <nav class="min-w-0 flex-1 overflow-x-auto overscroll-x-contain rounded-xl bg-zinc-100/90 p-1 ring-1 ring-zinc-200/70 [scrollbar-width:thin]" aria-label="Admin sections">
      <div class="inline-flex w-max flex-nowrap gap-0.5 whitespace-nowrap">
        <?php if ($isAdmin): ?>
          <a href="/admin/" class="<?= $nav_current === 'dashboard' ? 'tab tab-active' : 'tab' ?>">Dashboard</a>
        <?php endif; ?>
        <a href="/admin/profile.php" class="<?= $nav_current === 'profile' ? 'tab tab-active' : 'tab' ?>">Profile</a>
        <a href="/admin/links.php" class="<?= $nav_current === 'links' ? 'tab tab-active' : 'tab' ?>">Links</a>
        <a href="/admin/security/" class="<?= $nav_current === 'security' ? 'tab tab-active' : 'tab' ?>">Security</a>
        <?php if ($isAdmin): ?>
          <a href="/admin/users.php" class="<?= $nav_current === 'users' ? 'tab tab-active' : 'tab' ?>">Users</a>
        <?php endif; ?>
      </div>
    </nav>
    <div class="shrink-0" role="group" aria-label="Account">
      <a href="/admin/logout.php" class="admin-logout">Logout</a>
    </div>
  </div>
</header>
