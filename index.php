<?php
declare(strict_types=1);
use function App\{pdo, e};
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/helpers.php';

$u = $_GET['u'] ?? null;
$go = $_GET['go'] ?? null;

if ($go !== null) {
    $id = (int)$go;
    if ($id > 0) {
        $stmt = pdo()->prepare("SELECT id, url FROM links WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        if ($row = $stmt->fetch()) {
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
    $stmt = pdo()->prepare("SELECT id, display_name, username, bio, theme, avatar_path FROM users WHERE username = ?");
    $stmt->execute([$u]);
    $user = $stmt->fetch();
    if (!$user) {
        http_response_code(404);
        echo "User not found";
        exit;
    }
    $links = pdo()->prepare("SELECT id, title, url, color_hex, icon_slug FROM links WHERE user_id = ? AND is_active = 1 ORDER BY position ASC, id ASC");
    $links->execute([$user['id']]);
    $links = $links->fetchAll();
    include __DIR__ . '/inc/icons.php';
    $themeClass = 'theme-' . $user['theme'];
    ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e($user['display_name']) ?> · Links</title><link rel="stylesheet" href="/assets/css/styles.css"></head>
<body class="<?= e($themeClass) ?>">
  <main class="container">
    <section class="profile">
      <?php if (!empty($user['avatar_path'])): ?>
        <img class="avatar" src="<?= e($user['avatar_path']) ?>" alt="Avatar">
      <?php endif; ?>
      <h1 class="name">@<?= e($user['username']) ?></h1>
      <?php if (!empty($user['bio'])): ?>
        <p class="bio"><?= nl2br(e($user['bio'])) ?></p>
      <?php endif; ?>
    </section>
    <section class="links">
      <?php foreach ($links as $l): ?>
        <a class="link-btn" style="--btn-color: <?= e($l['color_hex']) ?>;" href="/index.php?go=<?= (int)$l['id'] ?>" rel="noopener">
          <span class="icon">
            <?php
              $svg = \App\render_icon_svg($l['icon_slug'] ?? 'link');
              echo $svg ?: '';
            ?>
          </span>
          <span class="title"><?= e($l['title']) ?></span>
        </a>
      <?php endforeach; ?>
    </section>
  </main>
</body></html><?php
    exit;
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>LinkHub</title><link rel="stylesheet" href="/assets/css/styles.css"></head>
<body class="theme-light">
  <main class="container">
    <h1>LinkHub</h1>
    <p>Create your profile at <code>/admin</code> and visit <code>/@username</code> to share your link‑in‑bio page.</p>
  </main>
</body></html>
