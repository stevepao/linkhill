<?php
declare(strict_types=1);
use function App\{config, e};
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/helpers.php';
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Reset link sent · <?= e(config()['app_name']) ?></title><link rel="stylesheet" href="/assets/css/styles.css"></head>
<body class="theme-light"><main class="container narrow">
  <h1>Check your email</h1>
  <div class="alert"><p>If an account exists for that email, we sent a password reset link. It expires in 30 minutes.</p><p><a href="/admin/login.php">Back to sign in</a></p></div>
</main></body></html>
