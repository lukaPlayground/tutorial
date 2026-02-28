---
title: "React Server Components 깊게 파헤치기"
date: "2026-02-23"
description: "RSC가 왜 등장했고, 번들 사이즈·데이터 페칭·스트리밍에 어떤 영향을 미치는지 구체적으로 살펴본다."
tags: ["React", "RSC", "성능 최적화"]
---

## 왜 RSC인가

기존 React는 모든 컴포넌트를 클라이언트에서 실행했다. 즉, 컴포넌트 코드가 JS 번들에 포함돼 브라우저로 전송된다. 문제는 **데이터를 가져오기 위해서만 쓰이는 라이브러리**(예: `date-fns`, DB 클라이언트)까지 번들에 묶인다는 것이다.

RSC는 이 컴포넌트들을 서버에서 실행한다. 클라이언트로 전송되는 건 **렌더링된 결과물**뿐이고, 컴포넌트 코드 자체는 번들에 포함되지 않는다.

## 번들 사이즈 비교

| 방식 | 라이브러리 번들 포함 여부 | 초기 JS 크기 |
|------|--------------------------|-------------|
| Client Component | 포함 | 크다 |
| Server Component | 미포함 | 작다 |

## 데이터 페칭

RSC에서는 `async/await`를 컴포넌트 안에서 직접 쓸 수 있다.

```tsx
// Server Component — DB 직접 접근 가능
export default async function PostList() {
  const posts = await db.query('SELECT * FROM posts ORDER BY date DESC');

  return (
    <ul>
      {posts.map(post => (
        <li key={post.id}>{post.title}</li>
      ))}
    </ul>
  );
}
```

클라이언트에서 `useEffect + fetch`로 했던 것을 서버에서 단 한 번의 렌더링으로 처리한다.

## Streaming과 Suspense

RSC는 **스트리밍**을 지원한다. `<Suspense>`로 감싸진 컴포넌트는 데이터가 준비될 때까지 fallback을 보여주고, 준비되면 교체된다.

```tsx
import { Suspense } from 'react';

export default function Page() {
  return (
    <div>
      <h1>블로그</h1>
      <Suspense fallback={<p>로딩 중...</p>}>
        <PostList />
      </Suspense>
    </div>
  );
}
```

## 제약사항

- `useState`, `useEffect`, 브라우저 API 사용 불가
- 이벤트 핸들러(`onClick` 등) 사용 불가
- 위가 필요하면 `"use client"` 추가

## 마무리

RSC는 은총알이 아니다. 인터랙션이 없는 컴포넌트, 데이터를 서버에서 가져오는 컴포넌트에 적합하다. 반대로 상태나 이벤트가 필요한 컴포넌트는 그냥 Client Component를 쓰면 된다. 둘을 적절히 섞는 게 핵심이다.
