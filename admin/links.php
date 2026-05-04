<?php
/**
 * links.php — Manage links.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);
use function App\{pdo, e, require_user, links_has_description};
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/auth.php';
require __DIR__ . '/../inc/csrf.php';
require __DIR__ . '/../inc/helpers.php';
require __DIR__ . '/../inc/icons.php';

$me = \App\require_user();
$csrf = \App\csrf_token();
$hasDesc = links_has_description();
$cols = $hasDesc ? 'id, entry_type, title, url, description, color_hex, icon_slug, position, is_active' : 'id, entry_type, title, url, color_hex, icon_slug, position, is_active';
$links = pdo()->prepare("SELECT $cols FROM links WHERE user_id=? ORDER BY position ASC, id ASC");
$links->execute([$me['id']]);
$links = $links->fetchAll();
$icons = \App\icon_list();
$pageTitle = 'Links';
$bodyClass = 'min-h-screen bg-zinc-50 text-zinc-900 antialiased';
require __DIR__ . '/../inc/partials/head.php';
?>
<main class="admin-app mx-auto max-w-3xl px-4 pb-20 pt-8 sm:px-6 lg:px-8">
<?php
$nav_heading = 'Links';
$nav_current = 'links';
require __DIR__ . '/../inc/partials/admin_nav.php';
?>
  <div class="admin-content">
  <section class="card">
    <h2 class="mb-4 border-b border-zinc-100 pb-3 text-lg font-semibold text-zinc-900">Add link</h2>
    <form id="addForm" class="form-stack">
      <input type="hidden" name="_token" value="<?= e($csrf) ?>">
      <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <label>Title<br><input type="text" name="title" required maxlength="80"></label>
        <label>URL<br><input type="url" name="url" placeholder="https://..." required></label>
      </div>
      <?php if ($hasDesc): ?><label>Description (optional; if set, link shows as a card with blurb on your page)<br><input type="text" name="description" placeholder="Optional blurb" maxlength="500"></label><?php endif; ?>
      <div class="flex flex-wrap items-end gap-3">
        <div class="grid min-w-0 flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
          <label>Color<br><input type="color" name="color_hex" value="#111827"></label>
          <label>Icon<br>
            <select name="icon_slug">
              <?php foreach ($icons as $slug => $path): ?>
                <option value="<?= e($slug) ?>"><?= e($slug) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>
        <button type="submit" class="btn-primary shrink-0">Add link</button>
      </div>
    </form>
  </section>
  <section class="card">
    <h2 class="mb-4 border-b border-zinc-100 pb-3 text-lg font-semibold text-zinc-900">Add heading</h2>
    <form id="addHeadingForm" class="form-stack">
      <input type="hidden" name="_token" value="<?= e($csrf) ?>">
      <input type="hidden" name="entry_type" value="heading">
      <div class="flex flex-wrap items-end gap-3">
        <label class="min-w-[12rem] flex-1">Heading text<br><input type="text" name="title" placeholder="Section title (e.g. Social links)" maxlength="80" required></label>
        <button type="submit" class="btn-primary shrink-0">Add heading</button>
      </div>
    </form>
  </section>
  <section class="card">
    <h2 class="mb-4 border-b border-zinc-100 pb-3 text-lg font-semibold text-zinc-900">Your entries</h2>
    <ul id="linkList" class="m-0 flex list-none flex-col gap-3 p-0" data-csrf="<?= e($csrf) ?>">
      <?php foreach ($links as $l): ?>
        <?php $isHeading = ($l['entry_type'] ?? 'link') === 'heading'; ?>
        <li class="link-item<?= $isHeading ? ' link-item--heading' : '' ?>" data-id="<?= (int)$l['id'] ?>" data-type="<?= $isHeading ? 'heading' : 'link' ?>">
          <div class="link-item__row">
            <span class="drag" title="Drag to reorder">⋮⋮</span>
            <div class="link-item__fields">
              <?php if ($isHeading): ?>
                <div class="link-item__heading-row">
                  <label class="link-item__label">Heading<br><input class="title" type="text" value="<?= e($l['title']) ?>" maxlength="80" placeholder="Section title"></label>
                  <div class="link-item__actions">
                    <button type="button" class="save">Save</button>
                    <button type="button" class="delete danger">Delete</button>
                  </div>
                </div>
              <?php else: ?>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                  <label class="link-item__label">Title<br><input class="title" type="text" value="<?= e($l['title']) ?>" maxlength="80" placeholder="Link title"></label>
                  <label class="link-item__label">URL<br><input class="url" type="url" value="<?= e($l['url']) ?>" placeholder="https://..."></label>
                </div>
                <?php if ($hasDesc): ?><label class="link-item__label">Description (optional)<br><input class="description" type="text" placeholder="Optional blurb (shows as card)" value="<?= e($l['description'] ?? '') ?>" maxlength="500"></label><?php endif; ?>
                <div class="link-item__meta">
                  <div class="link-item__meta-left">
                    <label class="link-item__meta-label">Color <input class="color" type="color" value="<?= e($l['color_hex']) ?>" title="Button color"></label>
                    <label class="link-item__meta-label">Icon <select class="icon"><?php foreach ($icons as $slug => $path): ?><option value="<?= e($slug) ?>" <?= $slug === ($l['icon_slug'] ?? 'link') ? 'selected' : '' ?>><?= e($slug) ?></option><?php endforeach; ?></select></label>
                  </div>
                  <div class="link-item__actions">
                    <button type="button" class="save">Save</button>
                    <button type="button" class="delete danger">Delete</button>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const list = document.getElementById('linkList');
const csrf = list.dataset.csrf;
new Sortable(list, {
  handle: '.drag',
  animation: 150,
  onEnd: async function () {
    const ids = Array.from(list.querySelectorAll('.link-item')).map((li, idx) => ({id: li.dataset.id, position: idx}));
    await fetch('/admin/api/reorder_links.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
      body: JSON.stringify({items: ids})
    });
  }
});
document.getElementById('addForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'create');
  fd.append('entry_type', 'link');
  const res = await fetch('/admin/api/link_crud.php', { method: 'POST', body: fd });
  const json = await res.json();
  if (json && json.id) location.reload();
  else alert(json.error || 'Failed to add link');
});
document.getElementById('addHeadingForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'create');
  fd.append('entry_type', 'heading');
  const res = await fetch('/admin/api/link_crud.php', { method: 'POST', body: fd });
  const json = await res.json();
  if (json && json.id) location.reload();
  else alert(json.error || 'Failed to add heading');
});
list.addEventListener('click', async (e) => {
  const li = e.target.closest('.link-item');
  if (!li) return;
  if (e.target.classList.contains('save')) {
    const fd = new FormData();
    fd.append('_token', csrf);
    fd.append('action', 'update');
    fd.append('id', li.dataset.id);
    fd.append('title', li.querySelector('.title').value);
    if (li.dataset.type === 'link') {
      fd.append('url', li.querySelector('.url').value);
      var descEl = li.querySelector('.description');
      if (descEl) fd.append('description', descEl.value.trim());
      fd.append('color_hex', li.querySelector('.color').value);
      fd.append('icon_slug', li.querySelector('.icon').value);
    }
    const res = await fetch('/admin/api/link_crud.php', {method: 'POST', body: fd});
    const json = await res.json();
    if (!json.ok) alert(json.error || 'Update failed');
  } else if (e.target.classList.contains('delete')) {
    if (!confirm(li.dataset.type === 'heading' ? 'Delete this heading?' : 'Delete this link?')) return;
    const fd = new FormData();
    fd.append('_token', csrf);
    fd.append('action', 'delete');
    fd.append('id', li.dataset.id);
    const res = await fetch('/admin/api/link_crud.php', {method: 'POST', body: fd});
    const json = await res.json();
    if (json.ok) li.remove();
    else alert(json.error || 'Delete failed');
  }
});
</script></body></html>
