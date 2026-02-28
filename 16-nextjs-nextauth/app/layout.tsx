import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "NextAuth Tutorial — 소셜 로그인",
  description: "Next.js + NextAuth v5로 GitHub/Google 소셜 로그인 구현",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="ko">
      <body>{children}</body>
    </html>
  );
}
