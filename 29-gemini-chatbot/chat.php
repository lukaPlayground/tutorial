<?php declare(strict_types=1);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ── .env 파싱 ──────────────────────────────────────────────
if (file_exists(__DIR__ . '/.env')) {
    foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        putenv(trim($k) . '=' . trim($v));
        $_ENV[trim($k)] = trim($v);
    }
}

function json_ok(array $data): never {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_err(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('POST only', 405);

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) json_err('Invalid JSON');

$messages = $input['messages'] ?? [];
$system   = trim($input['system'] ?? '');
$model    = $input['model'] ?? 'gemini-2.0-flash';

if (empty($messages)) json_err('messages required');

$apiKey = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '');
if (!$apiKey) json_err('GEMINI_API_KEY not set', 500);

// ── 메시지 변환: {role, content} → Gemini {role, parts:[{text}]} ──
// assistant → model (Gemini 역할명)
$contents = array_map(fn($msg) => [
    'role'  => $msg['role'] === 'assistant' ? 'model' : $msg['role'],
    'parts' => [['text' => $msg['content']]],
], $messages);

$body = ['contents' => $contents];
if ($system !== '') {
    $body['system_instruction'] = ['parts' => [['text' => $system]]];
}

// ── Gemini API 호출 (SDK 없이 직접 HTTP) ──────────────────
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

$context = stream_context_create([
    'http' => [
        'method'        => 'POST',
        'header'        => "Content-Type: application/json\r\n",
        'content'       => json_encode($body),
        'ignore_errors' => true,
    ],
]);

$raw = file_get_contents($url, false, $context);

if ($raw === false) json_err('Gemini API request failed', 502);

$res = json_decode($raw, true);

if (!$res)                  json_err('Invalid response from Gemini', 502);
if (isset($res['error']))   json_err($res['error']['message'] ?? 'Gemini API error', 502);

$text  = $res['candidates'][0]['content']['parts'][0]['text'] ?? '';
$usage = $res['usageMetadata'] ?? [];

// ── input_tokens / output_tokens 키로 정규화 (프론트 변경 없음) ──
json_ok([
    'content'     => $text,
    'model'       => $model,
    'stop_reason' => $res['candidates'][0]['finishReason'] ?? 'STOP',
    'usage'       => [
        'input_tokens'  => $usage['promptTokenCount']     ?? 0,
        'output_tokens' => $usage['candidatesTokenCount'] ?? 0,
    ],
]);
