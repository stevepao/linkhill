<?php
/**
 * Shared HTML head + body open. Set before include:
 *   $pageTitle (string), optional $bodyClass, optional $headExtra (raw HTML before stylesheet).
 */
declare(strict_types=1);
use function App\e;
$pageTitle = $pageTitle ?? 'LinkHill';
$bodyClass = $bodyClass ?? 'min-h-screen bg-zinc-50 text-zinc-900 antialiased';
$headExtra = $headExtra ?? '';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0d9488">
<meta name="apple-mobile-web-app-capable" content="yes">
<title><?= e((string) $pageTitle) ?></title>
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon-180x180.png">
<link rel="manifest" href="/manifest.json">
<?= $headExtra ?>
<link rel="stylesheet" href="/public/assets/app.css">
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () {
    navigator.serviceWorker.register('/sw.js').catch(function () {});
  });
}
</script>
</head>
<body class="<?= e((string) $bodyClass) ?>">
