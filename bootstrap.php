<?php
/**
 * bootstrap.php — one-time app bootstrap per request.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);

namespace App;

use Dotenv\Dotenv;

function bootstrap(): void
{
    static $booted = false;
    if ($booted) {
        return;
    }

    $autoload = __DIR__ . '/vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
    }

    if (class_exists(Dotenv::class)) {
        Dotenv::createImmutable(__DIR__)->safeLoad();
    }

    $booted = true;
}
