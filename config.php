<?php
declare(strict_types=1);

/**
 * config.php
 *
 * Central application configuration file.
 * This file defines environment, app metadata, and database settings.
 * It is intentionally simple and explicit.
 *
 * In production:
 * - Values can be overridden via environment variables
 * - This file can remain unencoded or be partially protected
 */

/* -------------------------
 | Application metadata
 * -------------------------*/
define('APP_NAME', 'Calc Pro');
define('APP_VERSION', '1.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'local'); // local | production

/* -------------------------
 | Database configuration
 * -------------------------*/
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'calcpro');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

/* -------------------------
 | Runtime flags
 * -------------------------*/
define('APP_DEBUG', APP_ENV === 'local');

/* -------------------------
 | Error handling
 * -------------------------*/
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
}

define('LICENSE_FILE', __DIR__ . '/license.key');
define('LICENSE_PRODUCT_CODE', 'CALCPRO');
// When integrating the standalone Licensing Engine, set the expected
// engine product identifier here so both formats are accepted.
define('LICENSE_PRODUCT_ID', 'calcpro_calculator');
