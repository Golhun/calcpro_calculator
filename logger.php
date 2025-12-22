<?php
declare(strict_types=1);

/**
 * logger.php
 *
 * Central application logging utility.
 * Keeps logging consistent, minimal, and version-aware.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function app_log(
    string $level,
    string $event,
    string $message,
    array $context = []
): void {
    try {
        $pdo = db();

        $stmt = $pdo->prepare(
            "INSERT INTO app_logs
             (level, event, message, context_json, app_version, client_ip)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $level,
            $event,
            mb_substr($message, 0, 255),
            $context ? json_encode($context) : null,
            APP_VERSION,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    } catch (Throwable $e) {
        // Logging must never break the app
        if (APP_DEBUG) {
            error_log('Logger failure: ' . $e->getMessage());
        }
    }
}
