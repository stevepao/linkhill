<?php
/**
 * secrets_loader.php — Populate runtime environment from 1Password Connect.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);

namespace App\Secrets;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use RuntimeException;

/** Relative to project root (bootstrap directory). */
const OP_CONFIG_RELATIVE = '/../op_config';

const CONNECT_CONFIG_FILENAME = 'linkhill_connect.json';

const DEFAULT_TOKEN_FILENAME = 'OP_CONNECT_TOKEN';

/**
 * Load secrets from Connect into $_ENV and putenv(), matching typical Dotenv usage.
 *
 * @param non-empty-string $project_root Absolute path to the app root (same as bootstrap.php directory).
 */
function load(string $project_root): void
{
    $wire = read_connect_wire($project_root);

    $token_path = $wire['op_config_dir'] . DIRECTORY_SEPARATOR . $wire['token_filename'];
    if (!is_readable($token_path)) {
        throw new RuntimeException(
            '[LinkHill] Connect token file not found or unreadable: ' . $token_path
        );
    }

    $raw = file_get_contents($token_path);
    if ($raw === false) {
        throw new RuntimeException('[LinkHill] Failed to read Connect token file.');
    }

    $token = trim($raw);
    if ($token === '') {
        throw new RuntimeException('[LinkHill] Connect token file is empty.');
    }

    $client = new Client([
        'base_uri' => rtrim($wire['connect_base_url'], '/') . '/',
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ],
        'timeout' => $wire['http_timeout_seconds'],
    ]);

    try {
        $vault_id = $wire['vault_id'];
        $item_title = $wire['item_title'];
        $item_id = resolve_item_uuid($client, $vault_id, $item_title);
        $uri = 'v1/vaults/' . rawurlencode($vault_id) . '/items/' . rawurlencode($item_id);
        $response = $client->get($uri);
        /** @var array<string, mixed> $item */
        $item = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    } catch (GuzzleException $e) {
        throw new RuntimeException('[LinkHill] 1Password Connect request failed: ' . $e->getMessage(), 0, $e);
    } catch (JsonException $e) {
        throw new RuntimeException('[LinkHill] Invalid JSON from Connect API.', 0, $e);
    }

    foreach (fields_to_env_map($item) as $name => $value) {
        inject_env($name, $value);
    }
}

/**
 * Read ../op_config/linkhill_connect.json and return validated wiring + resolved op_config directory.
 *
 * @param non-empty-string $project_root
 *
 * @return array{
 *     op_config_dir: non-empty-string,
 *     connect_base_url: non-empty-string,
 *     vault_id: non-empty-string,
 *     item_title: non-empty-string,
 *     token_filename: non-empty-string,
 *     http_timeout_seconds: float
 * }
 */
function read_connect_wire(string $project_root): array
{
    $op_dir = realpath($project_root . OP_CONFIG_RELATIVE);
    if ($op_dir === false || !is_dir($op_dir)) {
        throw new RuntimeException(
            '[LinkHill] op_config directory not found. Expected at ' . $project_root . OP_CONFIG_RELATIVE
        );
    }

    $config_path = $op_dir . DIRECTORY_SEPARATOR . CONNECT_CONFIG_FILENAME;
    if (!is_readable($config_path)) {
        throw new RuntimeException(
            '[LinkHill] Connect config missing or unreadable: ' . $config_path
        );
    }

    $json_raw = file_get_contents($config_path);
    if ($json_raw === false) {
        throw new RuntimeException('[LinkHill] Failed to read Connect config file.');
    }

    try {
        /** @var mixed $decoded */
        $decoded = json_decode($json_raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        throw new RuntimeException('[LinkHill] Invalid JSON in ' . CONNECT_CONFIG_FILENAME . ': ' . $e->getMessage(), 0, $e);
    }

    if (!is_array($decoded)) {
        throw new RuntimeException('[LinkHill] Connect config must be a JSON object.');
    }

    $base_url = trim((string) ($decoded['connect_base_url'] ?? ''));
    $vault_id = trim((string) ($decoded['vault_id'] ?? ''));
    $item_title = trim((string) ($decoded['item_title'] ?? ''));

    if ($base_url === '' || $vault_id === '' || $item_title === '') {
        throw new RuntimeException(
            '[LinkHill] Connect config requires non-empty connect_base_url, vault_id, and item_title.'
        );
    }

    $token_name = trim((string) ($decoded['token_filename'] ?? DEFAULT_TOKEN_FILENAME));
    if ($token_name === '') {
        $token_name = DEFAULT_TOKEN_FILENAME;
    }
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $token_name)) {
        throw new RuntimeException('[LinkHill] token_filename must contain only safe filename characters.');
    }

    $timeout_raw = $decoded['http_timeout_seconds'] ?? 25;
    $timeout = is_numeric($timeout_raw) ? (float) $timeout_raw : 25.0;
    if ($timeout < 1.0 || $timeout > 120.0) {
        throw new RuntimeException('[LinkHill] http_timeout_seconds must be between 1 and 120.');
    }

    return [
        'op_config_dir' => $op_dir,
        'connect_base_url' => $base_url,
        'vault_id' => $vault_id,
        'item_title' => $item_title,
        'token_filename' => $token_name,
        'http_timeout_seconds' => $timeout,
    ];
}

/**
 * @return list<mixed>
 */
function fetch_items_list(Client $client, string $vault_id, array $query): array
{
    $response = $client->get('v1/vaults/' . rawurlencode($vault_id) . '/items', [
        'query' => $query,
    ]);
    $list = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

    return is_array($list) ? $list : [];
}

/**
 * @return non-empty-string
 */
function resolve_item_uuid(Client $client, string $vault_id, string $title): string
{
    $filter = 'title eq "' . str_replace(['\\', '"'], ['\\\\', '\\"'], $title) . '"';
    $lists = [];
    try {
        $lists[] = fetch_items_list($client, $vault_id, ['filter' => $filter]);
        $lists[] = fetch_items_list($client, $vault_id, []);
    } catch (GuzzleException $e) {
        throw new RuntimeException('[LinkHill] Connect list items failed: ' . $e->getMessage(), 0, $e);
    } catch (JsonException $e) {
        throw new RuntimeException('[LinkHill] Invalid JSON listing Connect items.', 0, $e);
    }

    foreach ($lists as $list) {
        foreach ($list as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (($row['title'] ?? null) === $title && !empty($row['id']) && is_string($row['id'])) {
                return $row['id'];
            }
        }
    }

    throw new RuntimeException(
        '[LinkHill] Connect item "' . $title . '" not found in vault ' . $vault_id . '.'
    );
}

/**
 * @param array<string, mixed> $item
 *
 * @return array<string, string>
 */
function fields_to_env_map(array $item): array
{
    $map = [];
    foreach ($item['fields'] ?? [] as $field) {
        if (!is_array($field)) {
            continue;
        }
        $label = trim((string) ($field['label'] ?? ''));
        if ($label === '') {
            continue;
        }
        if (!array_key_exists('value', $field)) {
            continue;
        }
        $value = $field['value'];
        if ($value === null) {
            continue;
        }
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            continue;
        }
        $map[$label] = (string) $value;
    }

    return $map;
}

function inject_env(string $name, string $value): void
{
    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
        return;
    }
    $value = str_replace("\0", '', $value);
    $_ENV[$name] = $value;
    putenv($name . '=' . $value);
}
