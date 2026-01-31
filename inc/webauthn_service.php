<?php
/**
 * webauthn_service.php — WebAuthn service interface.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);
namespace App\WebAuthn;

/**
 * Minimal interface for WebAuthn (registration + assertion). Implementations can wrap lbuchs/WebAuthn or another library.
 */
interface WebAuthnServiceInterface {
    /** Return PublicKeyCredentialCreationOptions (object ready for JSON). */
    public function getCreationOptions(
        string $userIdBinary,
        string $userName,
        string $userDisplayName,
        array $excludeCredentialIds = [],
        int $timeout = 60,
        string $residentKey = 'preferred',
        string $userVerification = 'preferred'
    ): \stdClass;

    /** Return the challenge (binary) to store in session for processCreate. */
    public function getStoredChallenge(): string;

    /** Process attestation; return credential data to store (credentialId binary, publicKey PEM, signCount, etc.). */
    public function processCreate(
        string $clientDataJSON,
        string $attestationObject,
        string $challengeBinary
    ): \stdClass;

    /** Return PublicKeyCredentialRequestOptions (object ready for JSON). */
    public function getRequestOptions(
        array $allowCredentialIds = [],
        int $timeout = 60,
        string $userVerification = 'preferred'
    ): \stdClass;

    /** Process assertion; return true if valid. New sign count available via getSignatureCounter(). */
    public function processGet(
        string $clientDataJSON,
        string $authenticatorData,
        string $signature,
        string $credentialPublicKeyPem,
        string $challengeBinary,
        ?int $prevSignCount = null
    ): bool;

    public function getSignatureCounter(): ?int;
}
