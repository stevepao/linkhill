<?php
declare(strict_types=1);
/**
 * Idempotent migration script: safe to run multiple times.
 * Applies: users columns, email UNIQUE, password_resets table, webauthn_credentials table,
 * backfill webauthn_user_handle for existing users.
 * Run from CLI: php sql/migrate.php
 * Or via browser (protect in prod): ensure docroot doesn't serve sql/ or run from project root.
 */
require_once dirname(__DIR__) . '/inc/db.php';

function column_exists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare(
        "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetch();
}

function table_exists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare(
        "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
    );
    $stmt->execute([$table]);
    return (bool) $stmt->fetch();
}

function index_exists(PDO $pdo, string $table, string $indexName): bool {
    $stmt = $pdo->prepare(
        "SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?"
    );
    $stmt->execute([$table, $indexName]);
    return (bool) $stmt->fetch();
}

/**
 * Run migration. Returns [array $done, array $errors]. Idempotent.
 */
function run_migration(): array {
    $pdo = \App\pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $done = [];
    $errors = [];

// --- users: add columns if missing (MySQL 5.7 compatible: no ADD COLUMN IF NOT EXISTS)
foreach (
    [
        ['password_updated_at', "ADD COLUMN password_updated_at DATETIME NULL AFTER password_hash"],
        ['webauthn_user_handle', "ADD COLUMN webauthn_user_handle VARBINARY(32) NULL UNIQUE"],
        ['last_login_at', "ADD COLUMN last_login_at DATETIME NULL AFTER password_updated_at"],
        ['user_session_version', "ADD COLUMN user_session_version INT UNSIGNED NOT NULL DEFAULT 0 AFTER last_login_at"],
    ] as $col
) {
    if (!column_exists($pdo, 'users', $col[0])) {
        try {
            $pdo->exec("ALTER TABLE users " . $col[1]);
            $done[] = "users.{$col[0]}";
        } catch (Throwable $e) {
            $errors[] = "users.{$col[0]}: " . $e->getMessage();
        }
    }
}

// --- users: ensure email UNIQUE (may already exist)
try {
    $stmt = $pdo->query("SHOW INDEX FROM users WHERE Column_name = 'email' AND Non_unique = 0");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD UNIQUE KEY unique_email (email)");
        $done[] = "users.unique_email";
    }
} catch (Throwable $e) {
    $errors[] = "users.unique_email: " . $e->getMessage();
}

// --- password_resets table
if (!table_exists($pdo, 'password_resets')) {
    try {
        $pdo->exec("
            CREATE TABLE password_resets (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                token_hash VARBINARY(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_expires (user_id, expires_at),
                CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $done[] = "table password_resets";
    } catch (Throwable $e) {
        $errors[] = "password_resets: " . $e->getMessage();
    }
}

// --- webauthn_credentials table
if (!table_exists($pdo, 'webauthn_credentials')) {
    try {
        $pdo->exec("
            CREATE TABLE webauthn_credentials (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                credential_id VARBINARY(255) NOT NULL UNIQUE,
                public_key TEXT NOT NULL,
                sign_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
                aaguid BINARY(16) NULL,
                transports VARCHAR(255) NULL,
                attestation_format VARCHAR(64) NULL,
                nickname VARCHAR(100) NULL,
                backup_eligible TINYINT(1) NOT NULL DEFAULT 0,
                backup_state TINYINT(1) NOT NULL DEFAULT 0,
                uv_initialized TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_used_at DATETIME NULL,
                INDEX idx_wc_user (user_id),
                CONSTRAINT fk_wc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $done[] = "table webauthn_credentials";
    } catch (Throwable $e) {
        $errors[] = "webauthn_credentials: " . $e->getMessage();
    }
}

// --- links: add description (optional blurb for card-style display)
if (!column_exists($pdo, 'links', 'description')) {
    try {
        $pdo->exec("ALTER TABLE links ADD COLUMN description TEXT NULL AFTER url");
        $done[] = "links.description";
    } catch (Throwable $e) {
        $errors[] = "links.description: " . $e->getMessage();
    }
}

// --- backfill webauthn_user_handle for users where NULL (only if column exists)
if (column_exists($pdo, 'users', 'webauthn_user_handle')) {
try {
    $stmt = $pdo->query("SELECT id FROM users WHERE webauthn_user_handle IS NULL");
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($rows as $userId) {
        $handle = random_bytes(32);
        $up = $pdo->prepare("UPDATE users SET webauthn_user_handle = ? WHERE id = ?");
        $up->execute([$handle, $userId]);
    }
    if (count($rows) > 0) {
        $done[] = "backfill webauthn_user_handle (" . count($rows) . " users)";
    }
} catch (Throwable $e) {
    $errors[] = "backfill webauthn_user_handle: " . $e->getMessage();
}
}

    return [$done, $errors];
}

// CLI entrypoint
if (PHP_SAPI === 'cli') {
    [$done, $errors] = run_migration();
    if (count($errors) > 0) {
        fwrite(STDERR, "Migration errors:\n" . implode("\n", $errors) . "\n");
        exit(1);
    }
    echo "Migration OK. Applied: " . (count($done) ? implode(", ", $done) : "nothing (already up to date)") . "\n";
}
