<?php
declare(strict_types=1);

require_once __DIR__ . '/License.php';

try {
    // Boot using your existing client_config.php
    $lic = License::boot(__DIR__ . '/client_config.php');

    // Run full validation chain
    $lic->assertValid();

    echo "LICENSE VALID: Engine passed all checks.\n";

    $warnings = $lic->getWarnings();
    if ($warnings) {
        echo "WARNINGS:\n";
        foreach ($warnings as $w) {
            echo " - {$w}\n";
        }
    }
} catch (Throwable $e) {
    echo "LICENSE INVALID: " . $e->getMessage() . "\n";
    exit(1);
}
