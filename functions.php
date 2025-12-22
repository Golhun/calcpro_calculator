<?php
declare(strict_types=1);

/**
 * functions.php
 * Server-side calculation utilities.
 *
 * NOTE:
 * - For expression parsing we rely on the frontend (math.js) for safety and features.
 * - Server-side functions here are used for financial and stats computations and for validation.
 */

function json_out(array $payload, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function clamp(float $v, float $min, float $max): float
{
    return max($min, min($max, $v));
}

/** -------------------------
 * Financial calculators
 * ------------------------*/

function simple_interest(float $principal, float $ratePct, float $timeYears): array
{
    $r = $ratePct / 100.0;
    $interest = $principal * $r * $timeYears;
    $amount = $principal + $interest;

    return [
        'interest' => round($interest, 2),
        'amount'   => round($amount, 2),
    ];
}

function compound_interest(float $principal, float $ratePct, float $timeYears, int $compoundsPerYear): array
{
    $r = $ratePct / 100.0;
    $n = max(1, $compoundsPerYear);
    $amount = $principal * pow(1 + ($r / $n), $n * $timeYears);
    $interest = $amount - $principal;

    return [
        'interest' => round($interest, 2),
        'amount'   => round($amount, 2),
    ];
}

/**
 * Loan payment (amortized) using standard annuity formula.
 * payment = P * [ i(1+i)^N ] / [ (1+i)^N - 1 ]
 */
function loan_payment(float $principal, float $annualRatePct, int $months): array
{
    $months = max(1, $months);
    $i = ($annualRatePct / 100.0) / 12.0;

    if (abs($i) < 1e-12) {
        $payment = $principal / $months;
        return [
            'monthly_payment' => round($payment, 2),
            'total_payment'   => round($payment * $months, 2),
            'total_interest'  => round(($payment * $months) - $principal, 2),
        ];
    }

    $pow = pow(1 + $i, $months);
    $payment = $principal * ($i * $pow) / ($pow - 1);

    $total = $payment * $months;
    $interest = $total - $principal;

    return [
        'monthly_payment' => round($payment, 2),
        'total_payment'   => round($total, 2),
        'total_interest'  => round($interest, 2),
    ];
}

/** -------------------------
 * Statistics
 * ------------------------*/

function parse_number_list(string $csv): array
{
    $parts = preg_split('/[\s,;]+/', trim($csv));
    $nums = [];
    foreach ($parts as $p) {
        if ($p === '') continue;
        if (!is_numeric($p)) {
            throw new InvalidArgumentException("Invalid number: {$p}");
        }
        $nums[] = (float)$p;
    }
    if (count($nums) === 0) {
        throw new InvalidArgumentException("No numbers provided.");
    }
    return $nums;
}

function stats_summary(array $values): array
{
    sort($values);
    $n = count($values);
    $sum = array_sum($values);
    $mean = $sum / $n;

    // median
    if ($n % 2 === 1) {
        $median = $values[intdiv($n, 2)];
    } else {
        $median = ($values[$n/2 - 1] + $values[$n/2]) / 2.0;
    }

    // mode (simple frequency)
    $freq = [];
    foreach ($values as $v) {
        $k = (string)$v;
        $freq[$k] = ($freq[$k] ?? 0) + 1;
    }
    arsort($freq);
    $maxCount = reset($freq);
    $modes = [];
    foreach ($freq as $k => $c) {
        if ($c === $maxCount && $c > 1) $modes[] = (float)$k;
    }

    // variance + std (population)
    $var = 0.0;
    foreach ($values as $v) {
        $var += ($v - $mean) ** 2;
    }
    $var = $var / $n;
    $std = sqrt($var);

    return [
        'count'    => $n,
        'sum'      => round($sum, 6),
        'mean'     => round($mean, 6),
        'median'   => round($median, 6),
        'mode'     => $modes,
        'variance' => round($var, 6),
        'std_dev'  => round($std, 6),
        'min'      => round(min($values), 6),
        'max'      => round(max($values), 6),
    ];
}
