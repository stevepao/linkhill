<?php
declare(strict_types=1);
namespace App;

/**
 * Simple file-based rate limiter. Key = action + identifier (e.g. ip or ip+email).
 * Limits: max N attempts per window (e.g. 5 per hour).
 */
function rate_limit_check(string $action, string $identifier, int $maxAttempts = 5, int $windowSeconds = 3600): bool {
    $dir = __DIR__ . '/../storage/rate_limit';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        return true; // allow if storage not writable (fail open for availability)
    }
    $key = hash('sha256', $action . ':' . $identifier);
    $file = $dir . '/' . $key . '.json';
    $now = time();
    $cutoff = $now - $windowSeconds;
    $attempts = [];
    if (is_file($file)) {
        $raw = @file_get_contents($file);
        if ($raw !== false) {
            $data = json_decode($raw, true);
            if (is_array($data['t'] ?? null)) {
                $attempts = array_filter($data['t'], fn($t) => $t > $cutoff);
            }
        }
    }
    if (count($attempts) >= $maxAttempts) {
        return false;
    }
    $attempts[] = $now;
    file_put_contents($file, json_encode(['t' => $attempts]), LOCK_EX);
    return true;
}

function rate_limit_identifier(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return $ip;
}

function rate_limit_identifier_with_email(string $email): string {
    return rate_limit_identifier() . ':' . strtolower(trim($email));
}
