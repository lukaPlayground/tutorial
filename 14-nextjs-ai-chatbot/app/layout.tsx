import type { Metadata } from 'next';
import './globals.css';

export const metadata: Metadata = {
  title: 'AI Chatbot — Next.js + Vercel AI SDK',
  description: 'Vercel AI SDK + OpenAI로 만든 스트리밍 챗봇',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="ko">
      <body className="antialiased">{children}</body>
    </html>
  );
}
