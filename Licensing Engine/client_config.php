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
    'license_file' => (defined('LICENSE_FILE') ? constant('LICENSE_FILE') : __DIR__ . '/license.key'),
    'state_file'   => (defined('LICENSE_STATE_FILE') ? constant('LICENSE_STATE_FILE') : __DIR__ . '/license.state.json'),

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

    // Optional: public key for signature verification and enforcement. You can either
    // set 'public_key_b64' here (base64 ed25519 public key) or export PUBLIC_KEY_B64
    // in the environment. If 'require_signature' is true, validation will fail when
    // no public key is configured.
    // 'public_key_b64' => 'BASE64_PUBLIC_KEY_HERE',
    // 'require_signature' => false,
];
