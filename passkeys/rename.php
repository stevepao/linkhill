<?php
/**
 * rename.php — Rename passkey.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);
use function App\{pdo, require_user, json_response};
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/auth.php';
require __DIR__ . '/../inc/helpers.php';
require __DIR__ . '/../inc/csrf.php';
\App\session_boot();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
\App\csrf_verify();
$user = \App\require_user();

$input = json_decode((string)file_get_contents('php://input'), true);
$id = isset($input['id']) ? (int)$input['id'] : 0;
$nickname = isset($input['nickname']) && is_string($input['nickname']) ? substr(trim($input['nickname']), 0, 100) : '';

$stmt = pdo()->prepare("UPDATE webauthn_credentials SET nickname = ? WHERE id = ? AND user_id = ?");
$stmt->execute([$nickname ?: null, $id, $user['id']]);
if ($stmt->rowCount() === 0) {
    json_response(['error' => 'Not found'], 404);
}
json_response(['ok' => true]);
