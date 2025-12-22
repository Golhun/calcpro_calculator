<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$action = $_GET['action'] ?? '';

try {
    $pdo = db();

    if ($action === 'history_list') {
        $stmt = $pdo->query("SELECT id, created_at, category, input_text, result_text FROM calc_history ORDER BY id DESC LIMIT 50");
        json_out(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'history_add') {
        $category = trim((string)($_POST['category'] ?? 'basic'));
        $input = trim((string)($_POST['input_text'] ?? ''));
        $result = trim((string)($_POST['result_text'] ?? ''));
        $meta = $_POST['meta_json'] ?? null;

        if ($input === '' || $result === '') {
            json_out(['ok' => false, 'error' => 'Missing input or result'], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO calc_history (category, input_text, result_text, meta_json) VALUES (?, ?, ?, ?)");
        $stmt->execute([$category, $input, $result, $meta ? json_encode($meta) : null]);

        json_out(['ok' => true]);
    }

    if ($action === 'financial') {
        $type = $_POST['type'] ?? '';
        if ($type === 'simple_interest') {
            $p = (float)($_POST['principal'] ?? 0);
            $r = (float)($_POST['rate'] ?? 0);
            $t = (float)($_POST['time'] ?? 0);
            $res = simple_interest($p, $r, $t);
            json_out(['ok' => true, 'data' => $res]);
        }

        if ($type === 'compound_interest') {
            $p = (float)($_POST['principal'] ?? 0);
            $r = (float)($_POST['rate'] ?? 0);
            $t = (float)($_POST['time'] ?? 0);
            $n = (int)($_POST['compounds'] ?? 12);
            $res = compound_interest($p, $r, $t, $n);
            json_out(['ok' => true, 'data' => $res]);
        }

        if ($type === 'loan_payment') {
            $p = (float)($_POST['principal'] ?? 0);
            $r = (float)($_POST['rate'] ?? 0);
            $m = (int)($_POST['months'] ?? 1);
            $res = loan_payment($p, $r, $m);
            json_out(['ok' => true, 'data' => $res]);
        }

        json_out(['ok' => false, 'error' => 'Unknown financial type'], 400);
    }

    if ($action === 'stats') {
        $csv = (string)($_POST['values'] ?? '');
        $nums = parse_number_list($csv);
        $res = stats_summary($nums);
        json_out(['ok' => true, 'data' => $res]);
    }

    if ($action === 'graph_save') {
        $title = trim((string)($_POST['title'] ?? 'My Graph'));
        $expr  = trim((string)($_POST['expression'] ?? 'x'));
        $xmin  = (float)($_POST['x_min'] ?? -10);
        $xmax  = (float)($_POST['x_max'] ?? 10);
        $step  = (float)($_POST['step'] ?? 0.1);

        $step = clamp($step, 0.0001, 10);
        if ($xmax <= $xmin) json_out(['ok' => false, 'error' => 'x_max must be greater than x_min'], 400);

        $stmt = $pdo->prepare("INSERT INTO saved_graphs (title, expression, x_min, x_max, step) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $expr, $xmin, $xmax, $step]);

        json_out(['ok' => true]);
    }

    if ($action === 'graph_list') {
        $stmt = $pdo->query("SELECT id, created_at, title, expression, x_min, x_max, step FROM saved_graphs ORDER BY id DESC LIMIT 20");
        json_out(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    json_out(['ok' => false, 'error' => 'Unknown action'], 404);

} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 500);
}
