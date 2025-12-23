<?php
declare(strict_types=1);

final class Policy
{
    public int $checkIntervalDays;
    public int $warnAfterDays;
    public int $maxOfflineDays;

    public function __construct(int $checkIntervalDays, int $warnAfterDays, int $maxOfflineDays)
    {
        $this->checkIntervalDays = max(1, $checkIntervalDays);
        $this->warnAfterDays     = max(0, $warnAfterDays);
        $this->maxOfflineDays    = max(1, $maxOfflineDays);
    }

    public static function fromLicenseOrDefaults(array $license, array $defaults): self
    {
        $pol = $license['policy'] ?? [];
        $check = (int)($pol['check_interval_days'] ?? $defaults['check_interval_days'] ?? 30);
        $warn  = (int)($pol['warn_after_days'] ?? $defaults['warn_after_days'] ?? 180);
        $max   = (int)($pol['max_offline_days'] ?? $defaults['max_offline_days'] ?? 365);

        return new self($check, $warn, $max);
    }
}
