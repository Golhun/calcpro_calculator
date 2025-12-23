<?php
declare(strict_types=1);

/**
 * CalcPro – License Bootstrap
 *
 * This file enforces licensing for BOTH UI and API entry points.
 * It must be required at the very top of index.php and api.php.
 *
 * Any failure here MUST hard-stop execution.
 */

// If the app defines a global `LICENSE_FILE` or other constants, load app config first so
// the licensing client config (below) can pick up overrides from `config.php`.
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

require_once __DIR__ . '/Licensing Engine/License.php';

try {
    // Boot licensing engine
    $license = License::boot(__DIR__ . '/Licensing Engine/client_config.php');

    // Hard validation
    $license->assertValid();

    // Expose non-blocking warnings to the app (optional UI use)
    $GLOBALS['LICENSE_WARNINGS'] = $license->getWarnings();

} catch (Throwable $e) {

    // Always block execution
    http_response_code(403);

    $message = $e->getMessage();

    // CLI mode (testing, cron, scripts)
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, "LICENSE ERROR: {$message}" . PHP_EOL);
        exit(1);
    }

    // API calls (JSON response)
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (str_contains($uri, 'api.php')) {
        header('Content-Type: application/json');
        echo json_encode([
            'ok'      => false,
            'error'   => 'LICENSE_INVALID',
            'message' => $message,
        ], JSON_PRETTY_PRINT);
        exit;
    }

    // UI / Browser access
    header('Content-Type: text/html; charset=UTF-8');

    echo '<!doctype html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<title>License Error</title>';
    echo '<style>
            body {
                font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
                background: #0f172a;
                color: #e5e7eb;
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100vh;
                margin: 0;
            }
            .box {
                background: #020617;
                border: 1px solid #334155;
                border-radius: 14px;
                padding: 28px 32px;
                max-width: 520px;
                text-align: center;
            }
            h1 {
                color: #f87171;
                margin-bottom: 12px;
            }
            p {
                color: #cbd5f5;
                font-size: 15px;
                line-height: 1.5;
            }
          </style>';
    echo '</head>';
    echo '<body>';
    echo '<div class="box">';
    echo '<h1>License Error</h1>';
    echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</div>';
    echo '</body>';
    echo '</html>';

    exit;
}
