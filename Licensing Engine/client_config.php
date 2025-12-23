<?php
declare(strict_types=1);

/**
 * client_config.php
 * Licensing Engine client configuration (per-app).
 *
 * Each app copies this file and adjusts values.
 */

return [
    // Unique product id, must match server license.product_id
    'product_id' => 'calcpro_calculator',

    // Where license files live (recommended: outside web root)
    'license_file' => __DIR__ . '/license.key',
    'state_file'   => __DIR__ . '/license.state.json',

    // Server endpoint (optional). If null, engine works fully offline after activation,
    // but you lose remote revoke, remote suspend, and server-controlled transfers.
    'server' => [
        'enabled' => true,
        'base_url' => 'https://your-domain.com/license_api.php', // change
        'timeout_seconds' => 8,
    ],

    // Offline policy defaults used when license.policy missing
    'defaults' => [
        'check_interval_days' => 30,
        'warn_after_days'     => 180,
        'max_offline_days'    => 365,
        'max_transfers'       => 2,
    ],

    // Clock rollback tolerance
    'clock_guard' => [
        'max_rollback_count' => 3,
    ],
];
