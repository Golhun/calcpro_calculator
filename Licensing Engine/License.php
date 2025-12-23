<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Storage.php';
require_once __DIR__ . '/Fingerprint.php';
require_once __DIR__ . '/Policy.php';
require_once __DIR__ . '/TimeGuard.php';
require_once __DIR__ . '/HttpClient.php';
require_once __DIR__ . '/Validator.php';

final class License
{
    private array $cfg;
    private Storage $storage;

    private ?array $license = null;
    private array $state;

    private string $fingerprintHash;

    private function __construct(array $cfg)
    {
        $this->cfg = $cfg;
        $this->storage = new Storage($cfg['license_file'], $cfg['state_file']);
        $this->state = $this->storage->readState();
        $this->fingerprintHash = Fingerprint::hash();
    }

    public static function boot(string $configFile): self
    {
        $cfg = require $configFile;
        if (!is_array($cfg)) {
            throw new RuntimeException('Invalid client_config.php');
        }
        return new self($cfg);
    }

    /**
     * Load license from disk and run core checks.
     * If server enabled and due, perform a validate check (best effort).
     */
    public function assertValid(): void
    {
        // Clock guard first (prevents obvious rollback abuse)
        TimeGuard::assertNoRollback($this->state, (int)($this->cfg['clock_guard']['max_rollback_count'] ?? 3));
        $this->storage->writeState($this->state);

        $this->license = $this->storage->readLicense();
        if (!$this->license) {
            throw new RuntimeException('No license.key found. Please activate the software.');
        }

        // Core validation (structure, product, status, expiry)
        Validator::validateCore($this->license, (string)$this->cfg['product_id']);

        // Fingerprint validation
        Validator::validateFingerprint($this->license, $this->fingerprintHash);

        // Offline policy enforcement
        $policy = Policy::fromLicenseOrDefaults($this->license, $this->cfg['defaults'] ?? []);
        $offline = Validator::computeOfflineState($policy, $this->state);

        if ($offline['hard_block'] === true) {
            throw new RuntimeException('License requires internet validation. Offline limit exceeded.');
        }

        // Optional server validation when due
        if (($this->cfg['server']['enabled'] ?? false) === true && $offline['due'] === true) {
            $this->tryServerValidate();
        }

        // Persist updated state after checks
        $this->storage->writeState($this->state);

        // If warn, apps can show a banner. We do not block.
        // You can read warnings via getWarnings()
    }

    public function getWarnings(): array
    {
        if (!$this->license) return [];

        $policy = Policy::fromLicenseOrDefaults($this->license, $this->cfg['defaults'] ?? []);
        $offline = Validator::computeOfflineState($policy, $this->state);

        $w = [];
        if ($offline['warn'] === true) {
            $days = $offline['offline_days'];
            $w[] = $days === null
                ? 'Validation is due. Please connect to the internet.'
                : "Validation overdue. Offline days: {$days}. Please connect to the internet.";
        }
        if (($this->cfg['server']['enabled'] ?? false) !== true) {
            $w[] = 'Online validation is disabled. Remote revocation and transfers are not available.';
        }
        return $w;
    }

    public function canReceiveUpdates(string $appReleaseDateRfc3339): bool
    {
        if (!$this->license) {
            $this->license = $this->storage->readLicense();
        }
        if (!$this->license) return false;

        return Validator::canReceiveUpdates($this->license, $appReleaseDateRfc3339);
    }

    /**
     * Activate by fetching a server-signed license payload and writing license.key.
     * This is typically used on first install.
     */
    public function activateOnline(string $licenseId, array $machineInfo = []): void
    {
        if (($this->cfg['server']['enabled'] ?? false) !== true) {
            throw new RuntimeException('Server activation is disabled.');
        }

        $base = (string)($this->cfg['server']['base_url'] ?? '');
        if ($base === '') throw new RuntimeException('Server base_url missing.');

        $payload = [
            'license_id' => $licenseId,
            'product_id' => (string)$this->cfg['product_id'],
            'fingerprint_hash' => $this->fingerprintHash,
            'machine' => $machineInfo,
        ];

        $url = $base . '?action=activate';

        $res = HttpClient::postJson($url, $payload, (int)($this->cfg['server']['timeout_seconds'] ?? 8), [
            'X-Product-Id: ' . (string)$this->cfg['product_id'],
            'X-Client-Version: 1.0.0',
        ]);

        $json = $res['json'];
        if (!($json['ok'] ?? false)) {
            throw new RuntimeException('Activation failed: ' . safe_string($json['error'] ?? 'Unknown error'));
        }

        $data = $json['data'] ?? [];
        $licensePayload = $data['license_payload'] ?? null;
        if (!is_array($licensePayload)) {
            throw new RuntimeException('Activation response missing license_payload.');
        }

        // Store license
        $this->storage->writeLicense($licensePayload);

        // Update state
        $this->state['license_id'] = $licensePayload['license_id'] ?? $licenseId;
        $this->state['product_id'] = (string)$this->cfg['product_id'];

        if (!$this->state['first_activated_at']) {
            $this->state['first_activated_at'] = utc_now_rfc3339();
        }

        $this->state['locked_to_fingerprint_hash'] = $this->fingerprintHash;
        $this->state['last_success_check_at'] = utc_now_rfc3339();

        $policy = Policy::fromLicenseOrDefaults($licensePayload, $this->cfg['defaults'] ?? []);
        $this->state['next_check_due_at'] = ts_to_rfc3339(time() + ($policy->checkIntervalDays * 86400));

        $this->state['last_server_status'] = safe_string($data['status'] ?? 'ACTIVE');
        $this->state['last_server_message'] = safe_string($data['message'] ?? 'Activated');

        $this->storage->writeState($this->state);

        // Refresh in-memory
        $this->license = $licensePayload;
    }

    /**
     * Creates a transfer request on the server.
     */
    public function requestTransferOnline(string $licenseId, string $toFingerprintHash, string $reason = '', array $contact = []): array
    {
        if (($this->cfg['server']['enabled'] ?? false) !== true) {
            throw new RuntimeException('Server transfer requests are disabled.');
        }

        $base = (string)($this->cfg['server']['base_url'] ?? '');
        if ($base === '') throw new RuntimeException('Server base_url missing.');

        $payload = [
            'license_id' => $licenseId,
            'product_id' => (string)$this->cfg['product_id'],
            'from_fingerprint_hash' => $this->fingerprintHash,
            'to_fingerprint_hash' => $toFingerprintHash,
            'reason' => $reason,
            'contact' => $contact,
        ];

        $url = $base . '?action=transfer_request';

        $res = HttpClient::postJson($url, $payload, (int)($this->cfg['server']['timeout_seconds'] ?? 8), [
            'X-Product-Id: ' . (string)$this->cfg['product_id'],
            'X-Client-Version: 1.0.0',
        ]);

        $json = $res['json'];
        if (!($json['ok'] ?? false)) {
            throw new RuntimeException('Transfer request failed: ' . safe_string($json['error'] ?? 'Unknown error'));
        }

        return $json['data'] ?? [];
    }

    /**
     * Best-effort validation call. Does not block app on network failure.
     */
    private function tryServerValidate(): void
    {
        if (!$this->license) return;

        $base = (string)($this->cfg['server']['base_url'] ?? '');
        if ($base === '') return;

        $url = $base . '?action=validate';

        $payload = [
            'license_id' => safe_string($this->license['license_id'] ?? ''),
            'product_id' => (string)$this->cfg['product_id'],
            'fingerprint_hash' => $this->fingerprintHash,
            'client_state' => [
                'last_success_check_at' => $this->state['last_success_check_at'] ?? null,
            ],
        ];

        try {
            $res = HttpClient::postJson($url, $payload, (int)($this->cfg['server']['timeout_seconds'] ?? 8), [
                'X-Product-Id: ' . (string)$this->cfg['product_id'],
                'X-Client-Version: 1.0.0',
            ]);

            $json = $res['json'];
            if (!($json['ok'] ?? false)) {
                // Server says not ok, store message for UI
                $this->state['last_server_message'] = safe_string($json['error'] ?? 'Validation failed');
                return;
            }

            $data = $json['data'] ?? [];
            $status = safe_string($data['status'] ?? 'ACTIVE');
            $message = safe_string($data['message'] ?? 'OK');

            // Update state based on successful check-in
            $this->state['last_success_check_at'] = utc_now_rfc3339();
            $this->state['last_server_status'] = $status;
            $this->state['last_server_message'] = $message;

            // Update next due
            $policy = Policy::fromLicenseOrDefaults($this->license, $this->cfg['defaults'] ?? []);
            $this->state['next_check_due_at'] = ts_to_rfc3339(time() + ($policy->checkIntervalDays * 86400));

            // If server indicates blocking statuses, enforce immediately
            $blocked = ['SUSPENDED', 'REVOKED', 'EXPIRED', 'TRIAL_EXPIRED'];
            if (in_array($status, $blocked, true)) {
                throw new RuntimeException('License not valid: ' . $status);
            }
        } catch (Throwable $e) {
            // Network issues should not block the app here.
            // State remains as-is, offline policy will eventually force a check.
            $this->state['last_server_message'] = 'Validation check skipped: ' . $e->getMessage();
        }
    }
}
