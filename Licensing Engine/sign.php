<?php
declare(strict_types=1);

require_once __DIR__ . '/canonical.php';

function sign_payload(array $payload, string $secretKeyB64): array
{
    if (!extension_loaded('sodium')) {
        throw new RuntimeException('ext-sodium is required for Ed25519 signing.');
    }

    $secretKey = base64_decode($secretKeyB64, true);
    if ($secretKey === false || strlen($secretKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
        throw new RuntimeException('Invalid secret key.');
    }

    $canon = canonicalize($payload);
    $sigBin = sodium_crypto_sign_detached($canon, $secretKey);
    $sigB64 = base64_encode($sigBin);

    return [
        'alg' => 'ed25519',
        'kid' => 'lic-v1',
        'sig' => $sigB64,
    ];
}

/**
 * Verify a flattened license's signature using the provided public key (base64).
 * Returns true when signature is valid, false otherwise.
 */
function verify_payload_signature(array $license, string $publicKeyB64): bool
{
    if (!extension_loaded('sodium')) {
        throw new RuntimeException('ext-sodium is required for Ed25519 verification.');
    }

    $pubKey = base64_decode($publicKeyB64, true);
    if ($pubKey === false || strlen($pubKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
        throw new RuntimeException('Invalid public key.');
    }

    // Extract signature (support both array {alg,kid,sig} or a plain base64 string)
    $sigB64 = null;
    if (isset($license['signature'])) {
        if (is_array($license['signature'])) {
            $sigB64 = $license['signature']['sig'] ?? null;
        } else {
            $sigB64 = $license['signature'];
        }
    }

    if (!$sigB64) return false;

    $sig = base64_decode($sigB64, true);
    if ($sig === false) return false;

    // Reconstruct the payload that was signed: the license without signature-related fields and schema_version
    $payload = $license;
    unset($payload['signature'], $payload['signature_alg'], $payload['signature_kid'], $payload['schema_version']);

    $canon = canonicalize($payload);

    return sodium_crypto_sign_verify_detached($sig, $canon, $pubKey);
}
