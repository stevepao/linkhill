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

const CONNECT_BASE_URL = 'https://1password-bridge.hillwork.org';

const VAULT_ID = 'expbsxowmtwqr6c6tbm36eoxlq';

const ITEM_TITLE = 'linkhill_env';

/** Relative to project root (bootstrap directory). */
const TOKEN_RELATIVE_PATH = '/../op_config/OP_CONNECT_TOKEN';

/**
 * Load secrets from Connect into $_ENV and putenv(), matching typical Dotenv usage.
 *
 * @param non-empty-string $project_root Absolute path to the app root (same as bootstrap.php directory).
 */
function load(string $project_root): void
{
    $token_path = realpath($project_root . TOKEN_RELATIVE_PATH);
    if ($token_path === false || !is_readable($token_path)) {
        throw new RuntimeException(
            '[LinkHill] OP_CONNECT_TOKEN not found or unreadable. Expected file at '
            . $project_root . TOKEN_RELATIVE_PATH
        );
    }

    $raw = file_get_contents($token_path);
    if ($raw === false) {
        throw new RuntimeException('[LinkHill] Failed to read OP_CONNECT_TOKEN file.');
    }

    $token = trim($raw);
    if ($token === '') {
        throw new RuntimeException('[LinkHill] OP_CONNECT_TOKEN file is empty.');
    }

    $client = new Client([
        'base_uri' => rtrim(CONNECT_BASE_URL, '/') . '/',
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ],
        'timeout' => 25,
    ]);

    try {
        $item_id = resolve_item_uuid($client, VAULT_ID, ITEM_TITLE);
        $uri = 'v1/vaults/' . rawurlencode(VAULT_ID) . '/items/' . rawurlencode($item_id);
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
