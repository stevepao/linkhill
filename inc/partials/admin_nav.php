<?php
/**
 * Admin top navigation. Requires: $me (user row), $nav_heading (string), $nav_current
 * ('dashboard'|'profile'|'links'|'security'|'users').
 */
declare(strict_types=1);
use function App\e;
$me = $me ?? [];
$nav_heading = $nav_heading ?? 'Admin';
$nav_current = $nav_current ?? '';
$isAdmin = (($me['role'] ?? '') === 'admin');
?>
<header class="mb-8 border-b border-zinc-200 pb-6">
  <div class="mb-4">
    <h1 class="text-2xl font-semibold tracking-tight text-zinc-900"><?= e((string) $nav_heading) ?></h1>
  </div>
  <nav class="flex flex-wrap gap-1" aria-label="Account">
    <?php if ($isAdmin): ?>
      <a href="/admin/" class="nav-link <?= $nav_current === 'dashboard' ? 'nav-link-active' : '' ?>">Dashboard</a>
    <?php endif; ?>
    <a href="/admin/profile.php" class="nav-link <?= $nav_current === 'profile' ? 'nav-link-active' : '' ?>">Profile</a>
    <a href="/admin/links.php" class="nav-link <?= $nav_current === 'links' ? 'nav-link-active' : '' ?>">Links</a>
    <a href="/admin/security/" class="nav-link <?= $nav_current === 'security' ? 'nav-link-active' : '' ?>">Security</a>
    <?php if ($isAdmin): ?>
      <a href="/admin/users.php" class="nav-link <?= $nav_current === 'users' ? 'nav-link-active' : '' ?>">Users</a>
    <?php endif; ?>
    <a href="/admin/logout.php" class="nav-link nav-link-danger">Logout</a>
  </nav>
</header>
