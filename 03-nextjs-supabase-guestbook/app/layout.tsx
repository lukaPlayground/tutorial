import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "방명록 — Luka's Playground",
  description: "Next.js + Supabase로 만든 방명록",
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
