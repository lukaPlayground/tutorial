'use client';

import { useRef, useTransition } from 'react';
import { addTodo } from '../actions';

export function AddTodoForm() {
  const ref = useRef<HTMLFormElement>(null);
  const [pending, startTransition] = useTransition();

  return (
    <form
      ref={ref}
      action={(formData) => {
        const text = formData.get('text') as string;
        startTransition(async () => {
          await addTodo(text);
          ref.current?.reset();
        });
      }}
      className="flex gap-3 mb-8"
    >
      <input
        name="text"
        type="text"
        placeholder="할 일을 입력하세요..."
        required
        disabled={pending}
        className="flex-1 px-4 py-3 rounded-xl text-sm outline-none transition-colors placeholder:opacity-25 disabled:opacity-50"
        style={{
          background: 'var(--surface)',
          border: '1px solid var(--border)',
          color: 'var(--text)',
        }}
      />
      <button
        type="submit"
        disabled={pending}
        className="px-5 py-3 rounded-xl text-sm font-semibold text-white transition-opacity disabled:opacity-40"
        style={{ background: 'linear-gradient(135deg, #6366f1, #8b5cf6)' }}
      >
        {pending ? '추가 중...' : '추가'}
      </button>
    </form>
  );
}
