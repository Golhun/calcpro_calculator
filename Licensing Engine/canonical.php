<?php
declare(strict_types=1);

// canonical.php
// Deterministic canonical JSON for signing/verifying.

function canonicalize($value): string
{
    $normalized = normalize_value($value);
    return json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function normalize_value($v)
{
    if (is_array($v)) {
        // If associative array, sort keys
        if (is_assoc($v)) {
            ksort($v);
            $out = [];
            foreach ($v as $k => $val) {
                $out[(string)$k] = normalize_value($val);
            }
            return $out;
        }

        // Indexed array, keep order
        $out = [];
        foreach ($v as $val) {
            $out[] = normalize_value($val);
        }
        return $out;
    }

    if (is_object($v)) {
        return normalize_value((array)$v);
    }

    // Normalize scalars
    if (is_bool($v) || is_int($v) || is_float($v) || is_string($v) || $v === null) {
        return $v;
    }

    // Fallback: stringify
    return (string)$v;
}

function is_assoc(array $arr): bool
{
    $i = 0;
    foreach ($arr as $k => $_) {
        if ($k !== $i) return true;
        $i++;
    }
    return false;
}
