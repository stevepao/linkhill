<?php
declare(strict_types=1);
use function App\{pdo, require_user, json_response};
require __DIR__ . '/../../inc/db.php';
require __DIR__ . '/../../inc/auth.php';
require __DIR__ . '/../../inc/csrf.php';
require __DIR__ . '/../../inc/helpers.php';

$me = \App\require_user();
// CSRF from header
\App\csrf_verify();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
    \App\json_response(['error'=>'Invalid payload'], 400);
}
$ids = array_map(fn($i)=> (int)($i['id'] ?? 0), $data['items']);
if (!$ids) \App\json_response(['ok'=>true]);

// verify ownership
$in = implode(',', array_fill(0, count($ids), '?'));
$st = pdo()->prepare("SELECT id FROM links WHERE id IN ($in) AND user_id = ?");
$st->execute([...$ids, $me['id']]);
$found = $st->fetchAll();
if (count($found) !== count($ids)) \App\json_response(['error'=>'Ownership mismatch'], 403);

pdo()->beginTransaction();
try {
    $up = pdo()->prepare("UPDATE links SET position = ? WHERE id = ? AND user_id = ?");
    foreach ($data['items'] as $row) {
        $up->execute([(int)$row['position'], (int)$row['id'], $me['id']]);
    }
    pdo()->commit();
    \App\json_response(['ok'=>true]);
} catch (\Throwable $e) {
    pdo()->rollBack();
    \App\json_response(['error'=>'DB error'], 500);
}
