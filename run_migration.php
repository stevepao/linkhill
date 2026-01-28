<?php
declare(strict_types=1);
/**
 * Web-runnable migration for hosts without CLI (e.g. IONOS shared hosting).
 * Usage: https://yoursite.com/run_migration.php?key=YOUR_MIGRATION_KEY
 * Set migration_key in config/config.php (see config.example). Delete this file after use.
 */
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/sql/migrate.php';

$cfg = \App\config();
$key = $cfg['migration_key'] ?? '';
$given = isset($_GET['key']) ? (string)$_GET['key'] : '';

header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Migration</title></head><body><pre>';

if ($key === '') {
    echo "Migration key not set. Add 'migration_key' => 'your-secret' to config/config.php, then visit run_migration.php?key=your-secret\n";
    echo "</pre></body></html>";
    exit;
}
if (!hash_equals($key, $given)) {
    echo "Invalid or missing key. Use the URL: run_migration.php?key=YOUR_MIGRATION_KEY\n";
    echo "</pre></body></html>";
    exit;
}

try {
    [$done, $errors] = run_migration();
    if (count($errors) > 0) {
        echo "Migration errors:\n" . implode("\n", $errors) . "\n";
    } else {
        echo "Migration OK. Applied: " . (count($done) ? implode(", ", $done) : "nothing (already up to date)") . "\n";
    }
} catch (Throwable $e) {
    echo "Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "\n";
}

echo "\n** Delete this file (run_migration.php) after use. **\n";
echo "</pre></body></html>";
