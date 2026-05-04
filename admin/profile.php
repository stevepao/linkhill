<?php
/**
 * profile.php — User profile.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);
use function App\{pdo, e, require_user};
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/auth.php';
require __DIR__ . '/../inc/csrf.php';
require __DIR__ . '/../inc/helpers.php';

$me = \App\require_user();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    \App\csrf_verify();
    $display = trim((string)($_POST['display_name'] ?? ''));
    $bio     = trim((string)($_POST['bio'] ?? ''));
    $customFooter = trim((string)($_POST['custom_footer'] ?? ''));
    $theme   = in_array($_POST['theme'] ?? 'light', ['light','dark','custom'], true) ? $_POST['theme'] : 'light';
    // Handle avatar upload optionally
    $avatarPath = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $f = $_FILES['avatar'];
        if ($f['size'] > 200*1024) {
            $msg = 'Avatar too large (max 200KB).';
        } else {
            $fi = new finfo(FILEINFO_MIME_TYPE);
            $mime = $fi->file($f['tmp_name']);
            $ext = $mime === 'image/jpeg' ? 'jpg' : ($mime === 'image/png' ? 'png' : '');
            if (!$ext) {
                $msg = 'Invalid avatar format. Use JPG or PNG.';
            } else {
                $name = bin2hex(random_bytes(8)) . '.' . $ext;
                $dest = __DIR__ . '/../assets/img/avatars/' . $name;
                if (move_uploaded_file($f['tmp_name'], $dest)) {
                    $avatarPath = '/assets/img/avatars/' . $name;
                } else {
                    $msg = 'Failed to save avatar.';
                }
            }
        }
    }
    if ($display && !$msg) {
        $sql = "UPDATE users SET display_name=?, bio=?, custom_footer=?, theme=?, updated_at=NOW()";
        $args = [$display, $bio, $customFooter, $theme];
        if ($avatarPath) { $sql .= ", avatar_path=?"; $args[] = $avatarPath; }
        $sql .= " WHERE id=?";
        $args[] = $me['id'];
        $up = pdo()->prepare($sql);
        $up->execute($args);
        $msg = 'Profile updated.';
    } elseif (!$msg) {
        $msg = 'Display name required.';
    }
}
$stmt = pdo()->prepare("SELECT email,username,display_name,bio,custom_footer,theme,avatar_path FROM users WHERE id=?");
$stmt->execute([$me['id']]);
$row = $stmt->fetch();
$pageTitle = 'Profile';
$bodyClass = 'min-h-screen bg-zinc-50 text-zinc-900 antialiased';
require __DIR__ . '/../inc/partials/head.php';
?>
<main class="mx-auto max-w-3xl px-4 pb-16 pt-6 sm:px-6 lg:px-8">
<?php
$nav_heading = 'Profile';
$nav_current = 'profile';
require __DIR__ . '/../inc/partials/admin_nav.php';
?>
  <?php if ($msg): ?><div class="alert mb-6"><?= e($msg) ?></div><?php endif; ?>
  <div class="card form-stack">
  <form method="post" enctype="multipart/form-data">
    <?= \App\csrf_field() ?>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <label>Display name<br><input type="text" name="display_name" value="<?= e($row['display_name']) ?>" required></label>
      <label>Username (public URL)<br><input type="text" value="@<?= e($row['username']) ?>" disabled class="bg-zinc-100 text-zinc-600"></label>
    </div>
    <label>Bio<br><textarea name="bio" rows="4"><?= e($row['bio']) ?></textarea></label>
    <label>Custom footer (optional)<br><textarea name="custom_footer" rows="3" placeholder="Shown centered below your links on your public page"><?= e($row['custom_footer'] ?? '') ?></textarea></label>
    <label>Theme<br>
      <select name="theme">
        <option value="light" <?= $row['theme']==='light'?'selected':'' ?>>Light</option>
        <option value="dark"  <?= $row['theme']==='dark'?'selected':'' ?>>Dark</option>
        <option value="custom"<?= $row['theme']==='custom'?'selected':'' ?>>Custom</option>
      </select>
    </label>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
      <?php if (!empty($row['avatar_path'])): ?>
        <img src="<?= e($row['avatar_path']) ?>" alt="Avatar" class="h-16 w-16 shrink-0 rounded-full object-cover ring-2 ring-zinc-100">
      <?php endif; ?>
      <label class="flex-1">Avatar (JPG/PNG, ≤200KB)
        <input type="file" name="avatar" accept="image/jpeg,image/png">
      </label>
    </div>
    <div class="pt-2">
      <button type="submit" class="btn-primary">Save profile</button>
    </div>
  </form>
  </div>
  <p class="mt-6 text-sm text-zinc-600">Public page: <a href="/@<?= e($row['username']) ?>" target="_blank" rel="noopener" class="font-medium text-teal-700 hover:text-teal-800">/@<?= e($row['username']) ?></a></p>
</main></body></html>
