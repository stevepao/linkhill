<?php
declare(strict_types=1);
use function App\{pdo, require_user, json_response};
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/auth.php';
require __DIR__ . '/../inc/helpers.php';
\App\session_boot();

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
$user = \App\require_user();

$stmt = pdo()->prepare("
    SELECT id, credential_id, nickname, sign_count, created_at, last_used_at
    FROM webauthn_credentials
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$user['id']]);
$list = [];
while ($row = $stmt->fetch()) {
    $list[] = [
        'id' => (int)$row['id'],
        'credentialIdMask' => bin2hex(substr($row['credential_id'], 0, 4)) . '…',
        'nickname' => $row['nickname'],
        'signCount' => (int)$row['sign_count'],
        'createdAt' => $row['created_at'],
        'lastUsedAt' => $row['last_used_at'],
    ];
}
json_response(['credentials' => $list]);
