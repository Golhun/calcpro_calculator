<?php
declare(strict_types=1);

/**
 * api.php
 *
 * Central API router for Calc Pro.
 * Enforces:
 * - CSRF protection
 * - Rate limiting
 * - Input validation
 * - Protected business logic via ionCube boundary
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/protected_api.php';

/* -------------------------
 | Local helpers
 * -------------------------*/
function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_out(['ok' => false, 'error' => 'POST required'], 405);
    }
}

function limit_length(string $value, int $max, string $field): string
{
    if (mb_strlen($value) > $max) {
        json_out(['ok' => false, 'error' => "$field exceeds max length"], 400);
    }
    return trim($value);
}

/* -------------------------
 | Routing
 * -------------------------*/
$action = $_GET['action'] ?? '';

try {
    $pdo = db();

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

        $category = limit_length($_POST['category'] ?? 'basic', 32, 'category');
        $input    = limit_length($_POST['input_text'] ?? '', 500, 'input_text');
        $result   = limit_length($_POST['result_text'] ?? '', 500, 'result_text');

        if ($input === '' || $result === '') {
            json_out(['ok' => false, 'error' => 'Input and result required'], 400);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO calc_history (category, input_text, result_text)
             VALUES (?, ?, ?)"
        );
        $stmt->execute([$category, $input, $result]);

        json_out(['ok' => true]);
    }

    /* ---------- FINANCIAL (POST, PROTECTED) ---------- */
    if ($action === 'financial') {

        require_post();
        rate_limit('financial', 30, 60);
        verify_csrf($_POST['csrf_token'] ?? null);

        $type = $_POST['type'] ?? '';

        if ($type === 'simple_interest') {
            $res = p_simple_interest(
                (float)($_POST['principal'] ?? 0),
                (float)($_POST['rate'] ?? 0),
                (float)($_POST['time'] ?? 0)
            );
            json_out(['ok' => true, 'data' => $res]);
        }

        if ($type === 'compound_interest') {
            $res = p_compound_interest(
                (float)($_POST['principal'] ?? 0),
                (float)($_POST['rate'] ?? 0),
                (float)($_POST['time'] ?? 0),
                (int)($_POST['compounds'] ?? 12)
            );
            json_out(['ok' => true, 'data' => $res]);
        }

        if ($type === 'loan_payment') {
            $res = p_loan_payment(
                (float)($_POST['principal'] ?? 0),
                (float)($_POST['rate'] ?? 0),
                (int)($_POST['months'] ?? 1)
            );
            json_out(['ok' => true, 'data' => $res]);
        }

        json_out(['ok' => false, 'error' => 'Unknown financial type'], 400);
    }

    /* ---------- STATS (POST, PROTECTED) ---------- */
    if ($action === 'stats') {

        require_post();
        rate_limit('stats', 20, 60);
        verify_csrf($_POST['csrf_token'] ?? null);

        $csv = limit_length((string)($_POST['values'] ?? ''), 1000, 'values');
        $nums = parse_number_list($csv);

        $res = p_stats_summary($nums);

        json_out(['ok' => true, 'data' => $res]);
    }

    /* ---------- GRAPH SAVE (POST) ---------- */
    if ($action === 'graph_save') {

        require_post();
        rate_limit('graph_save', 15, 60);
        verify_csrf($_POST['csrf_token'] ?? null);

        $title = limit_length($_POST['title'] ?? 'My Graph', 120, 'title');
        $expr  = limit_length($_POST['expression'] ?? 'x', 255, 'expression');

        $xmin = (float)($_POST['x_min'] ?? -10);
        $xmax = (float)($_POST['x_max'] ?? 10);
        $step = clamp((float)($_POST['step'] ?? 0.1), 0.0001, 10);

        if ($xmax <= $xmin) {
            json_out(['ok' => false, 'error' => 'x_max must be greater than x_min'], 400);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO saved_graphs (title, expression, x_min, x_max, step)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$title, $expr, $xmin, $xmax, $step]);

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
    json_out([
        'ok' => false,
        'error' => APP_DEBUG ? $e->getMessage() : 'Server error'
    ], 500);
}
