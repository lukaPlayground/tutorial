# 방명록 — Next.js + Supabase

Next.js App Router + Supabase로 만든 풀스택 방명록.

## 시작하기

### 1. Supabase 설정

[Supabase](https://supabase.com)에서 새 프로젝트 생성 후 SQL Editor에서 실행:

```sql
create table messages (
  id         bigserial primary key,
  name       text      not null,
  content    text      not null,
  created_at timestamptz default now()
);

alter table messages enable row level security;
create policy "Read all"   on messages for select using (true);
create policy "Insert all" on messages for insert with check (true);
create policy "Delete all" on messages for delete using (true);
```

### 2. 환경 변수 설정

```bash
cp .env.local.example .env.local
```

`.env.local`에 Supabase 키 입력:

```
NEXT_PUBLIC_SUPABASE_URL=https://your-project.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=your-anon-key
```

### 3. 로컬 실행

```bash
npm install
npm run dev
```

[http://localhost:3000](http://localhost:3000) 확인.

---

## Vercel 배포

### 1. Vercel에 Import

[vercel.com/new](https://vercel.com/new) → GitHub 연결 → `tutorial` 레포 선택 → **Root Directory**를 `03-nextjs-supabase-guestbook`으로 지정.

### 2. 환경 변수 추가

Vercel 프로젝트 Settings → Environment Variables:

| Name | Value |
|------|-------|
| `NEXT_PUBLIC_SUPABASE_URL` | Supabase 프로젝트 URL |
| `NEXT_PUBLIC_SUPABASE_ANON_KEY` | Supabase anon key |

### 3. Deploy

환경 변수 저장 후 Redeploy → 완료.

---

## 기술 스택

- **Next.js 15** (App Router)
- **TypeScript**
- **Supabase** (PostgreSQL)
- **TailwindCSS**
- **Server Action** (`'use server'`)
