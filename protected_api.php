<?php
declare(strict_types=1);

/**
 * protected_api.php
 *
 * Public interface to protected functions.
 * Your app calls functions here, not inside the module directly.
 */

require_once __DIR__ . '/protected_loader.php';
load_protected_module();

/**
 * Wraps simple interest calculation
 */
function p_simple_interest(float $principal, float $ratePct, float $timeYears): array
{
    return protected_simple_interest($principal, $ratePct, $timeYears);
}

/**
 * Wraps compound interest calculation
 */
function p_compound_interest(float $principal, float $ratePct, float $timeYears, int $compoundsPerYear): array
{
    return protected_compound_interest($principal, $ratePct, $timeYears, $compoundsPerYear);
}

/**
 * Wraps loan amortization calculation
 */
function p_loan_payment(float $principal, float $annualRatePct, int $months): array
{
    return protected_loan_payment($principal, $annualRatePct, $months);
}

/**
 * Wraps stats calculation
 */
function p_stats_summary(array $values): array
{
    return protected_stats_summary($values);
}
