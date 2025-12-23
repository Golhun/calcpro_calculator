<?php
declare(strict_types=1);

require_once __DIR__ . '/canonical.php';
require_once __DIR__ . '/sign.php';
require_once __DIR__ . '/Fingerprint.php';

// Load secret key from environment
$secretKeyB64 = getenv('SECRET_KEY_B64');
if (!$secretKeyB64) {
    fwrite(STDERR, "SECRET_KEY_B64 not set.\n");
    fwrite(STDERR, "PowerShell example:\n");
    fwrite(STDERR, "  \$env:SECRET_KEY_B64=\"PASTE_SECRET_KEY\"\n");
    exit(1);
}

// Generate fingerprint using YOUR logic
$fingerprintHash = Fingerprint::hash();

$payload = [
    'license_id' => 'LIC-LOCAL-001',
    'product_id' => 'calcpro_calculator',
    'status'     => 'ACTIVE',
    'plan'       => 'trial',

    'issued_at'  => gmdate('c'),
    'expires_at' => gmdate('c', strtotime('+60 days')),
    'updates_until' => gmdate('c', strtotime('+6 years')),

    'fingerprint' => [
        'mode' => 'machine',
        'bound' => true,
        'fingerprint_hash'  => $fingerprintHash,
    ],

    'policy' => [
        'check_interval_days' => 30,
        'warn_after_days'     => 180,
        'max_offline_days'    => 365,
    ],
];

// Build signed license (flatten payload per schema and attach signature)
$sig = sign_payload($payload, $secretKeyB64);
$licenseKey = array_merge(['schema_version' => 1], $payload);
$licenseKey['signature_alg'] = $sig['alg'] ?? 'ed25519';
$licenseKey['signature'] = $sig['sig'] ?? ($sig['sig'] ?? null);

// Write license.key where Storage.php expects it
file_put_contents(
    __DIR__ . '/license.key',
    json_encode($licenseKey, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo "License issued successfully\n";
echo "Fingerprint bound: {$fingerprintHash}\n";
