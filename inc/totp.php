<?php
/**
 * totp.php — TOTP helpers.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);
namespace App\Totp;

/** RFC4648 Base32 (no padding) */
function base32_encode(string $data): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    foreach (str_split($data) as $c) {
        $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
    }
    $chunks = str_split($bits, 5);
    $out = '';
    foreach ($chunks as $chunk) {
        if (strlen($chunk) < 5) $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
        $out .= $alphabet[bindec($chunk)];
    }
    return $out;
}

function base32_decode(string $b32): string {
    $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32));
    $alphabet = array_flip(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'));
    $bits = '';
    foreach (str_split($b32) as $ch) {
        if (!isset($alphabet[$ch])) continue;
        $bits .= str_pad(decbin($alphabet[$ch]), 5, '0', STR_PAD_LEFT);
    }
    $out = '';
    $bytes = str_split($bits, 8);
    foreach ($bytes as $byte) {
        if (strlen($byte) < 8) continue;
        $out .= chr(bindec($byte));
    }
    return $out;
}

function random_base32_secret(int $bytes = 20): string {
    return base32_encode(random_bytes($bytes));
}

function hotp(string $secret, int $counter, int $digits = 6, string $algo = 'sha1'): string {
    $key = base32_decode($secret);
    $binCounter = pack('N*', 0) . pack('N*', $counter);
    $hmac = hash_hmac($algo, $binCounter, $key, true);
    $offset = ord($hmac[19]) & 0x0F;
    $code = ((ord($hmac[$offset]) & 0x7F) << 24) |
            ((ord($hmac[$offset + 1]) & 0xFF) << 16) |
            ((ord($hmac[$offset + 2]) & 0xFF) << 8) |
            (ord($hmac[$offset + 3]) & 0xFF);
    $hotp = $code % (10 ** $digits);
    return str_pad((string)$hotp, $digits, '0', STR_PAD_LEFT);
}

function totp(string $secret, ?int $time = null, int $digits = 6, int $period = 30, string $algo = 'sha1'): string {
    $time ??= time();
    $counter = intdiv($time, $period);
    return hotp($secret, $counter, $digits, $algo);
}

function verify(string $secret, string $code, int $window = 1, int $digits = 6, int $period = 30, string $algo = 'sha1'): bool {
    $now = time();
    $code = preg_replace('/\s+/', '', $code);
    for ($w = -$window; $w <= $window; $w++) {
        if (hash_equals(totp($secret, $now + ($w * $period), $digits, $period, $algo), $code)) {
            return true;
        }
    }
    return false;
}

function provisioning_uri(string $secret, string $userLabel, string $issuer): string {
    $label  = rawurlencode($issuer) . ':' . rawurlencode($userLabel);
    $issuer = rawurlencode($issuer);
    $params = http_build_query([
        'secret'   => $secret,
        'issuer'   => $issuer,
        'digits'   => 6,
        'period'   => 30,
        'algorithm'=> 'SHA1',
    ], '', '&', PHP_QUERY_RFC3986);
    return "otpauth://totp/{$label}?{$params}";
}
