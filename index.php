<?php
/**
 * index.php — Public homepage and profile pages.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);
use function App\{pdo, e, config, base_url, links_has_description, is_valid_hex_color, link_contrast_text, link_darken, link_muted_rgba};
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/helpers.php';

$u = $_GET['u'] ?? null;
$go = $_GET['go'] ?? null;

if ($go !== null) {
    $id = (int)$go;
    if ($id > 0) {
        $stmt = pdo()->prepare("SELECT id, url, entry_type FROM links WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row && ($row['entry_type'] ?? 'link') === 'link' && !empty($row['url'])) {
            // Minimal analytics
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $ipHash = $ip ? hash('sha256', $ip . date('Y-m-d')) : null;
            $uaHash = $ua ? hash('sha256', $ua) : null;
            $ins = pdo()->prepare("INSERT INTO link_clicks (link_id, ip_hash, ua_hash) VALUES (?, ?, ?)");
            $ins->execute([$row['id'], $ipHash, $uaHash]);
            header("Location: " . $row['url'], true, 302);
            exit;
        }
    }
    http_response_code(404);
    echo "Link not found";
    exit;
}

if ($u !== null) {
    $stmt = pdo()->prepare("SELECT id, display_name, username, bio, custom_footer, theme, avatar_path FROM users WHERE username = ?");
    $stmt->execute([$u]);
    $user = $stmt->fetch();
    if (!$user) {
        http_response_code(404);
        echo "User not found";
        exit;
    }
    $hasDesc = links_has_description();
    $linkCols = $hasDesc ? 'id, entry_type, title, url, description, color_hex, icon_slug' : 'id, entry_type, title, url, color_hex, icon_slug';
    $links = pdo()->prepare("SELECT $linkCols FROM links WHERE user_id = ? AND is_active = 1 ORDER BY position ASC, id ASC");
    $links->execute([$user['id']]);
    $links = $links->fetchAll();
    include __DIR__ . '/inc/icons.php';
    $profileCanonical = rtrim(base_url(), '/') . '/@' . $user['username'];
    $profileMetaDesc = e($user['display_name']) . ' — link in bio, links and profile. View all links for ' . e($user['display_name']) . '.';
    $pageTitle = e($user['display_name']) . ' · Links | Link in Bio';
    $theme = (string) ($user['theme'] ?? 'light');
    $bodyClass = match ($theme) {
        'dark' => 'min-h-screen bg-zinc-950 text-zinc-100 antialiased',
        'custom' => 'min-h-screen bg-zinc-50 text-zinc-900 antialiased',
        default => 'min-h-screen bg-zinc-50 text-zinc-900 antialiased',
    };
    ob_start();
    ?>
<meta name="description" content="<?= $profileMetaDesc ?>">
<link rel="canonical" href="<?= e($profileCanonical) ?>">
<meta property="og:type" content="profile">
<meta property="og:url" content="<?= e($profileCanonical) ?>">
<meta property="og:title" content="<?= e($user['display_name']) ?> · Links">
<meta property="og:description" content="<?= $profileMetaDesc ?>">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?= e($user['display_name']) ?> · Links">
<meta name="twitter:description" content="<?= $profileMetaDesc ?>">
<?php
    $headExtra = ob_get_clean();
    require __DIR__ . '/inc/partials/head.php';
?>
  <main class="mx-auto max-w-md px-4 pb-16 pt-8 sm:px-5">
    <header class="mb-8 bg-transparent px-2 py-4 text-center">
      <?php if (!empty($user['avatar_path'])): ?>
        <img class="mx-auto mb-4 h-24 w-24 rounded-full object-cover ring-2 ring-zinc-200/40 sm:h-28 sm:w-28 <?= $theme === 'dark' ? 'ring-zinc-600/50' : '' ?>" src="<?= e($user['avatar_path']) ?>" alt="">
      <?php endif; ?>
      <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 <?= $theme === 'dark' ? '!text-zinc-50' : '' ?>"><?= e($user['display_name']) ?></h1>
      <?php if (!empty($user['bio'])): ?>
        <p class="mt-2 text-sm leading-relaxed text-zinc-600 <?= $theme === 'dark' ? '!text-zinc-400' : '' ?>"><?= nl2br(e($user['bio'])) ?></p>
      <?php endif; ?>
    </header>
    <nav class="flex flex-col gap-3" aria-label="Links">
        <?php foreach ($links as $l): ?>
          <?php if (($l['entry_type'] ?? 'link') === 'heading'): ?>
            <h2 class="mb-1 mt-8 text-center text-lg font-semibold tracking-tight text-zinc-900 first:mt-0 <?= $theme === 'dark' ? '!text-zinc-100' : '' ?>"><?= e($l['title']) ?></h2>
          <?php else: ?>
          <?php
            $href = '/index.php?go=' . (int)$l['id'];
            $showCard = $hasDesc && !empty(trim((string)($l['description'] ?? '')));
            $hex = (is_valid_hex_color($l['color_hex'] ?? '') ? $l['color_hex'] : '#111827');
            $btnStyle = '--button-bg:' . $hex . ';--button-text:' . link_contrast_text($hex) . ';--button-hover:' . link_darken($hex) . ';';
            $cardStyle = '--card-bg:' . $hex . ';--border:' . link_darken($hex, 0.2) . ';color:' . link_contrast_text($hex) . ';--muted:' . link_muted_rgba($hex) . ';';
          ?>
          <?php if ($showCard): ?>
            <a class="profile-link-card" href="<?= e($href) ?>" rel="noopener" style="<?= e($cardStyle) ?>">
              <h3><?= e($l['title']) ?></h3>
              <p><?= nl2br(e(trim($l['description']))) ?></p>
            </a>
          <?php else: ?>
            <a class="profile-link-btn" href="<?= e($href) ?>" rel="noopener" style="<?= e($btnStyle) ?>">
              <span class="icon inline-flex shrink-0">
                <?php $svg = \App\render_icon_svg($l['icon_slug'] ?? 'link'); echo $svg ?: ''; ?>
              </span>
              <span class="text-center"><?= e($l['title']) ?></span>
            </a>
          <?php endif; ?>
          <?php endif; ?>
        <?php endforeach; ?>
    </nav>
      <?php if (!empty(trim((string)($user['custom_footer'] ?? '')))): ?>
      <footer class="mt-10 border-t border-zinc-200/80 pt-6 text-center text-xs leading-relaxed text-zinc-500 <?= $theme === 'dark' ? 'border-zinc-800 !text-zinc-400' : '' ?>"><?= nl2br(e(trim($user['custom_footer']))) ?></footer>
      <?php endif; ?>
  </main>
</body></html><?php
    exit;
}
// Homepage: informational only; no auth UI
$appName = config()['app_name'] ?? 'Hillwork';
$canonical = rtrim(base_url(), '/');
$metaDesc = 'Free Linktree alternative: create a clean link-in-bio page in minutes. No paywalls, no lock-in. Best free link in bio tool for Instagram, TikTok, and more.';
$metaKeywords = 'Linktree alternative, link in bio, link in bio free, bio link, Instagram link, TikTok link, link hub, link page';
$year = (int) date('Y');
$pageTitle = e($appName) . ' — Free Linktree Alternative | Link in Bio';
$bodyClass = 'min-h-screen bg-zinc-50 text-zinc-900 antialiased';
ob_start();
?>
  <meta name="description" content="<?= e($metaDesc) ?>">
  <meta name="keywords" content="<?= e($metaKeywords) ?>">
  <link rel="canonical" href="<?= e($canonical) ?>/">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= e($canonical) ?>/">
  <meta property="og:title" content="<?= e($appName) ?> — Free Linktree Alternative | Link in Bio">
  <meta property="og:description" content="<?= e($metaDesc) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($appName) ?> — Free Linktree Alternative | Link in Bio">
  <meta name="twitter:description" content="<?= e($metaDesc) ?>">
<?php
$headExtra = ob_get_clean();
require __DIR__ . '/inc/partials/head.php';
?>
  <div class="mx-auto max-w-3xl px-4 pb-16 pt-10 sm:px-6 lg:px-8">
    <main>
      <section class="card mb-12 p-8 text-center shadow-sm ring-1 ring-zinc-200/70 sm:p-10" aria-labelledby="landing-hero-heading">
        <h1 id="landing-hero-heading" class="text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl"><?= e($appName) ?></h1>
        <p class="mt-4 text-base text-zinc-600">All your links, one simple page — free &amp; open.</p>
        <p class="mt-2 text-sm leading-relaxed text-zinc-500">Create a clean, customizable link hub in minutes. No paywalls. Own your data.</p>
        <nav class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center sm:gap-4" aria-label="Sign up and log in">
          <a href="/signup" class="btn-primary w-full sm:w-auto sm:min-w-[11rem]" id="cta-signup">Create free account</a>
          <a href="/login" class="btn-secondary w-full sm:w-auto sm:min-w-[11rem]" id="cta-login">Log in</a>
        </nav>
      </section>

      <section class="mb-12" aria-labelledby="benefits-heading">
        <h2 id="benefits-heading" class="sr-only">Benefits</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3 md:gap-6">
          <div class="card flex h-full flex-col p-6 text-left shadow-sm ring-1 ring-zinc-200/70">
            <h3 class="mb-2 text-base font-semibold text-zinc-900">Free &amp; open</h3>
            <p class="muted m-0 flex-1">No subscriptions. Open ethos. No vendor lock‑in.</p>
          </div>
          <div class="card flex h-full flex-col p-6 text-left shadow-sm ring-1 ring-zinc-200/70">
            <h3 class="mb-2 text-base font-semibold text-zinc-900">Fast &amp; private</h3>
            <p class="muted m-0 flex-1">Minimal tracking. Privacy‑respectful by default.</p>
          </div>
          <div class="card flex h-full flex-col p-6 text-left shadow-sm ring-1 ring-zinc-200/70">
            <h3 class="mb-2 text-base font-semibold text-zinc-900">Customizable</h3>
            <p class="muted m-0 flex-1">Your links, your branding, your control.</p>
          </div>
        </div>
      </section>

      <p class="text-center text-sm leading-relaxed text-zinc-500">An open alternative to Linktree. Your page, your data.</p>
    </main>

    <footer class="mt-14 border-t border-zinc-200/50 pt-8">
      <nav class="mb-2 flex flex-wrap gap-x-5 gap-y-1 text-xs text-zinc-500" aria-label="Footer">
        <a href="/about" class="text-zinc-500 transition-colors hover:text-zinc-700">About</a>
        <a href="/privacy" class="text-zinc-500 transition-colors hover:text-zinc-700">Privacy</a>
        <a href="/terms" class="text-zinc-500 transition-colors hover:text-zinc-700">Terms</a>
        <a href="/contact" class="text-zinc-500 transition-colors hover:text-zinc-700">Contact</a>
      </nav>
      <p class="text-xs text-zinc-400">© <?= $year ?> <a href="https://hillwork.us" class="text-zinc-500 transition-colors hover:text-zinc-600">Hillwork, LLC</a></p>
    </footer>
  </div>
</body>
</html>
