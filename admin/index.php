<?php
declare(strict_types=1);
use function App\{pdo, e, require_user, require_admin};
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/auth.php';
require __DIR__ . '/../inc/helpers.php';

$u = \App\require_user();
$isAdmin = ($u['role'] === 'admin');
$counts = ['users'=>null,'links'=>null];
if ($isAdmin) {
    $counts['users'] = (int)pdo()->query("SELECT COUNT(*) AS c FROM users")->fetch()['c'];
    $counts['links'] = (int)pdo()->query("SELECT COUNT(*) AS c FROM links")->fetch()['c'];
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Dashboard</title><link rel="stylesheet" href="/assets/css/styles.css"></head>
<body class="theme-light"><main class="container">
  <header class="admin-header">
    <h1>Dashboard</h1>
    <nav>
      <a href="/admin/profile.php">Profile</a>
      <a href="/admin/links.php">Links</a>
      <a href="/admin/security/">Security</a>
      <?php if ($isAdmin): ?><a href="/admin/users.php">Users</a><?php endif; ?>
      <a class="danger" href="/admin/logout.php">Logout</a>
    </nav>
  </header>
  <?php if ($isAdmin): ?>
    <section class="cards">
      <div class="card"><strong><?= $counts['users'] ?></strong><div>Users</div></div>
      <div class="card"><strong><?= $counts['links'] ?></strong><div>Links</div></div>
    </section>
  <?php else: ?>
    <p>Welcome, <?= e($u['name']) ?>. Use the links above to manage your profile.</p>
  <?php endif; ?>
</main></body></html>
