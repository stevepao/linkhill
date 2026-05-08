<?php
/**
 * bootstrap.php — one-time app bootstrap per request.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);

namespace App;

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

    // Classmap autoload skips files that only define functions; always load explicitly.
    require_once __DIR__ . '/inc/secrets_loader.php';
    \App\Secrets\load(__DIR__);

    $booted = true;
}
