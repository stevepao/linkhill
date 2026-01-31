<?php
/**
 * email_verification.php — Email verification token lifecycle.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);
namespace App;

/**
 * Email verification token lifecycle: create, find by token, mark used.
 * Same pattern as password reset: store SHA-512 hash; raw token sent once in email.
 */
function email_verification_create(int $userId, int $expireMinutes = 60): string {
    $raw = random_bytes(32);
    $tokenForLink = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($raw));
    $hash = hash('sha512', $raw, true);
    $expires = gmdate('Y-m-d H:i:s', time() + $expireMinutes * 60);
    $stmt = pdo()->prepare("INSERT INTO email_verifications (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $hash, $expires]);
    return $tokenForLink;
}

/** @return array{user_id: int, id: int}|null */
function email_verification_find_valid(string $rawToken): ?array {
    $rawToken = trim($rawToken);
    $rawToken = str_replace(["\xE2\x80\x93", "\xE2\x80\x90", "\xC2\xAD"], '-', $rawToken);
    $rawToken = preg_replace('/[^A-Za-z0-9_-]/', '', $rawToken);
    if ($rawToken === '') return null;
    $raw = base64_decode(str_replace(['-', '_'], ['+', '/'], $rawToken) . str_repeat('=', (4 - strlen($rawToken) % 4) % 4), true);
    if ($raw === false || strlen($raw) !== 32) return null;
    $hash = hash('sha512', $raw, true);
    $stmt = pdo()->prepare("SELECT id, user_id FROM email_verifications WHERE token_hash = ? AND used_at IS NULL AND expires_at > UTC_TIMESTAMP()");
    $stmt->execute([$hash]);
    $row = $stmt->fetch();
    return $row ? ['user_id' => (int)$row['user_id'], 'id' => (int)$row['id']] : null;
}

function email_verification_mark_used(int $verificationId): void {
    pdo()->prepare("UPDATE email_verifications SET used_at = NOW() WHERE id = ?")->execute([$verificationId]);
}

function email_verification_set_user_verified(int $userId): void {
    pdo()->prepare("UPDATE users SET email_verified_at = NOW() WHERE id = ?")->execute([$userId]);
}
