<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

final class Storage
{
    private string $licenseFile;
    private string $stateFile;

    public function __construct(string $licenseFile, string $stateFile)
    {
        $this->licenseFile = $licenseFile;
        $this->stateFile   = $stateFile;
    }

    public function readLicense(): ?array
    {
        $data = json_read_file($this->licenseFile);
        if (!is_array($data)) return null;

        // Backwards-compat: some tools may write a top-level wrapper with 'payload' and 'signature' keys.
        if (isset($data['payload']) && is_array($data['payload'])) {
            $payload = $data['payload'];
            $out = array_merge(['schema_version' => $data['schema_version'] ?? 1], $payload);

            // Normalize signature representation
            if (isset($data['signature'])) {
                if (is_array($data['signature'])) {
                    $out['signature_alg'] = $data['signature']['alg'] ?? null;
                    $out['signature'] = $data['signature']['sig'] ?? null;
                } else {
                    $out['signature'] = $data['signature'];
                }
            }

            $data = $out;
        }

        // Normalize fingerprint key if legacy 'hash' used
        if (isset($data['fingerprint']) && is_array($data['fingerprint'])) {
            if (isset($data['fingerprint']['hash']) && !isset($data['fingerprint']['fingerprint_hash'])) {
                $data['fingerprint']['fingerprint_hash'] = $data['fingerprint']['hash'];
            }
        }

        return $data;
    }

    public function writeLicense(array $license): bool
    {
        return json_write_file($this->licenseFile, $license);
    }

    public function readState(): array
    {
        return json_read_file($this->stateFile) ?? [
            'schema_version' => 1,
            'license_id' => null,
            'product_id' => null,
            'first_activated_at' => null,
            'last_success_check_at' => null,
            'next_check_due_at' => null,
            'last_server_status' => null,
            'last_server_message' => null,
            'locked_to_fingerprint_hash' => null,
            'clock_guard' => [
                'last_seen_time' => null,
                'rollback_count' => 0,
            ],
        ];
    }

    public function writeState(array $state): bool
    {
        return json_write_file($this->stateFile, $state);
    }
}
