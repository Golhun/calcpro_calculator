<?php
// Simple CLI test to confirm license bootstrap enforces presence and validity of license.
// Run: php tests/test_license_enforcement.php
// Expected: exit code 1 if license missing or invalid; prints nothing on success.

declare(strict_types=1);

require_once __DIR__ . '/../license_bootstrap.php';

echo "License bootstrap completed successfully\n";
