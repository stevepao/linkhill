<?php
/**
 * db.php — Database and config helpers.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);
namespace App;

function config(): array {
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/../config/config.php';
        date_default_timezone_set($cfg['timezone'] ?? 'UTC');
    }
    return $cfg;
}

/** @return \PDO */
function pdo(): \PDO {
    static $pdo = null;
    if ($pdo instanceof \PDO) return $pdo;
    $c = config()['db'];
    $dsn = "mysql:host={$c['host']};dbname={$c['dbname']};charset={$c['charset']}";
    $pdo = new \PDO($dsn, $c['user'], $c['pass'], [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}

function now(): string {
    return date('Y-m-d H:i:s');
}

/** Detect base URL if not set in config */
function base_url(): string {
    $cfg = config();
    if (!empty($cfg['base_url'])) return rtrim($cfg['base_url'], '/');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
             (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $scheme = $https ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/.');
    return $scheme . '://' . $host . ($dir === '' ? '' : $dir);
}
