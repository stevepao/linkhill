<?php
/**
 * SecretsLoader — Populate runtime environment from 1Password Connect.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);

namespace App\Config;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use RuntimeException;

final class SecretsLoader
{
    private const CONNECT_BASE_URL = 'https://1password-bridge.hillwork.org';

    private const VAULT_ID = 'expbsxowmtwqr6c6tbm36eoxlq';

    private const ITEM_TITLE = 'linkhill_env';

    private const TOKEN_RELATIVE_PATH = '/../op_config/OP_CONNECT_TOKEN';

    /**
     * Load secrets from Connect into $_ENV and putenv(), matching typical Dotenv usage.
     *
     * @param non-empty-string $projectRoot Absolute path to the app root (same as bootstrap.php directory).
     */
    public static function load(string $projectRoot): void
    {
        $tokenPath = realpath($projectRoot . self::TOKEN_RELATIVE_PATH);
        if ($tokenPath === false || !is_readable($tokenPath)) {
            throw new RuntimeException(
                '[LinkHill] OP_CONNECT_TOKEN not found or unreadable. Expected file at '
                . $projectRoot . self::TOKEN_RELATIVE_PATH
            );
        }

        $raw = file_get_contents($tokenPath);
        if ($raw === false) {
            throw new RuntimeException('[LinkHill] Failed to read OP_CONNECT_TOKEN file.');
        }

        $token = trim($raw);
        if ($token === '') {
            throw new RuntimeException('[LinkHill] OP_CONNECT_TOKEN file is empty.');
        }

        $client = new Client([
            'base_uri' => rtrim(self::CONNECT_BASE_URL, '/') . '/',
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ],
            'timeout' => 25,
        ]);

        try {
            $itemId = self::resolveItemUuid($client, self::VAULT_ID, self::ITEM_TITLE);
            $uri = 'v1/vaults/' . rawurlencode(self::VAULT_ID) . '/items/' . rawurlencode($itemId);
            $response = $client->get($uri);
            /** @var array<string, mixed> $item */
            $item = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (GuzzleException $e) {
            throw new RuntimeException('[LinkHill] 1Password Connect request failed: ' . $e->getMessage(), 0, $e);
        } catch (JsonException $e) {
            throw new RuntimeException('[LinkHill] Invalid JSON from Connect API.', 0, $e);
        }

        foreach (self::fieldsToEnvMap($item) as $name => $value) {
            self::injectEnv($name, $value);
        }
    }

    /**
     * @return list<mixed>
     */
    private static function fetchItemsList(Client $client, string $vaultId, array $query): array
    {
        $response = $client->get('v1/vaults/' . rawurlencode($vaultId) . '/items', [
            'query' => $query,
        ]);
        $list = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        return is_array($list) ? $list : [];
    }

    /**
     * @return non-empty-string
     */
    private static function resolveItemUuid(Client $client, string $vaultId, string $title): string
    {
        $filter = 'title eq "' . str_replace(['\\', '"'], ['\\\\', '\\"'], $title) . '"';
        $lists = [];
        try {
            $lists[] = self::fetchItemsList($client, $vaultId, ['filter' => $filter]);
            $lists[] = self::fetchItemsList($client, $vaultId, []);
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
            '[LinkHill] Connect item "' . $title . '" not found in vault ' . $vaultId . '.'
        );
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array<string, string>
     */
    private static function fieldsToEnvMap(array $item): array
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

    private static function injectEnv(string $name, string $value): void
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            return;
        }
        $value = str_replace("\0", '', $value);
        $_ENV[$name] = $value;
        putenv($name . '=' . $value);
    }
}
