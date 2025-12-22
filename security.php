<?php
declare(strict_types=1);

/**
 * security.php
 *
 * Minimal security utilities:
 * - CSRF protection
 * - Rate limiting
 */

require_once __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* -------------------------
 | CSRF TOKEN
 * -------------------------*/
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): void
{
    if (
        empty($token) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        echo json_encode([
            'ok' => false,
            'error' => 'Invalid or missing CSRF token'
        ]);
        exit;
    }
}

/* -------------------------
 | Simple rate limiting
 * -------------------------*/
function rate_limit(string $key, int $maxRequests = 60, int $windowSeconds = 60): void
{
    $now = time();

    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = [
            'count' => 1,
            'start' => $now
        ];
        return;
    }

    $entry = &$_SESSION['rate_limit'][$key];

    if ($now - $entry['start'] > $windowSeconds) {
        $entry = ['count' => 1, 'start' => $now];
        return;
    }

    $entry['count']++;

    if ($entry['count'] > $maxRequests) {
        http_response_code(429);
        echo json_encode([
            'ok' => false,
            'error' => 'Too many requests. Please slow down.'
        ]);
        exit;
    }
}
