import { getTodos } from './actions';
import { AddTodoForm } from './components/AddTodoForm';
import { TodoItem } from './components/TodoItem';

export const dynamic = 'force-dynamic';

export default async function Home() {
  const todos = await getTodos();
  const doneCount = todos.filter((t) => t.done).length;

  return (
    <div className="min-h-screen" style={{ background: 'var(--bg)' }}>
      <div className="mx-auto max-w-xl px-5 py-14">

        {/* 헤더 */}
        <header className="mb-10">
          <p className="text-xs font-bold uppercase tracking-widest mb-3" style={{ color: 'var(--muted)' }}>
            Tutorial · 15
          </p>
          <h1 className="text-3xl font-extrabold tracking-tight mb-2" style={{ color: 'var(--text)' }}>
            Todo App
          </h1>
          <p className="text-sm" style={{ color: 'var(--muted)' }}>
            Next.js · Prisma · PostgreSQL
          </p>
        </header>

        {/* 입력 폼 */}
        <AddTodoForm />

        {/* 통계 */}
        {todos.length > 0 && (
          <div className="flex items-center justify-between mb-4">
            <span className="text-xs font-semibold" style={{ color: 'var(--muted)' }}>
              전체 {todos.length}개
            </span>
            <span className="text-xs font-semibold" style={{ color: '#a5b4fc' }}>
              완료 {doneCount} / {todos.length}
            </span>
          </div>
        )}

        {/* Todo 목록 */}
        {todos.length === 0 ? (
          <div className="text-center py-20">
            <p className="text-sm" style={{ color: 'var(--muted)' }}>
              할 일을 추가해보세요
            </p>
          </div>
        ) : (
          <ul className="flex flex-col gap-2">
            {todos.map((todo) => (
              <TodoItem key={todo.id} todo={todo} />
            ))}
          </ul>
        )}

        {/* 모두 완료 */}
        {todos.length > 0 && doneCount === todos.length && (
          <p className="text-center text-xs mt-8" style={{ color: '#a5b4fc' }}>
            모든 할 일을 완료했습니다 🎉
          </p>
        )}

        {/* 푸터 */}
        <footer className="mt-16 text-center">
          <a
            href="https://github.com/lukaPlayground/tutorial/tree/main/15-nextjs-prisma-todo"
            target="_blank"
            rel="noreferrer"
            className="text-xs transition-colors"
            style={{ color: 'var(--muted)' }}
          >
            GitHub →
          </a>
        </footer>

      </div>
    </div>
  );
}
