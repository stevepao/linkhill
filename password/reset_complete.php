<?php
declare(strict_types=1);
use function App\{config, e};
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/helpers.php';
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Password updated · <?= e(config()['app_name']) ?></title><link rel="stylesheet" href="/assets/css/styles.css"></head>
<body class="theme-light"><main class="container narrow">
  <h1>Password updated</h1>
  <div class="alert"><p>Your password has been changed. Other sessions have been signed out. You can now sign in with your new password.</p><p><a href="/admin/login.php">Sign in</a></p></div>
</main></body></html>
