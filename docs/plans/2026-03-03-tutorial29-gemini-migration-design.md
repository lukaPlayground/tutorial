# Tutorial 29 Claude → Gemini 마이그레이션 설계

**날짜**: 2026-03-03
**목적**: Tutorial 29의 AI 백엔드를 유료 Claude API에서 무료 Gemini API로 교체

---

## 목표

튜토리얼 학습자가 API 키 비용 없이 따라할 수 있도록, Claude API(유료)를 Gemini API(무료 티어)로 교체한다.
코드 구조·UI는 그대로 유지. AI 호출 레이어만 교체.

---

## 코드 변경

### 폴더 이름
`29-claude-chatbot/` → `29-gemini-chatbot/`

### chat.php
- Anthropic PHP SDK 제거
- Gemini REST API 직접 호출 (`file_get_contents` + `stream_context_create`)
- 엔드포인트: `https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key={KEY}`
- 메시지 변환: `{role, content}` → `{role, parts:[{text}]}`
- 역할 변환: `assistant` → `model`
- 시스템 프롬프트: `system_instruction.parts[0].text` 필드로 분리
- 토큰 응답: `usageMetadata.promptTokenCount / candidatesTokenCount`

### composer.json
삭제 (Gemini는 SDK 없이 PHP 내장 함수로 호출)

### composer.lock, vendor/
삭제

### index.php
- 모델 옵션 3종 교체:
  - `gemini-2.0-flash` (기본값, 빠름/무료)
  - `gemini-1.5-flash` (안정)
  - `gemini-1.5-pro` (고성능)
- `conversationHistory` 구조: 프론트는 `{role, content}` 유지
- 토큰 표시 키: `input_tokens` / `output_tokens` (서버에서 매핑해서 내려줌 → 프론트 변경 없음)

### .env.example
`ANTHROPIC_API_KEY` → `GEMINI_API_KEY`

---

## 문서 변경

| 파일 | 변경 |
|------|------|
| `README.md` | 튜토리얼 29 태그·설명·실행 안내 업데이트 |
| `index.html` | 카드 내용 업데이트 (Gemini, 무료) |
| `49-gemini-chatbot.md` | 블로그 글 재작성 (tistory-blog/ + 29-gemini-chatbot/ 복사) |
| `thumbnail-gemini-chatbot.html` | 썸네일 재작성 (tistory-thumbnail-html/ + 29-gemini-chatbot/ 복사) |

---

## Claude vs Gemini 차이점 요약

| 항목 | Claude (Tutorial 29) | Gemini (Tutorial 29 변경 후) |
|------|---------------------|----------------------------|
| API 키 비용 | 유료 | **무료** |
| PHP SDK | `anthropic-ai/sdk` (Composer) | **없음** (직접 HTTP) |
| 메시지 역할 | `user` / `assistant` | `user` / **`model`** |
| 메시지 포맷 | `{role, content}` | `{role, parts:[{text}]}` |
| 시스템 프롬프트 | `system:` 파라미터 | **`system_instruction`** 별도 필드 |
| 토큰 필드 | `usage->inputTokens` | **`usageMetadata->promptTokenCount`** |
