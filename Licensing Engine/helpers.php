<?php
declare(strict_types=1);

function json_read_file(string $path): ?array
{
    if (!is_file($path)) return null;
    $raw = file_get_contents($path);
    if ($raw === false) return null;

    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function json_write_file(string $path, array $data): bool
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) return false;

    // Atomic write
    $tmp = $path . '.tmp';
    if (file_put_contents($tmp, $json, LOCK_EX) === false) return false;
    return rename($tmp, $path);
}

function utc_now_rfc3339(): string
{
    return gmdate('c');
}

function rfc3339_to_ts(?string $s): ?int
{
    if (!$s) return null;
    $t = strtotime($s);
    return $t === false ? null : $t;
}

function ts_to_rfc3339(int $ts): string
{
    return gmdate('c', $ts);
}

function safe_string($v): string
{
    return is_string($v) ? $v : '';
}

function safe_int($v, int $fallback = 0): int
{
    if (is_int($v)) return $v;
    if (is_numeric($v)) return (int)$v;
    return $fallback;
}

function array_get(array $a, string $key, $default = null)
{
    return array_key_exists($key, $a) ? $a[$key] : $default;
}
