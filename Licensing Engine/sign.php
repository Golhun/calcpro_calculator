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
