import type { Metadata } from 'next';
import './globals.css';

export const metadata: Metadata = {
  title: { default: 'Dev Notes', template: '%s | Dev Notes' },
  description: '기술 스택별 학습 기록',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="ko">
      <body className="min-h-screen antialiased">
        {/* 헤더 */}
        <header className="border-b border-white/[0.07] bg-[#0f1117]/90 backdrop-blur sticky top-0 z-10">
          <div className="mx-auto max-w-3xl px-5 h-14 flex items-center justify-between">
            <a href="/" className="font-bold text-base tracking-tight text-white hover:opacity-80 transition-opacity">
              Dev Notes
            </a>
            <a
              href="https://github.com/lukaPlayground/tutorial/tree/main/13-nextjs-markdown-blog"
              target="_blank"
              rel="noreferrer"
              className="text-xs text-white/40 hover:text-white/70 transition-colors"
            >
              GitHub →
            </a>
          </div>
        </header>

        {/* 본문 */}
        <main className="mx-auto max-w-3xl px-5 py-12">
          {children}
        </main>

        {/* 푸터 */}
        <footer className="border-t border-white/[0.07] mt-16">
          <div className="mx-auto max-w-3xl px-5 py-6 text-xs text-white/25 flex justify-between">
            <span>Dev Notes</span>
            <span>Next.js · gray-matter · react-markdown</span>
          </div>
        </footer>
      </body>
    </html>
  );
}
