'use client';

import { useChat } from '@ai-sdk/react';
import { TextStreamChatTransport } from 'ai';
import { useEffect, useRef, useState } from 'react';

const transport = new TextStreamChatTransport({ api: '/api/chat' });

export default function ChatPage() {
  const { messages, sendMessage, status, error } = useChat({ transport });
  const [input, setInput] = useState('');
  const bottomRef = useRef<HTMLDivElement>(null);

  const isLoading = status === 'streaming' || status === 'submitted';

  // 새 메시지가 올 때마다 스크롤 하단 이동
  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  const handleSend = () => {
    const text = input.trim();
    if (!text || isLoading) return;
    sendMessage({ text });
    setInput('');
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  };

  return (
    <div className="flex flex-col h-screen" style={{ background: 'var(--bg)' }}>

      {/* 헤더 */}
      <header style={{ borderBottom: '1px solid var(--border)', background: 'rgba(15,17,23,0.9)' }}
        className="sticky top-0 z-10 backdrop-blur">
        <div className="mx-auto max-w-3xl px-5 h-14 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-7 h-7 rounded-full bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-xs">
              ✦
            </div>
            <span className="font-semibold text-sm tracking-tight text-white">AI Chatbot</span>
            <span className="text-xs px-2 py-0.5 rounded-full"
              style={{ background: 'rgba(99,102,241,0.12)', border: '1px solid rgba(99,102,241,0.25)', color: '#a5b4fc' }}>
              Pollinations.ai
            </span>
          </div>
          <a href="https://github.com/lukaPlayground/tutorial/tree/main/14-nextjs-ai-chatbot"
            target="_blank" rel="noreferrer"
            className="text-xs transition-colors"
            style={{ color: 'var(--muted)' }}>
            GitHub →
          </a>
        </div>
      </header>

      {/* 메시지 목록 */}
      <main className="flex-1 overflow-y-auto">
        <div className="mx-auto max-w-3xl px-5 py-8 flex flex-col gap-6">

          {/* 첫 진입 안내 */}
          {messages.length === 0 && (
            <div className="flex flex-col items-center justify-center py-20 gap-4 text-center">
              <div className="w-14 h-14 rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-2xl">
                ✦
              </div>
              <div>
                <p className="font-semibold text-white mb-1">무엇이든 물어보세요</p>
                <p className="text-sm" style={{ color: 'var(--muted)' }}>
                  Vercel AI SDK + Pollinations.ai로 구동 (API 키 불필요)
                </p>
              </div>
              {/* 예시 질문 */}
              <div className="flex flex-wrap gap-2 justify-center mt-2">
                {[
                  'Next.js App Router를 설명해줘',
                  'React Server Component란?',
                  'TypeScript를 써야 하는 이유',
                  'Tailwind CSS 장단점',
                ].map(q => (
                  <button key={q}
                    onClick={() => { sendMessage({ text: q }); }}
                    className="text-xs px-3 py-1.5 rounded-full transition-colors cursor-pointer"
                    style={{
                      background: 'rgba(255,255,255,0.04)',
                      border: '1px solid var(--border)',
                      color: 'var(--muted)',
                    }}>
                    {q}
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* 메시지 버블 */}
          {messages.map(m => (
            <div key={m.id} className={`flex gap-3 ${m.role === 'user' ? 'flex-row-reverse' : 'flex-row'}`}>
              {/* 아바타 */}
              <div className={`w-7 h-7 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold
                ${m.role === 'user'
                  ? 'bg-white/10 text-white/60'
                  : 'bg-gradient-to-br from-violet-500 to-indigo-600 text-white'}`}>
                {m.role === 'user' ? 'U' : '✦'}
              </div>

              {/* 말풍선 */}
              <div className={`max-w-[75%] rounded-2xl px-4 py-3 text-sm leading-relaxed whitespace-pre-wrap
                ${m.role === 'user'
                  ? 'rounded-tr-sm text-white'
                  : 'rounded-tl-sm'}` }
                style={m.role === 'user'
                  ? { background: 'rgba(99,102,241,0.2)', border: '1px solid rgba(99,102,241,0.3)' }
                  : { background: 'var(--surface)', border: '1px solid var(--border)', color: 'var(--text)' }}>
                {m.parts.map((part, i) =>
                  part.type === 'text' ? <span key={i}>{part.text}</span> : null
                )}
              </div>
            </div>
          ))}

          {/* 로딩 인디케이터 */}
          {isLoading && (
            <div className="flex gap-3">
              <div className="w-7 h-7 rounded-full bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-xs text-white">✦</div>
              <div className="rounded-2xl rounded-tl-sm px-4 py-3 flex items-center gap-1.5"
                style={{ background: 'var(--surface)', border: '1px solid var(--border)' }}>
                <span className="w-1.5 h-1.5 rounded-full bg-white/40 animate-bounce" style={{ animationDelay: '0ms' }} />
                <span className="w-1.5 h-1.5 rounded-full bg-white/40 animate-bounce" style={{ animationDelay: '150ms' }} />
                <span className="w-1.5 h-1.5 rounded-full bg-white/40 animate-bounce" style={{ animationDelay: '300ms' }} />
              </div>
            </div>
          )}

          {/* 에러 */}
          {error && (
            <div className="text-xs text-center py-2 px-4 rounded-xl"
              style={{ background: 'rgba(239,68,68,0.1)', border: '1px solid rgba(239,68,68,0.2)', color: '#fca5a5' }}>
              오류가 발생했습니다. 잠시 후 다시 시도해주세요.
            </div>
          )}

          <div ref={bottomRef} />
        </div>
      </main>

      {/* 입력창 */}
      <footer style={{ borderTop: '1px solid var(--border)', background: 'rgba(15,17,23,0.95)' }}
        className="backdrop-blur">
        <div className="mx-auto max-w-3xl px-5 py-4">
          <div className="flex gap-3 items-end">
            <textarea
              value={input}
              onChange={e => setInput(e.target.value)}
              onKeyDown={handleKeyDown}
              placeholder="메시지를 입력하세요... (Shift+Enter: 줄바꿈)"
              rows={1}
              className="flex-1 resize-none rounded-xl px-4 py-3 text-sm outline-none transition-colors placeholder:text-white/20"
              style={{
                background: 'var(--surface)',
                border: '1px solid var(--border)',
                color: 'var(--text)',
                maxHeight: '160px',
                overflowY: 'auto',
              }}
              onInput={e => {
                const t = e.currentTarget;
                t.style.height = 'auto';
                t.style.height = Math.min(t.scrollHeight, 160) + 'px';
              }}
            />
            <button
              onClick={handleSend}
              disabled={isLoading || !input.trim()}
              className="h-10 w-10 rounded-xl flex items-center justify-center flex-shrink-0 transition-all disabled:opacity-30 disabled:cursor-not-allowed"
              style={{ background: 'linear-gradient(135deg, #6366f1, #8b5cf6)' }}>
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M2 8L14 8M14 8L9 3M14 8L9 13" stroke="white" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"/>
              </svg>
            </button>
          </div>
          <p className="text-center text-xs mt-2" style={{ color: 'rgba(255,255,255,0.18)' }}>
            AI는 실수할 수 있습니다. 중요한 정보는 직접 확인하세요.
          </p>
        </div>
      </footer>

    </div>
  );
}
