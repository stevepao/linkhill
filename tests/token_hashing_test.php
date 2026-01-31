<?php
/**
 * token_hashing_test.php — Token encode/decode and hash round-trip test.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 *
 * Run: php tests/token_hashing_test.php
 */
declare(strict_types=1);
$raw = random_bytes(32);
$tokenForLink = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($raw));
$hash = hash('sha512', $raw, true);

$rawDecoded = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenForLink) . str_repeat('=', (4 - strlen($tokenForLink) % 4) % 4), true);
$hashDecoded = $rawDecoded !== false ? hash('sha512', $rawDecoded, true) : null;

$ok = ($rawDecoded === $raw && $hashDecoded === $hash);
echo $ok ? "OK: token encode/decode and hash round-trip\n" : "FAIL: round-trip mismatch\n";
exit($ok ? 0 : 1);
