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
<title><?= e((string) $pageTitle) ?></title>
<?= $headExtra ?>
<link rel="stylesheet" href="/public/assets/app.css">
</head>
<body class="<?= e((string) $bodyClass) ?>">
