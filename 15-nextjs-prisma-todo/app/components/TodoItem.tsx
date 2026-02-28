'use client';

import { useTransition } from 'react';
import { toggleTodo, deleteTodo } from '../actions';

type Todo = {
  id: string;
  text: string;
  done: boolean;
  createdAt: Date;
};

export function TodoItem({ todo }: { todo: Todo }) {
  const [pending, startTransition] = useTransition();

  return (
    <li
      className="flex items-center gap-3 px-4 py-3.5 rounded-xl group transition-colors"
      style={{
        background: 'var(--surface)',
        border: '1px solid var(--border)',
        opacity: pending ? 0.5 : 1,
      }}
    >
      {/* 완료 토글 */}
      <button
        onClick={() => startTransition(() => toggleTodo(todo.id, todo.done))}
        disabled={pending}
        className="w-5 h-5 rounded-full flex-shrink-0 flex items-center justify-center border-2 transition-colors"
        style={
          todo.done
            ? { background: '#6366f1', borderColor: '#6366f1', color: '#fff' }
            : { borderColor: 'rgba(255,255,255,0.2)', color: 'transparent' }
        }
      >
        {todo.done && (
          <svg width="10" height="8" viewBox="0 0 10 8" fill="none">
            <path d="M1 4L3.5 6.5L9 1" stroke="white" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        )}
      </button>

      {/* 텍스트 */}
      <span
        className="flex-1 text-sm"
        style={{
          color: todo.done ? 'rgba(255,255,255,0.25)' : 'var(--text)',
          textDecoration: todo.done ? 'line-through' : 'none',
        }}
      >
        {todo.text}
      </span>

      {/* 삭제 버튼 */}
      <button
        onClick={() => startTransition(() => deleteTodo(todo.id))}
        disabled={pending}
        className="opacity-0 group-hover:opacity-100 w-6 h-6 rounded-lg flex items-center justify-center text-xs transition-opacity"
        style={{ background: 'rgba(239,68,68,0.12)', color: '#f87171' }}
      >
        ×
      </button>
    </li>
  );
}
