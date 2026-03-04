# Tutorial 33 — n8n + Make 블로그 포스팅 자동화

`tistory-blog/*.md` 파일을 push하면 자동으로 티스토리 블로그에 임시저장된다.
동일한 워크플로우를 **n8n** (로컬 Docker)과 **Make** (클라우드 SaaS) 두 가지로 구현한다.

## 전체 흐름

```
push: tistory-blog/52-ollama-pdf-summary.md
        ↓
GitHub Actions (blog-publish.yml)
  1. 변경된 .md 파일 감지
  2. front matter 추출 + Markdown → HTML
  3. JSON 페이로드 구성
        ↓ (동시 호출)
n8n Webhook  →  Tistory API     (로컬 Docker)
Make Webhook →  Tistory API     (클라우드)
```

## 사전 준비

### Tistory access_token 발급

1. [티스토리 앱 등록](https://www.tistory.com/guide/api/manage/register)
2. 앱 등록 후 `App ID`와 `Secret Key` 확인
3. 브라우저에서 아래 URL 접근 (YOUR_APP_ID 교체):
   ```
   https://www.tistory.com/oauth/authorize?client_id=YOUR_APP_ID&redirect_uri=https://www.tistory.com/oauth&response_type=code
   ```
4. 로그인 후 리다이렉트된 URL에서 `code` 파라미터 확인
5. access_token 교환:
   ```bash
   curl "https://www.tistory.com/oauth/access_token?client_id=YOUR_APP_ID&client_secret=YOUR_SECRET&redirect_uri=https://www.tistory.com/oauth&code=YOUR_CODE&grant_type=authorization_code"
   ```
6. 응답에서 `access_token=...` 값 저장

### GitHub Secrets 등록

레포지토리 → Settings → Secrets and variables → Actions → New secret:

| Secret 이름 | 값 |
|------------|---|
| `TISTORY_ACCESS_TOKEN` | 위에서 발급한 토큰 |
| `TISTORY_BLOG_NAME` | `lukaplayground` |
| `N8N_WEBHOOK_URL` | n8n Webhook URL (n8n 설정 후) |
| `MAKE_WEBHOOK_URL` | Make Webhook URL (Make 설정 후) |

## n8n 설정 (로컬 Docker)

### 1. n8n 실행

```bash
docker run -it --rm \
  -p 5678:5678 \
  -v n8n_data:/home/node/.n8n \
  -e TISTORY_ACCESS_TOKEN=your_token_here \
  -e TISTORY_BLOG_NAME=lukaplayground \
  n8nio/n8n
```

### 2. 워크플로우 Import

1. `http://localhost:5678` 접속
2. 우측 상단 ⋯ → **Import from file**
3. `n8n/workflow.json` 선택 → Import
4. Webhook URL 확인: `http://localhost:5678/webhook/tistory-publish`
5. 워크플로우 **Activate** 토글 On

### 3. 외부 접근 설정 (GitHub Actions 연동 시)

GitHub Actions에서 로컬 n8n에 접근하려면 ngrok 필요:

```bash
# ngrok 설치 후
ngrok http 5678
# → https://xxxx.ngrok-free.app 생성
```

GitHub Secret `N8N_WEBHOOK_URL` 에 `https://xxxx.ngrok-free.app/webhook/tistory-publish` 설정

### 4. 로컬 테스트

```bash
curl -X POST http://localhost:5678/webhook/tistory-publish \
  -H "Content-Type: application/json" \
  -d '{
    "title": "테스트 포스트",
    "content": "<h1>제목</h1><p>내용입니다.</p>",
    "tags": "테스트",
    "visibility": "0",
    "filename": "test.md"
  }'
```

## Make 설정 (클라우드)

→ [make/setup-guide.md](./make/setup-guide.md) 참조

## 스크립트 로컬 실행

```bash
cd scripts
pip install -r requirements.txt

# 단일 파일 테스트
python3 process_md.py /path/to/blog-file.md
```

## 파일 구조

```
33-n8n-make-blog-automation/
  ├── .github/
  │   └── blog-publish.yml    # GitHub Actions 트리거
  ├── scripts/
  │   ├── process_md.py       # Markdown → HTML 변환
  │   └── requirements.txt
  ├── n8n/
  │   └── workflow.json       # n8n 워크플로우 (import 가능)
  ├── make/
  │   └── setup-guide.md      # Make 설정 가이드
  └── README.md
```

## n8n vs Make 비교

| 항목 | n8n | Make |
|------|-----|------|
| 실행 환경 | 로컬 Docker | 클라우드 SaaS |
| 비용 | 무료 (무제한) | 무료 (1,000 ops/월) |
| 코드 노드 | O (JS) | X |
| 설치 | Docker 필요 | 없음 |
| 외부 접근 | ngrok 필요 | 자동 |
| UI | 기술 중심 | 비주얼 중심 |
