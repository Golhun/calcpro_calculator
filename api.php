<?php
declare(strict_types=1);

/**
 * api.php
 *
 * Central API router for Calc Pro.
 * Enforces:
 * - CSRF protection (POST only)
 * - Rate limiting
 * - Input validation
 * - Protected business logic via ionCube boundary
 * - Audit logging (non-blocking)
 *
 * Notes:
 * - Always call app_log BEFORE json_out, because json_out exits.
 * - Each route returns early to avoid fall-through.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/protected_api.php';
require_once __DIR__ . '/logger.php';

/* -------------------------
 | Local helpers
 * -------------------------*/
function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'POST required']);
        exit;
    }
}

function limit_length(string $value, int $max, string $field): string
{
    if (mb_strlen($value) > $max) {
        json_out(['ok' => false, 'error' => "{$field} exceeds max length"], 400);
    }
    return trim($value);
}

function get_float(string $key, float $default = 0.0): float
{
    if (!isset($_POST[$key])) return $default;
    return (float)$_POST[$key];
}

function get_int(string $key, int $default = 0): int
{
    if (!isset($_POST[$key])) return $default;
    return (int)$_POST[$key];
}

/* -------------------------
 | Routing
 * -------------------------*/
$action = (string)($_GET['action'] ?? '');

try {
    $pdo = db();

    /* ---------- LICENSE STATUS (GET) ---------- */
    if ($action === 'license_status') {
        rate_limit('license_status', 60, 60);

        $status = function_exists('p_license_status')
            ? p_license_status()
            : ['ok' => false, 'reason' => 'License system not enabled'];

        json_out(['ok' => true, 'data' => $status]);
    }

    /* ---------- HISTORY LIST (GET) ---------- */
    if ($action === 'history_list') {
        rate_limit('history_list', 60, 60);

        $stmt = $pdo->query(
            "SELECT id, created_at, category, input_text, result_text
             FROM calc_history
             ORDER BY id DESC
             LIMIT 50"
        );

        json_out(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    /* ---------- HISTORY ADD (POST) ---------- */
    if ($action === 'history_add') {
        require_post();
        rate_limit('history_add', 30, 60);
        verify_csrf($_POST['csrf_token'] ?? null);

        $category = limit_length((string)($_POST['category'] ?? 'basic'), 32, 'category');
        $input    = limit_length((string)($_POST['input_text'] ?? ''), 500, 'input_text');
        $result   = limit_length((string)($_POST['result_text'] ?? ''), 500, 'result_text');

        if ($input === '' || $result === '') {
            json_out(['ok' => false, 'error' => 'Input and result required'], 400);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO calc_history (category, input_text, result_text)
             VALUES (?, ?, ?)"
        );
        $stmt->execute([$category, $input, $result]);

        app_log('info', 'calc', 'Calculation stored', ['category' => $category]);

        json_out(['ok' => true]);
    }

    /* ---------- FINANCIAL (POST, PROTECTED) ---------- */
    if ($action === 'financial') {
        require_post();
        rate_limit('financial', 30, 60);
        verify_csrf($_POST['csrf_token'] ?? null);

        $type = (string)($_POST['type'] ?? '');
        $type = limit_length($type, 32, 'type');

        // Minimal server-side validation (kept permissive but safe)
        $principal = get_float('principal', 0.0);
        $rate      = get_float('rate', 0.0);

        if (!is_finite($principal) || $principal < 0) {
            json_out(['ok' => false, 'error' => 'Invalid principal'], 400);
        }
        if (!is_finite($rate)) {
            json_out(['ok' => false, 'error' => 'Invalid rate'], 400);
        }

        if ($type === 'simple_interest') {
            $time = get_float('time', 0.0);
            if (!is_finite($time) || $time < 0) {
                json_out(['ok' => false, 'error' => 'Invalid time'], 400);
            }

            $res = p_simple_interest($principal, $rate, $time);
            json_out(['ok' => true, 'data' => $res]);
        }

        if ($type === 'compound_interest') {
            $time = get_float('time', 0.0);
            $n    = get_int('compounds', 12);

            if (!is_finite($time) || $time < 0) {
                json_out(['ok' => false, 'error' => 'Invalid time'], 400);
            }
            if ($n <= 0 || $n > 3650) {
                json_out(['ok' => false, 'error' => 'Invalid compounds'], 400);
            }

            $res = p_compound_interest($principal, $rate, $time, $n);
            json_out(['ok' => true, 'data' => $res]);
        }

        if ($type === 'loan_payment') {
            $months = get_int('months', 1);
            if ($months <= 0 || $months > 1200) {
                json_out(['ok' => false, 'error' => 'Invalid months'], 400);
            }

            $res = p_loan_payment($principal, $rate, $months);
            json_out(['ok' => true, 'data' => $res]);
        }

        json_out(['ok' => false, 'error' => 'Unknown financial type'], 400);
    }

    /* ---------- STATS (POST, PROTECTED) ---------- */
    if ($action === 'stats') {
        require_post();
        rate_limit('stats', 20, 60);
        verify_csrf($_POST['csrf_token'] ?? null);

        $csv  = limit_length((string)($_POST['values'] ?? ''), 1000, 'values');
        $nums = parse_number_list($csv);

        if (count($nums) < 1) {
            json_out(['ok' => false, 'error' => 'No valid numbers provided'], 400);
        }
        if (count($nums) > 2000) {
            json_out(['ok' => false, 'error' => 'Too many numbers (max 2000)'], 400);
        }

        $res = p_stats_summary($nums);

        json_out(['ok' => true, 'data' => $res]);
    }

    /* ---------- GRAPH SAVE (POST) ---------- */
    if ($action === 'graph_save') {
        require_post();
        rate_limit('graph_save', 15, 60);
        verify_csrf($_POST['csrf_token'] ?? null);

        $title = limit_length((string)($_POST['title'] ?? 'My Graph'), 120, 'title');
        $expr  = limit_length((string)($_POST['expression'] ?? 'x'), 255, 'expression');

        $xmin = (float)($_POST['x_min'] ?? -10);
        $xmax = (float)($_POST['x_max'] ?? 10);
        $step = clamp((float)($_POST['step'] ?? 0.1), 0.0001, 10);

        if (!is_finite($xmin) || !is_finite($xmax) || !is_finite($step)) {
            json_out(['ok' => false, 'error' => 'Invalid numeric range'], 400);
        }
        if ($xmax <= $xmin) {
            json_out(['ok' => false, 'error' => 'x_max must be greater than x_min'], 400);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO saved_graphs (title, expression, x_min, x_max, step)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$title, $expr, $xmin, $xmax, $step]);

        app_log('info', 'graph', 'Graph saved', ['expression' => $expr]);

        json_out(['ok' => true]);
    }

    /* ---------- GRAPH LIST (GET) ---------- */
    if ($action === 'graph_list') {
        rate_limit('graph_list', 60, 60);

        $stmt = $pdo->query(
            "SELECT id, created_at, title, expression, x_min, x_max, step
             FROM saved_graphs
             ORDER BY id DESC
             LIMIT 20"
        );

        json_out(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    json_out(['ok' => false, 'error' => 'Unknown action'], 404);

} catch (Throwable $e) {
    // Log first (json_out exits)
    app_log('error', 'api_error', $e->getMessage(), ['action' => $action]);

    json_out([
        'ok' => false,
        'error' => APP_DEBUG ? $e->getMessage() : 'Server error'
    ], 500);
}
