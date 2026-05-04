<?php
/**
 * about.php — About page.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);
use function App\{config, e, base_url};
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/helpers.php';
$appName = e(config()['app_name'] ?? 'Hillwork');
$canonical = rtrim(base_url(), '/') . '/about';
$metaDesc = $appName . ' is a free Linktree alternative. Create a clean, customizable link-in-bio page. No paywalls. Own your data. Best free link in bio tool.';
$metaKeywords = 'Linktree alternative, link in bio, about ' . $appName . ', free link page';
$pageTitle = 'About · ' . $appName . ' — Free Linktree Alternative';
$bodyClass = 'min-h-screen bg-zinc-50 text-zinc-900 antialiased';
ob_start();
?>
<meta name="description" content="<?= e($metaDesc) ?>">
<meta name="keywords" content="<?= e($metaKeywords) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:title" content="About · <?= $appName ?> — Free Linktree Alternative">
<meta property="og:description" content="<?= e($metaDesc) ?>">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="About · <?= $appName ?>">
<meta name="twitter:description" content="<?= e($metaDesc) ?>">
<?php
$headExtra = ob_get_clean();
require __DIR__ . '/inc/partials/head.php';
?>
  <main class="mx-auto max-w-md px-4 py-12 sm:px-5">
    <div class="card">
      <h1 class="mb-3 text-2xl font-semibold tracking-tight text-zinc-900">About</h1>
      <p class="text-sm leading-relaxed text-zinc-600"><?= $appName ?> is a free, open alternative to Linktree. Create a clean, customizable link‑in‑bio page. No paywalls. Own your data.</p>
      <p class="mt-6"><a href="/" class="font-medium text-teal-700 hover:text-teal-800">Home</a></p>
    </div>
  </main>
<?php require __DIR__ . '/inc/partials/site_footer.php'; ?>
</body></html>
