<?php
declare(strict_types=1);

/**
 * license_api.php
 * Licensing Engine Server API (v1)
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? null;

if (!$action) {
    respondError('Missing action', 'INVALID_REQUEST');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    respondError('Invalid JSON body', 'INVALID_REQUEST');
}

try {
    match ($action) {
        'activate'         => handleActivate($input),
        'validate'         => handleValidate($input),
        'updates'          => handleUpdates($input),
        'transfer_request' => handleTransferRequest($input),
        default            => respondError('Unknown action', 'INVALID_REQUEST'),
    };
} catch (Throwable $e) {
    respondError('Internal server error', 'INTERNAL_ERROR');
}

/* -------------------- HANDLERS -------------------- */

function handleActivate(array $in): void
{
    requireFields($in, ['license_id', 'product_id', 'fingerprint_hash']);

    $license = loadLicense($in['license_id'], $in['product_id']);

    if (!$license) {
        respondError('License not found', 'LICENSE_NOT_FOUND');
    }

    if (in_array($license['status'], ['REVOKED', 'SUSPENDED', 'EXPIRED'], true)) {
        respondOk([
            'status'  => $license['status'],
            'message' => 'License not allowed to activate'
        ]);
    }

    if ($license['fingerprint_bound']) {
        if ($license['fingerprint_hash'] !== $in['fingerprint_hash']) {
            respondError('License bound to another machine', 'FINGERPRINT_MISMATCH');
        }
    } else {
        bindFingerprint($license['license_id'], $in['fingerprint_hash']);
    }

    logCheckin($license['license_id'], $in['fingerprint_hash'], 'activate', $license['status']);

    respondOk([
        'server_time'     => nowUtc(),
        'status'          => $license['status'],
        'message'         => 'Activated',
        'license_payload' => buildSignedPayload($license, $in['fingerprint_hash'])
    ]);
}

function handleValidate(array $in): void
{
    requireFields($in, ['license_id', 'product_id', 'fingerprint_hash']);

    $license = loadLicense($in['license_id'], $in['product_id']);
    if (!$license) {
        respondError('License not found', 'LICENSE_NOT_FOUND');
    }

    if ($license['fingerprint_hash'] !== $in['fingerprint_hash']) {
        respondError('Fingerprint mismatch', 'FINGERPRINT_MISMATCH');
    }

    logCheckin($license['license_id'], $in['fingerprint_hash'], 'validate', $license['status']);

    respondOk([
        'server_time'    => nowUtc(),
        'status'         => $license['status'],
        'message'        => 'OK',
        'policy'         => extractPolicy($license),
        'updates_until'  => $license['updates_until'],
        'latest_version' => loadLatestVersion($license['product_id'])
    ]);
}

function handleUpdates(array $in): void
{
    requireFields($in, ['license_id', 'product_id', 'fingerprint_hash']);

    $license = loadLicense($in['license_id'], $in['product_id']);
    if (!$license) {
        respondError('License not found', 'LICENSE_NOT_FOUND');
    }

    $latest = loadLatestVersion($license['product_id']);

    respondOk([
        'updates_until' => $license['updates_until'],
        'eligible'      => $latest
            ? strtotime($latest['release_date']) <= strtotime($license['updates_until'])
            : false,
        'latest_version' => $latest
    ]);
}

function handleTransferRequest(array $in): void
{
    requireFields($in, ['license_id', 'product_id', 'from_fingerprint_hash', 'to_fingerprint_hash']);

    $requestId = 'TR-' . strtoupper(bin2hex(random_bytes(4)));

    $stmt = db()->prepare(
        'INSERT INTO license_transfers
         (request_id, license_id, from_fingerprint_hash, to_fingerprint_hash, reason, contact_name, contact_email, contact_phone)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $stmt->execute([
        $requestId,
        $in['license_id'],
        $in['from_fingerprint_hash'],
        $in['to_fingerprint_hash'],
        $in['reason'] ?? null,
        $in['contact']['name'] ?? null,
        $in['contact']['email'] ?? null,
        $in['contact']['phone'] ?? null
    ]);

    respondOk([
        'request_id' => $requestId,
        'status'     => 'OPEN',
        'message'    => 'Transfer request received'
    ]);
}

/* -------------------- HELPERS -------------------- */

function loadLicense(string $licenseId, string $productId): ?array
{
    $stmt = db()->prepare('SELECT * FROM licenses WHERE license_id = ? AND product_id = ?');
    $stmt->execute([$licenseId, $productId]);
    return $stmt->fetch() ?: null;
}

function bindFingerprint(string $licenseId, string $hash): void
{
    $stmt = db()->prepare(
        'UPDATE licenses SET fingerprint_hash = ?, fingerprint_bound = 1 WHERE license_id = ?'
    );
    $stmt->execute([$hash, $licenseId]);
}

function extractPolicy(array $l): array
{
    return [
        'check_interval_days' => (int)$l['check_interval_days'],
        'warn_after_days'     => (int)$l['warn_after_days'],
        'max_offline_days'    => (int)$l['max_offline_days']
    ];
}

function loadLatestVersion(string $productId): ?array
{
    $stmt = db()->prepare(
        'SELECT version, release_date, download_url
         FROM product_versions
         WHERE product_id = ?
         ORDER BY release_date DESC
         LIMIT 1'
    );
    $stmt->execute([$productId]);
    return $stmt->fetch() ?: null;
}

function logCheckin(string $licenseId, string $fp, string $action, string $status): void
{
    $stmt = db()->prepare(
        'INSERT INTO license_checkins
         (license_id, fingerprint_hash, action, server_status)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$licenseId, $fp, $action, $status]);
}

function buildSignedPayload(array $license, string $fp): array
{
    // Placeholder: real signing happens after schema is frozen
    $license['fingerprint_hash'] = $fp;
    return $license;
}

function requireFields(array $in, array $fields): void
{
    foreach ($fields as $f) {
        if (!array_key_exists($f, $in)) {
            respondError("Missing field: {$f}", 'INVALID_REQUEST');
        }
    }
}

function respondOk(array $data): never
{
    echo json_encode(['ok' => true, 'data' => $data], JSON_PRETTY_PRINT);
    exit;
}

function respondError(string $msg, string $code): never
{
    echo json_encode(['ok' => false, 'error' => $msg, 'code' => $code], JSON_PRETTY_PRINT);
    exit;
}

function nowUtc(): string
{
    return gmdate('c');
}
