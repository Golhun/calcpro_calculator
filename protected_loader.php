<?php
declare(strict_types=1);

/**
 * protected_loader.php
 *
 * Loads the protected module if available.
 * In production, you will replace protected_module.php with an ionCube-encoded file.
 */

require_once __DIR__ . '/ioncube_check.php';

function load_protected_module(): void
{
    $encodedPath = __DIR__ . '/protected_module.php';
    $stubPath    = __DIR__ . '/protected_module_stub.php';

    // Prefer the encoded module when ionCube is ready and the encoded file exists.
    if (ioncube_loader_ready() && file_exists($encodedPath)) {
        require_once $encodedPath;
        return;
    }

    // Fallback to stub for local dev or when ionCube is not present.
    require_once $stubPath;
}
