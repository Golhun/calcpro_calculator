<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

final class TimeGuard
{
    public static function assertNoRollback(array &$state, int $maxRollbackCount): void
    {
        $cg = $state['clock_guard'] ?? ['last_seen_time' => null, 'rollback_count' => 0];

        $lastSeen = rfc3339_to_ts($cg['last_seen_time'] ?? null);
        $now = time();

        if ($lastSeen !== null && $now + 60 < $lastSeen) {
            // Clock moved backwards beyond 60 seconds tolerance
            $cg['rollback_count'] = (int)($cg['rollback_count'] ?? 0) + 1;
            $cg['last_seen_time'] = ts_to_rfc3339($now);
            $state['clock_guard'] = $cg;

            if ($cg['rollback_count'] > $maxRollbackCount) {
                throw new RuntimeException('System clock rollback detected too many times.');
            }
        } else {
            $cg['last_seen_time'] = ts_to_rfc3339($now);
            $state['clock_guard'] = $cg;
        }
    }
}
