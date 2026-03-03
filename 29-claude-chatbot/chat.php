<?php
declare(strict_types=1);
require_once __DIR__ . '/vendor/autoload.php';

use Anthropic\Client;

// ─── .env 로드 ───────────────────────────────────────────────
if (file_exists(__DIR__ . '/.env')) {
    foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        putenv(trim($k) . '=' . trim($v));
        $_ENV[trim($k)] = trim($v);
    }
}

// ─── 응답 헬퍼 ───────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');

function json_ok(mixed $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_err(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── 메서드 검사 ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_err('POST 요청만 허용됩니다.', 405);
}

// ─── 요청 파싱 ───────────────────────────────────────────────
$input    = json_decode(file_get_contents('php://input'), true) ?? [];
$messages = $input['messages'] ?? [];
$system   = trim($input['system']  ?? '');
$model    = $input['model']    ?? 'claude-opus-4-6';

$allowed = ['claude-opus-4-6', 'claude-sonnet-4-6', 'claude-haiku-4-5'];
if (!in_array($model, $allowed, true)) {
    $model = 'claude-opus-4-6';
}

// ─── 유효성 검사 ─────────────────────────────────────────────
if (empty($messages)) {
    json_err('messages가 비어 있습니다.');
}

$lastRole = $messages[array_key_last($messages)]['role'] ?? '';
if ($lastRole !== 'user') {
    json_err('마지막 메시지는 user 역할이어야 합니다.');
}

// ─── API 키 확인 ─────────────────────────────────────────────
$apiKey = getenv('ANTHROPIC_API_KEY') ?: '';
if ($apiKey === '') {
    json_err('.env 파일에 ANTHROPIC_API_KEY를 설정해주세요.', 500);
}

// ─── Claude API 호출 ─────────────────────────────────────────
try {
    $client = new Client(apiKey: $apiKey);

    if ($system !== '') {
        $response = $client->messages->create(
            model: $model,
            maxTokens: 4096,
            system: $system,
            messages: $messages,
        );
    } else {
        $response = $client->messages->create(
            model: $model,
            maxTokens: 4096,
            messages: $messages,
        );
    }

    // content 블록에서 텍스트 추출 (여러 블록 대비)
    $text = '';
    foreach ($response->content as $block) {
        if ($block->type === 'text') {
            $text .= $block->text;
        }
    }

    json_ok([
        'content'     => $text,
        'model'       => $response->model,
        'stop_reason' => $response->stopReason,
        'usage'       => [
            'input_tokens'  => $response->usage->inputTokens,
            'output_tokens' => $response->usage->outputTokens,
        ],
    ]);

} catch (\Throwable $e) {
    json_err('Claude API 오류: ' . $e->getMessage(), 500);
}
