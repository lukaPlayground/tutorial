# Make 시나리오 설정 가이드

Make(Integromat)에서 아래 절차로 시나리오를 수동 구성한다.

## 사전 준비

- [make.com](https://make.com) 무료 계정 (1,000 ops/월)
- 티스토리 access_token ([발급 방법](../README.md#tistory-access-token-발급))

## 시나리오 구성

### 모듈 1: Webhooks > Custom webhook

1. 새 시나리오 생성 → 첫 모듈 `Webhooks > Custom webhook` 선택
2. **Add** 클릭 → Webhook 이름 입력: `tistory-blog-publish`
3. **Save** 클릭 → Webhook URL 복사 (`https://hook.eu2.make.com/...`)
4. 이 URL을 GitHub Secret `MAKE_WEBHOOK_URL`에 저장

### 모듈 2: HTTP > Make a request

1. 첫 모듈 오른쪽 `+` 클릭 → `HTTP > Make a request` 선택
2. 설정:
   - **URL**: `https://www.tistory.com/apis/post/write`
   - **Method**: `POST`
   - **Body type**: `application/x-www-form-urlencoded`
3. **Add item** 으로 아래 필드를 하나씩 추가:

| Key | Value (Map 탭에서 선택) |
|-----|----------------------|
| access_token | `{YOUR_TISTORY_ACCESS_TOKEN}` (직접 입력 또는 변수) |
| output | `json` |
| blogName | `lukaplayground` |
| title | `{{1.title}}` (Webhook 데이터) |
| content | `{{1.content}}` |
| visibility | `{{1.visibility}}` |
| tag | `{{1.tags}}` |

> `{{1.title}}` 은 첫 번째 모듈(Webhook)의 `title` 필드를 의미한다.

### 시나리오 활성화

1. 우측 하단 토글 → **On**
2. `Run once` 클릭 → Webhook URL로 테스트 curl 전송:
   ```bash
   curl -X POST "https://hook.eu2.make.com/YOUR_WEBHOOK_ID" \
     -H "Content-Type: application/json" \
     -d '{"title":"테스트","content":"<p>테스트</p>","tags":"테스트","visibility":"0"}'
   ```
3. 시나리오 히스토리에서 성공 확인

## 실행 결과 확인

Make 시나리오 히스토리:
- 초록 체크 = 성공
- Tistory 관리자 → 글 관리에서 임시저장 글 확인
