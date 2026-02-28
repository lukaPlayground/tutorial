---
title: "Next.js 시작하기 — App Router 핵심 정리"
date: "2026-02-20"
description: "App Router의 레이아웃 시스템, Server Component, 라우팅 방식을 예제 중심으로 정리한다."
tags: ["Next.js", "App Router", "React"]
---

## App Router란

Next.js 13부터 도입된 **App Router**는 `app/` 디렉토리를 기반으로 한다. 기존 `pages/` 방식과 다른 점은 기본적으로 모든 컴포넌트가 **React Server Component(RSC)** 라는 것이다.

## 파일 기반 라우팅

```
app/
├── layout.tsx      ← 루트 레이아웃 (모든 페이지 공유)
├── page.tsx        ← / 경로
└── posts/
    └── [slug]/
        └── page.tsx  ← /posts/:slug 경로
```

`layout.tsx`는 중첩 가능하다. `/posts` 전용 레이아웃이 필요하면 `app/posts/layout.tsx`를 추가하면 된다.

## Server Component vs Client Component

기본값은 Server Component. `useState`, `useEffect` 등 클라이언트 훅을 쓰려면 파일 최상단에 `"use client"` 지시어를 추가한다.

```tsx
// 서버 컴포넌트 — 기본값, 선언 불필요
export default async function Page() {
  const data = await fetch('https://api.example.com/data');
  // ...
}

// 클라이언트 컴포넌트
'use client';
import { useState } from 'react';

export default function Counter() {
  const [count, setCount] = useState(0);
  return <button onClick={() => setCount(c => c + 1)}>{count}</button>;
}
```

## generateStaticParams

동적 라우트(`[slug]`)를 정적으로 빌드하려면 `generateStaticParams`를 export한다.

```tsx
export async function generateStaticParams() {
  return getAllSlugs().map(slug => ({ slug }));
}
```

빌드 시 반환된 slug 수만큼 HTML이 미리 생성된다.

## 마무리

App Router는 처음엔 낯설지만, Server Component 덕분에 불필요한 클라이언트 JS를 줄일 수 있다. 레이아웃 중첩과 `generateStaticParams`를 익히면 대부분의 정적 사이트 요구사항을 커버할 수 있다.
