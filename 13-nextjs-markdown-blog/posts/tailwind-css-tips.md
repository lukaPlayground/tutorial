---
title: "Tailwind CSS 실전 팁 — 자주 쓰는 패턴 모음"
date: "2026-02-27"
description: "Tailwind를 쓰면서 반복적으로 쓰게 되는 패턴과 놓치기 쉬운 유틸리티를 정리한다."
tags: ["Tailwind CSS", "CSS", "프론트엔드"]
---

## 왜 Tailwind인가

CSS-in-JS나 CSS Modules 대비 Tailwind의 장점은 **스타일 결정을 HTML에서 바로** 한다는 것이다. 파일을 오가지 않아도 되고, 클래스명 짓는 고민이 없다.

## 자주 쓰는 패턴

### 수평 중앙 정렬 컨테이너

```html
<div class="mx-auto max-w-3xl px-4">
  <!-- 콘텐츠 -->
</div>
```

### Flexbox 중앙 정렬

```html
<div class="flex items-center justify-center gap-4">
  <span>왼쪽</span>
  <span>오른쪽</span>
</div>
```

### 반응형 그리드

```html
<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
  <!-- 카드들 -->
</div>
```

### 텍스트 말줄임 (여러 줄)

```html
<p class="line-clamp-2">
  긴 텍스트가 여기에 들어갑니다...
</p>
```

### 다크모드

```html
<div class="bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100">
  다크모드 대응 요소
</div>
```

## 놓치기 쉬운 유틸리티

| 클래스 | 역할 |
|--------|------|
| `truncate` | 한 줄 텍스트 말줄임 |
| `sr-only` | 시각적 숨김 (스크린리더는 읽음) |
| `not-sr-only` | `sr-only` 해제 |
| `divide-y` | 자식 요소 사이에 구분선 |
| `space-y-4` | 자식 요소 사이 세로 간격 |
| `aspect-video` | 16:9 비율 |
| `group-hover:` | 부모 hover 시 자식 스타일 |

## @apply로 반복 줄이기

동일한 클래스 조합이 반복되면 `@apply`로 추출한다.

```css
/* globals.css */
.btn-primary {
  @apply rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700;
}
```

단, Tailwind 팀은 컴포넌트 추출(`<Button />`)을 먼저 권장한다.

## 마무리

Tailwind의 진가는 팀 프로젝트에서 나온다. 디자인 토큰이 `tailwind.config`에 집중되고, 누가 써도 일관된 스타일이 나온다. 처음엔 클래스가 길어 보여도 익숙해지면 CSS 파일을 열 일이 거의 없어진다.
