<?php
require_once __DIR__ . '/db.php';

// ── 공통 헤더 ────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$uri    = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// ── 라우팅 ───────────────────────────────────────────────

// GET / — API 정보
if ($uri === '' || $uri === '/') {
    json_ok(200, [
        'api'       => 'PHP REST API',
        'version'   => '1.0',
        'endpoints' => [
            'GET    /api/tasks'       => '태스크 목록',
            'POST   /api/tasks'       => '태스크 생성',
            'GET    /api/tasks/{id}'  => '태스크 단건 조회',
            'PUT    /api/tasks/{id}'  => '태스크 수정',
            'DELETE /api/tasks/{id}'  => '태스크 삭제',
        ],
    ]);
    exit;
}

// /api/tasks  또는  /api/tasks/{id}
if (preg_match('#^/api/tasks(?:/(\d+))?$#', $uri, $m)) {
    $id = isset($m[1]) ? (int)$m[1] : null;

    if ($id === null) {
        if ($method === 'GET')  { list_tasks();  exit; }
        if ($method === 'POST') { create_task(); exit; }
        json_error(405, 'Method Not Allowed');
    } else {
        if ($method === 'GET')    { get_task($id);    exit; }
        if ($method === 'PUT')    { update_task($id); exit; }
        if ($method === 'DELETE') { delete_task($id); exit; }
        json_error(405, 'Method Not Allowed');
    }
}

json_error(404, 'Endpoint not found');

// ── 핸들러 ───────────────────────────────────────────────

function list_tasks(): void
{
    $rows = get_db()->query('SELECT * FROM tasks ORDER BY id DESC')->fetchAll();
    // done 필드를 boolean으로 변환
    foreach ($rows as &$r) $r['done'] = (bool)$r['done'];
    json_ok(200, $rows);
}

function get_task(int $id): void
{
    $stmt = get_db()->prepare('SELECT * FROM tasks WHERE id = ?');
    $stmt->execute([$id]);
    $task = $stmt->fetch();
    if (!$task) { json_error(404, 'Task not found'); return; }
    $task['done'] = (bool)$task['done'];
    json_ok(200, $task);
}

function create_task(): void
{
    $body  = json_body();
    $title = trim($body['title'] ?? '');

    if ($title === '') {
        json_error(400, '"title" is required');
        return;
    }

    $stmt = get_db()->prepare('INSERT INTO tasks (title) VALUES (?)');
    $stmt->execute([$title]);

    $id   = (int)get_db()->lastInsertId();
    $stmt = get_db()->prepare('SELECT * FROM tasks WHERE id = ?');
    $stmt->execute([$id]);
    $task = $stmt->fetch();
    $task['done'] = (bool)$task['done'];

    json_ok(201, $task);
}

function update_task(int $id): void
{
    $chk = get_db()->prepare('SELECT id FROM tasks WHERE id = ?');
    $chk->execute([$id]);
    if (!$chk->fetch()) { json_error(404, 'Task not found'); return; }

    $body   = json_body();
    $fields = [];
    $params = [];

    if (array_key_exists('title', $body)) {
        $title = trim($body['title']);
        if ($title === '') { json_error(400, '"title" cannot be empty'); return; }
        $fields[] = 'title = ?';
        $params[] = $title;
    }

    if (array_key_exists('done', $body)) {
        $fields[] = 'done = ?';
        $params[] = $body['done'] ? 1 : 0;
    }

    if (empty($fields)) { json_error(400, 'No fields to update'); return; }

    $fields[] = "updated_at = datetime('now')";
    $params[]  = $id;

    $upd = get_db()->prepare('UPDATE tasks SET ' . implode(', ', $fields) . ' WHERE id = ?');
    $upd->execute($params);

    $stmt = get_db()->prepare('SELECT * FROM tasks WHERE id = ?');
    $stmt->execute([$id]);
    $task = $stmt->fetch();
    $task['done'] = (bool)$task['done'];

    json_ok(200, $task);
}

function delete_task(int $id): void
{
    $chk = get_db()->prepare('SELECT id FROM tasks WHERE id = ?');
    $chk->execute([$id]);
    if (!$chk->fetch()) { json_error(404, 'Task not found'); return; }

    get_db()->prepare('DELETE FROM tasks WHERE id = ?')->execute([$id]);
    http_response_code(204);
}

// ── 헬퍼 ─────────────────────────────────────────────────

function json_body(): array
{
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function json_ok(int $status, mixed $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

function json_error(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
}
