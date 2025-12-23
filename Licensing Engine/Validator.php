<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

final class Validator
{
    /**
     * NOTE: Signature verification is intentionally separated.
     * In production you will verify the signature before trusting any fields.
     */
    public static function validateCore(array $license, string $expectedProductId): void
    {
        $sv = (int)($license['schema_version'] ?? 0);
        if ($sv !== 1) throw new RuntimeException('Unsupported license schema version.');

        $productId = safe_string($license['product_id'] ?? '');
        if ($productId !== $expectedProductId) throw new RuntimeException('License product mismatch.');

        $status = safe_string($license['status'] ?? '');
        if ($status === '') throw new RuntimeException('License status missing.');

        // Block statuses
        $blocked = ['SUSPENDED', 'REVOKED', 'EXPIRED', 'TRIAL_EXPIRED'];
        if (in_array($status, $blocked, true)) {
            throw new RuntimeException('License not valid for use: ' . $status);
        }

        $expiresAt = rfc3339_to_ts($license['expires_at'] ?? null);
        if ($expiresAt === null) throw new RuntimeException('expires_at missing or invalid.');

        if (time() > $expiresAt) {
            throw new RuntimeException('License expired.');
        }
    }

    public static function validateFingerprint(array $license, string $fingerprintHash): void
    {
        $fp = $license['fingerprint'] ?? null;
        if (!is_array($fp)) return;

        $bound = (bool)($fp['bound'] ?? false);
        if (!$bound) return;

        $expected = safe_string($fp['fingerprint_hash'] ?? '');
        if ($expected === '') throw new RuntimeException('License fingerprint missing.');
        if ($expected !== $fingerprintHash) throw new RuntimeException('Machine fingerprint mismatch.');
    }

    public static function computeOfflineState(Policy $policy, array $state): array
    {
        $lastSuccess = rfc3339_to_ts($state['last_success_check_at'] ?? null);

        if ($lastSuccess === null) {
            // No successful check yet, treat as due now
            return [
                'offline_days' => null,
                'warn' => false,
                'hard_block' => false,
                'due' => true,
            ];
        }

        $days = (int)floor((time() - $lastSuccess) / 86400);

        $warn = $days >= $policy->warnAfterDays;
        $hard = $days > $policy->maxOfflineDays;

        return [
            'offline_days' => $days,
            'warn' => $warn,
            'hard_block' => $hard,
            'due' => $days >= $policy->checkIntervalDays,
        ];
    }

    public static function canReceiveUpdates(array $license, string $appReleaseDateRfc3339): bool
    {
        $updatesUntil = rfc3339_to_ts($license['updates_until'] ?? null);
        $appRelease   = rfc3339_to_ts($appReleaseDateRfc3339);

        if ($updatesUntil === null || $appRelease === null) return false;
        return $appRelease <= $updatesUntil;
    }
}
