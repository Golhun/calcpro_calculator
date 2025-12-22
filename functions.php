<?php
declare(strict_types=1);

/**
 * functions.php
 *
 * General-purpose server-side utilities.
 *
 * IMPORTANT DESIGN DECISION:
 * - This file contains ONLY non-proprietary helper logic.
 * - Financial and statistical business logic has been moved
 *   behind the protected (ionCube-ready) boundary.
 *
 * This file is safe to remain unencoded.
 */

/* -------------------------------------------------
 | JSON RESPONSE HANDLING
 * -------------------------------------------------*/

/**
 * Send a normalized JSON response and terminate execution.
 */
function json_out(array $payload, int $code = 200): void
{
    if (!APP_DEBUG && isset($payload['error'])) {
        $payload['error'] = 'An error occurred. Please try again.';
    }

    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}


/* -------------------------------------------------
 | VALIDATION / SANITY HELPERS
 * -------------------------------------------------*/

/**
 * Clamp a numeric value between a minimum and maximum.
 */
function clamp(float $value, float $min, float $max): float
{
    return max($min, min($max, $value));
}

/**
 * Ensure a value is numeric and return as float.
 */
function require_numeric(mixed $value, string $fieldName): float
{
    if (!is_numeric($value)) {
        json_out([
            'ok'    => false,
            'error' => "{$fieldName} must be numeric"
        ], 400);
    }

    return (float) $value;
}

/**
 * Enforce maximum string length.
 */
function enforce_length(string $value, int $maxLength, string $fieldName): string
{
    $value = trim($value);

    if (mb_strlen($value) > $maxLength) {
        json_out([
            'ok'    => false,
            'error' => "{$fieldName} exceeds maximum length of {$maxLength}"
        ], 400);
    }

    return $value;
}

/* -------------------------------------------------
 | STATISTICS INPUT PARSING (PUBLIC, NON-PROTECTED)
 * -------------------------------------------------*/

/**
 * Parse a list of numbers from a string.
 *
 * Accepted separators:
 * - comma
 * - space
 * - semicolon
 *
 * Example:
 *   "2, 4 6;8"
 */
function parse_number_list(string $input): array
{
    $input = trim($input);

    if ($input === '') {
        throw new InvalidArgumentException('No numbers provided.');
    }

    $parts = preg_split('/[\s,;]+/', $input);
    $numbers = [];

    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }

        if (!is_numeric($part)) {
            throw new InvalidArgumentException("Invalid number: {$part}");
        }

        $numbers[] = (float) $part;
    }

    if (count($numbers) === 0) {
        throw new InvalidArgumentException('No valid numbers found.');
    }

    return $numbers;
}
