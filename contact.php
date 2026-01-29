<?php
declare(strict_types=1);
use function App\{config, e};
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/helpers.php';
$appName = e(config()['app_name'] ?? 'Hillwork');
$year = (int) date('Y');
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Contact · <?= $appName ?></title><link rel="stylesheet" href="/assets/css/paos.css"></head>
<body>
  <main class="container">
    <div class="stack">
      <h1>Contact</h1>
      <p>For support or feedback, please use the contact method provided by your host or administrator (e.g. Hillwork).</p>
      <p><a href="/">Home</a></p>
    </div>
  </main>
  <footer class="footer"><div class="container"><nav aria-label="Footer"><a href="/about">About</a><a href="/privacy">Privacy</a><a href="/terms">Terms</a><a href="/contact">Contact</a></nav><p class="footer-copy">© <?= $year ?> Hillwork</p></div></footer>
</body></html>
