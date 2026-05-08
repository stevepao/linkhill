<?php
/**
 * config.php — App configuration.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);

namespace App;

function env_str(string $key, string $default = ''): string
{
    $value = $_ENV[$key] ?? $default;
    return is_string($value) ? $value : $default;
}

function env_int(string $key, int $default): int
{
    $value = $_ENV[$key] ?? null;
    if ($value === null || $value === '') {
        return $default;
    }
    return (int)$value;
}

function env_bool(string $key, bool $default): bool
{
    $value = $_ENV[$key] ?? null;
    if ($value === null || $value === '') {
        return $default;
    }
    $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    return $parsed ?? $default;
}

return [
  'app_name'        => env_str('APP_NAME', 'LinkHill'),
  // Leave empty to auto-detect base URL at runtime; or set 'https://links.example.com'
  'base_url'        => env_str('APP_BASE_URL', ''),
  'db' => [
    'host'    => env_str('DB_HOST'),
    'dbname'  => env_str('DB_NAME'),
    'user'    => env_str('DB_USER'),
    'pass'    => env_str('DB_PASS'),
    'charset' => env_str('DB_CHARSET', 'utf8mb4'),
  ],
  'session_name'    => env_str('SESSION_NAME', 'linkhub_sess'),
  // When on HTTPS in production, keep true; for local dev without HTTPS you may set false
  'cookie_secure'   => env_bool('COOKIE_SECURE', true),
  'cookie_samesite' => env_str('COOKIE_SAMESITE', 'Lax'),
  'password_cost'   => env_int('PASSWORD_COST', 12),
  'timezone'        => env_str('APP_TIMEZONE', 'America/Los_Angeles'),

  // One-time web migration (run_migration.php?key=...). Set a secret, run once, then delete run_migration.php.
  'migration_key'  => env_str('MIGRATION_KEY'),

  // DEV_MODE: if true, password reset links are logged instead of emailed when SMTP not configured
  'dev_mode'        => env_bool('DEV_MODE', false),

  // SMTP for password reset and notifications (leave empty to skip sending; in dev_mode links are logged)
  'smtp' => [
    'host'       => env_str('SMTP_HOST'),
    'port'       => env_int('SMTP_PORT', 587),
    'secure'     => env_str('SMTP_SECURE', 'tls'),       // 'tls' or 'ssl'
    'user'       => env_str('SMTP_USER'),
    'pass'       => env_str('SMTP_PASS'),
    'from'       => env_str('SMTP_FROM', 'noreply@example.com'),
    'from_name'  => env_str('SMTP_FROM_NAME', 'LinkHill'),
  ],

  // WebAuthn / Passkeys
  'webauthn' => [
    'rp_id'   => env_str('WEBAUTHN_RP_ID'),   // Effective domain, e.g. 'example.com' or 'localhost' for dev
    'rp_name' => env_str('WEBAUTHN_RP_NAME', 'LinkHill'),
    'origin'  => env_str('WEBAUTHN_ORIGIN'),   // Full origin, e.g. 'https://example.com' or 'https://localhost:8443' for dev
  ],
];

