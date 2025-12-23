<?php
declare(strict_types=1);

/**
 * protected_api.php
 *
 * Public interface to protected business logic.
 * All application code must call functions here,
 * never the protected module directly.
 */

require_once __DIR__ . '/protected_loader.php';
load_protected_module();

/* -------------------------------------------------
 | License
 * -------------------------------------------------*/

/**
 * Returns current license status.
 * This is safe to expose to the UI.
 */
function p_license_status(): array
{
    try {
        return protected_check_license();
    } catch (Throwable $e) {
        return [
            'ok'     => false,
            'reason' => 'License check failed'
        ];
    }
}

/* -------------------------------------------------
 | Financial calculations
 * -------------------------------------------------*/

/**
 * Simple interest
 */
function p_simple_interest(
    float $principal,
    float $ratePct,
    float $timeYears
): array {
    return protected_simple_interest(
        $principal,
        $ratePct,
        $timeYears
    );
}

/**
 * Compound interest
 */
function p_compound_interest(
    float $principal,
    float $ratePct,
    float $timeYears,
    int $compoundsPerYear
): array {
    return protected_compound_interest(
        $principal,
        $ratePct,
        $timeYears,
        $compoundsPerYear
    );
}

/**
 * Loan amortization / monthly payment
 */
function p_loan_payment(
    float $principal,
    float $annualRatePct,
    int $months
): array {
    return protected_loan_payment(
        $principal,
        $annualRatePct,
        $months
    );
}

/* -------------------------------------------------
 | Statistics
 * -------------------------------------------------*/

/**
 * Statistical summary
 */
function p_stats_summary(array $values): array
{
    return protected_stats_summary($values);
}
