<?php
declare(strict_types=1);

/**
 * protected_module_stub.php
 *
 * This file represents your protected business logic in plain PHP.
 * In production, you will encode the equivalent logic into protected_module.php using ionCube Encoder.
 */

function protected_check_license(): array
{
    if (!defined('LICENSE_FILE') || !file_exists(LICENSE_FILE)) {
        return ['ok' => false, 'reason' => 'License file missing'];
    }

    $raw = file_get_contents(LICENSE_FILE);
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        return ['ok' => false, 'reason' => 'Invalid license format'];
    }

    if (($data['product'] ?? '') !== LICENSE_PRODUCT_CODE) {
        return ['ok' => false, 'reason' => 'Invalid product license'];
    }

    // Domain binding
    $domain = $_SERVER['HTTP_HOST'] ?? 'cli';
    if (!empty($data['domain']) && $data['domain'] !== $domain) {
        return ['ok' => false, 'reason' => 'License domain mismatch'];
    }

    // Expiry
    if (!empty($data['expires'])) {
        $exp = strtotime($data['expires']);
        if ($exp !== false && time() > $exp) {
            return ['ok' => false, 'reason' => 'License expired'];
        }
    }

    // Signature verification (stubbed here, enforced in encoded version)
    if (empty($data['signature'])) {
        return ['ok' => false, 'reason' => 'Missing license signature'];
    }

    return [
        'ok' => true,
        'issued_to' => $data['issued_to'] ?? 'Unknown',
        'expires' => $data['expires'] ?? null
    ];
}


function protected_simple_interest(float $principal, float $ratePct, float $timeYears): array
{
        $lic = protected_check_license();
    if (!$lic['ok']) {
        throw new RuntimeException('License error: ' . $lic['reason']);
    }

    $r = $ratePct / 100.0;
    $interest = $principal * $r * $timeYears;
    $amount = $principal + $interest;

    return [
        'interest' => round($interest, 2),
        'amount'   => round($amount, 2),
    ];
}

function protected_compound_interest(float $principal, float $ratePct, float $timeYears, int $compoundsPerYear): array
{
        $lic = protected_check_license();
    if (!$lic['ok']) {
        throw new RuntimeException('License error: ' . $lic['reason']);
    }

    $r = $ratePct / 100.0;
    $n = max(1, $compoundsPerYear);
    $amount = $principal * pow(1 + ($r / $n), $n * $timeYears);
    $interest = $amount - $principal;

    return [
        'interest' => round($interest, 2),
        'amount'   => round($amount, 2),
    ];
}

function protected_loan_payment(float $principal, float $annualRatePct, int $months): array
{
        $lic = protected_check_license();
    if (!$lic['ok']) {
        throw new RuntimeException('License error: ' . $lic['reason']);
    }

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

function protected_stats_summary(array $values): array
{
        $lic = protected_check_license();
    if (!$lic['ok']) {
        throw new RuntimeException('License error: ' . $lic['reason']);
    }
    
    sort($values);
    $n = count($values);
    $sum = array_sum($values);
    $mean = $sum / $n;

    if ($n % 2 === 1) {
        $median = $values[intdiv($n, 2)];
    } else {
        $median = ($values[$n/2 - 1] + $values[$n/2]) / 2.0;
    }

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
