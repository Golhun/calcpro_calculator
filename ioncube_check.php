<?php
declare(strict_types=1);

/**
 * ioncube_check.php
 * This is a safe example of how to detect the ionCube Loader.
 * It helps you practice: "if loader exists, include protected code, else show message".
 */

function ioncube_loader_ready(): bool
{
    // This is the standard detection pattern
    return extension_loaded('ionCube Loader') || function_exists('ioncube_file_is_encoded');
}

function require_ioncube_or_exit(): void
{
    if (!ioncube_loader_ready()) {
        http_response_code(500);
        echo "ionCube Loader is not installed on this PHP runtime. Please install/enable it to run protected modules.";
        exit;
    }
}
