# Tutorial 29 Gemini Migration Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Tutorial 29의 AI 백엔드를 Claude API(유료)에서 Gemini API(무료)로 교체한다.

**Architecture:** 기존 PHP 2파일 구조(chat.php + index.php) 유지. chat.php에서 Anthropic PHP SDK 제거, Gemini REST API를 `file_get_contents`로 직접 호출. 프론트엔드 conversationHistory는 `{role, content}` 그대로 유지하고 서버에서 Gemini 포맷으로 변환.

**Tech Stack:** PHP 8.0+, Gemini REST API (`generativelanguage.googleapis.com`), Vanilla JS, Fetch API. Composer 불필요.

---

## Task 1: 폴더 이름 변경 (git mv)

**Files:**
- Rename: `29-claude-chatbot/` → `29-gemini-chatbot/`

**Step 1: git mv로 폴더 이름 변경**

```bash
cd /Users/work6/Desktop/ai-code/tutorial
git mv 29-claude-chatbot 29-gemini-chatbot
```

**Step 2: 확인**

```bash
ls | grep 29
```
Expected: `29-gemini-chatbot`

---

## Task 2: Composer 파일 제거

**Files:**
- Delete: `29-gemini-chatbot/composer.json`
- Delete: `29-gemini-chatbot/composer.lock`
- Delete: `29-gemini-chatbot/vendor/` (디렉토리 전체)

**Step 1: composer 관련 파일/폴더 삭제**

```bash
cd /Users/work6/Desktop/ai-code/tutorial/29-gemini-chatbot
rm -f composer.json composer.lock
rm -rf vendor/
```

**Step 2: .gitignore 업데이트**

기존 `.gitignore`에서 vendor 관련 줄 확인 후 유지(vendor는 이미 ignore됨). 내용 그대로 둬도 무방.

---

## Task 3: .env.example 업데이트

**Files:**
- Modify: `29-gemini-chatbot/.env.example`

**Step 1: 파일 내용 교체**

```
GEMINI_API_KEY=your_gemini_api_key_here
```

> API 키 발급: https://aistudio.google.com → Get API key → Create API key (무료, 카드 불필요)

---

## Task 4: chat.php 재작성

**Files:**
- Rewrite: `29-gemini-chatbot/chat.php`

**Step 1: chat.php 전체 교체**

```php
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
```

**Step 2: PHP 문법 체크**

```bash
php -l 29-gemini-chatbot/chat.php
```
Expected: `No syntax errors detected`

---

## Task 5: index.php 업데이트

**Files:**
- Modify: `29-gemini-chatbot/index.php`

변경 사항은 최소화. 바꿀 부분:
1. `<title>` 태그
2. 로고 텍스트
3. 모델 `<select>` 옵션 3종
4. 환영 메시지 타이틀

**Step 1: title 변경**

```
<title>AI 챗봇 — Claude API</title>
→
<title>AI 챗봇 — Gemini API</title>
```

**Step 2: 로고 텍스트 변경**

```
🤖 Claude Chat
→
🤖 Gemini Chat
```

**Step 3: 모델 select 교체**

```html
<select id="modelSelect">
  <option value="gemini-2.0-flash">gemini-2.0-flash (빠름/무료)</option>
  <option value="gemini-1.5-flash">gemini-1.5-flash (안정)</option>
  <option value="gemini-1.5-pro">gemini-1.5-pro (고성능)</option>
</select>
```

**Step 4: 환영 메시지 타이틀 변경**

```
Claude AI 챗봇
→
Gemini AI 챗봇
```

**Step 5: PHP 문법 체크**

```bash
php -l 29-gemini-chatbot/index.php
```
Expected: `No syntax errors detected`

---

## Task 6: 동작 검증

**Step 1: .env 생성 후 서버 실행**

```bash
cd /Users/work6/Desktop/ai-code/tutorial/29-gemini-chatbot
cp .env.example .env
# .env 파일 열어서 GEMINI_API_KEY 실제 값 입력
php -S localhost:8080
```

**Step 2: 브라우저에서 확인**

- `http://localhost:8080` 접속
- 메시지 전송 → AI 응답 확인
- 멀티턴 대화 확인 (대화 이어지는지)
- 토큰 사용량 표시 확인
- 모델 변경 후 전송 확인

**Step 3: 서버 종료**

```bash
# Ctrl+C
```

---

## Task 7: 블로그 글 작성 + 복사

**Files:**
- Create: `/Users/work6/Desktop/ai-code/tistory-blog/49-gemini-chatbot.md`
- Copy to: `/Users/work6/Desktop/ai-code/tutorial/29-gemini-chatbot/blog.md`

**블로그 구조 (CLAUDE.md 스타일 규칙 준수)**

```
---
title: "[Gemini API] PHP로 AI 챗봇 만들기 — 무료 API · SDK 없이 · 멀티턴"
category: 튜토리얼 > AI
tags: [PHP, GeminiAPI, 챗봇, 멀티턴, 무료API, REST]
date: 2026-03-04
---
```

섹션 순서:
1. **왜 만들었나** — Claude API 유료 → Gemini 무료로 바꾼 이유, API 키 발급 장벽 제거
2. **기술 상세** — 아키텍처 (PHP 2파일, Composer 없음), Gemini REST API 구조, 메시지 포맷 차이
3. **실제 소스 코드** — chat.php (메시지 변환 로직, `file_get_contents` 호출), index.php (JS 상태 관리)
4. **비교 테이블** — Claude SDK vs Gemini REST, `user/assistant` vs `user/model`, 비용 비교
5. **삽질 기록** — `assistant`→`model` 역할명, `system_instruction` 별도 필드, `usageMetadata` 필드명
6. **마무리** — 핵심 3가지: 무료 API 키 발급, REST 직접 호출, 클라이언트 상태 관리

**Step 1: 블로그 글 작성 후 복사**

```bash
cp /Users/work6/Desktop/ai-code/tistory-blog/49-gemini-chatbot.md \
   /Users/work6/Desktop/ai-code/tutorial/29-gemini-chatbot/blog.md
```

---

## Task 8: 썸네일 작성 + 복사

**Files:**
- Create: `/Users/work6/Desktop/ai-code/tistory-thumbnail-html/thumbnail-gemini-chatbot.html`
- Copy to: `/Users/work6/Desktop/ai-code/tutorial/29-gemini-chatbot/thumbnail.html`

**썸네일 스펙 (CLAUDE.md 규칙)**
- 1200×630px, 다크 배경 (#0a0e17)
- 왼쪽 패널 (320px): 채팅 UI 목업 + Gemini API 호출 코드 스니펫 + 토큰 사용량 박스
- 오른쪽 패널:
  - 배지: `AI · PHP` + `Tutorial 29`
  - 제목: `PHP AI 챗봇 만들기`
  - 부제: `Gemini API · 무료 · SDK 없이 · 멀티턴 대화`
  - 기술 배지: `PHP 8`, `Gemini API`, `Fetch API`, `Vanilla JS`
  - 특징 4가지: 💬 멀티턴 대화, 🆓 무료 API, 🔄 모델 선택 (3종), 📊 토큰 사용량 표시
  - 푸터: 👨‍💻 Luka + `conversationHistory[]`
- 컬러: 파란-청록 그라디언트 (#4285f4 → #34a853, Google 컬러)

코드 스니펫에 표시할 내용 (chat.php 핵심):
```
// SDK 없이 직접 HTTP 호출
$url = "generativelanguage..."
      ."?key={$apiKey}";

$raw = file_get_contents(
  $url, false,
  stream_context_create([...])
);

// assistant → model 변환
'role' => $msg['role'] === 'assistant'
        ? 'model' : $msg['role']
```

**Step 1: 썸네일 작성 후 복사**

```bash
cp /Users/work6/Desktop/ai-code/tistory-thumbnail-html/thumbnail-gemini-chatbot.html \
   /Users/work6/Desktop/ai-code/tutorial/29-gemini-chatbot/thumbnail.html
```

---

## Task 9: README.md 업데이트

**Files:**
- Modify: `/Users/work6/Desktop/ai-code/tutorial/README.md`

**Step 1: 완료 테이블 29번 행 교체**

```markdown
| 29 | [29-gemini-chatbot](./29-gemini-chatbot) | `[Gemini API]` 나만의 AI 챗봇 웹앱 만들기 (PHP · 무료 · SDK 없이 · 멀티턴) | 로컬 실행 ↓ | [Blog](https://lukaplayground.tistory.com/54) |
```

**Step 2: 로컬 실행 안내 (PHP 섹션) 29번 업데이트**

```markdown
### 29-gemini-chatbot

​```bash
cd 29-gemini-chatbot
cp .env.example .env     # .env 열어서 GEMINI_API_KEY 입력
php -S localhost:8080
# → http://localhost:8080
​```

> **API 키 발급**: [aistudio.google.com](https://aistudio.google.com) → Get API key → Create API key (무료, 카드 불필요)
```

**Step 3: 진행 예정 섹션 — Claude API 항목 유지 (이미 체크됨)**

변경 없음.

**Step 4: Last updated 날짜 업데이트**

```
_Last updated: 2026-03-04 (3)_
```

---

## Task 10: index.html (GitHub Pages) 업데이트

**Files:**
- Modify: `/Users/work6/Desktop/ai-code/tutorial/index.html`

**Step 1: Tutorial 29 카드 내용 업데이트**

AI 섹션의 Tutorial 29 카드에서:
- 제목: `Claude API AI 챗봇` → `Gemini AI 챗봇`
- 설명: `Anthropic PHP SDK · 멀티턴` → `무료 API · SDK 없이 · 멀티턴`
- 태그 배지: `Claude API` → `Gemini API`, `Composer` 제거

---

## Task 11: Git commit & push

**Step 1: 변경 파일 확인**

```bash
cd /Users/work6/Desktop/ai-code/tutorial
git status
```

**Step 2: 스테이징 & 커밋**

```bash
git add 29-gemini-chatbot/ README.md index.html
git commit -m "Migrate Tutorial 29: Claude API → Gemini API (무료 · SDK 없이)"
```

**Step 3: 푸시**

```bash
git push origin main
```

---

## 참고: Claude vs Gemini 메시지 포맷 차이

```
[Claude]                          [Gemini]
messages: [                       contents: [
  {                                 {
    role: "user",                     role: "user",
    content: "안녕"                   parts: [{ text: "안녕" }]
  },                                },
  {                                 {
    role: "assistant",  →             role: "model",
    content: "안녕하세요"              parts: [{ text: "안녕하세요" }]
  }                                 }
]                                 ]

system: "..."                     system_instruction: {
                                    parts: [{ text: "..." }]
                                  }
```
