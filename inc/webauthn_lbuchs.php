<?php
/**
 * webauthn_lbuchs.php — WebAuthn implementation (lbuchs/webauthn).
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);
namespace App\WebAuthn;

/**
 * WebAuthn service implementation using lbuchs/WebAuthn.
 * Requires: composer require lbuchs/webauthn
 */
class WebAuthnLbuchs implements WebAuthnServiceInterface {
    private \lbuchs\WebAuthn\WebAuthn $lib;
    private string $origin;

    public function __construct(string $rpName, string $rpId, string $origin, array $allowedAttestationFormats = ['none']) {
        // This file lives in /inc, so project root is one level up.
        $autoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new \RuntimeException('Composer autoload not found. Run: composer install');
        }
        require_once $autoload;
        $this->lib = new \lbuchs\WebAuthn\WebAuthn($rpName, $rpId, $allowedAttestationFormats, true);
        $this->origin = $origin;
    }

    public function getCreationOptions(
        string $userIdBinary,
        string $userName,
        string $userDisplayName,
        array $excludeCredentialIds = [],
        int $timeout = 60,
        string $residentKey = 'preferred',
        string $userVerification = 'preferred'
    ): \stdClass {
        $exclude = [];
        foreach ($excludeCredentialIds as $id) {
            $exclude[] = is_string($id) ? $id : $id;
        }
        $args = $this->lib->getCreateArgs(
            $userIdBinary,
            $userName,
            $userDisplayName,
            $timeout,
            $residentKey === 'required',
            $userVerification === 'required',
            null,
            $exclude
        );
        return $args;
    }

    public function getStoredChallenge(): string {
        $ch = $this->lib->getChallenge();
        return $ch instanceof \lbuchs\WebAuthn\Binary\ByteBuffer ? $ch->getBinaryString() : (string) $ch;
    }

    public function processCreate(
        string $clientDataJSON,
        string $attestationObject,
        string $challengeBinary
    ): \stdClass {
        $challenge = new \lbuchs\WebAuthn\Binary\ByteBuffer($challengeBinary);
        $data = $this->lib->processCreate($clientDataJSON, $attestationObject, $challenge, false, true, false, false);
        $credentialId = $data->credentialId ?? null;
        $credentialIdBin = $credentialId instanceof \lbuchs\WebAuthn\Binary\ByteBuffer
            ? $credentialId->getBinaryString() : (string) $credentialId;
        $out = new \stdClass();
        $out->credentialId = $credentialIdBin;
        $out->credentialPublicKey = $data->credentialPublicKey ?? '';
        $out->signCount = $this->lib->getSignatureCounter() ?? 0;
        $out->aaguid = $data->AAGUID ?? null;
        $out->attestationFormat = $data->attestationFormat ?? null;
        $out->userPresent = $data->userPresent ?? true;
        $out->userVerified = $data->userVerified ?? false;
        $out->backupEligible = $data->isBackupEligible ?? false;
        $out->backedUp = $data->isBackedUp ?? false;
        return $out;
    }

    public function getRequestOptions(
        array $allowCredentialIds = [],
        int $timeout = 60,
        string $userVerification = 'preferred'
    ): \stdClass {
        $ids = [];
        foreach ($allowCredentialIds as $id) {
            $ids[] = is_string($id) ? $id : $id;
        }
        return $this->lib->getGetArgs($ids, $timeout, true, true, true, true, true, $userVerification);
    }

    public function processGet(
        string $clientDataJSON,
        string $authenticatorData,
        string $signature,
        string $credentialPublicKeyPem,
        string $challengeBinary,
        ?int $prevSignCount = null
    ): bool {
        $challenge = new \lbuchs\WebAuthn\Binary\ByteBuffer($challengeBinary);
        return $this->lib->processGet(
            $clientDataJSON,
            $authenticatorData,
            $signature,
            $credentialPublicKeyPem,
            $challenge,
            $prevSignCount,
            false,
            true
        );
    }

    public function getSignatureCounter(): ?int {
        return $this->lib->getSignatureCounter();
    }
}
