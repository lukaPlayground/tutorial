import { auth } from "@/auth";
import { redirect } from "next/navigation";
import { handleSignOut } from "@/app/actions";
import Image from "next/image";
import Link from "next/link";

export default async function ProtectedPage() {
  const session = await auth();

  if (!session?.user) {
    redirect("/");
  }

  return (
    <main style={{ minHeight: "100vh", background: "#0a0e17", color: "#e2e8f0" }}>
      {/* 헤더 */}
      <header style={{
        borderBottom: "1px solid rgba(255,255,255,0.07)",
        padding: "0 32px",
        height: "60px",
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
        background: "rgba(255,255,255,0.02)",
      }}>
        <span style={{ fontWeight: 700, fontSize: "15px", color: "#f1f5f9" }}>
          NextAuth Tutorial
        </span>
        <nav style={{ display: "flex", gap: "24px", alignItems: "center" }}>
          <Link href="/" style={{ fontSize: "13px", color: "rgba(255,255,255,0.5)", textDecoration: "none" }}>
            홈
          </Link>
          <Link href="/protected" style={{ fontSize: "13px", color: "#a5b4fc", textDecoration: "none" }}>
            Protected
          </Link>
        </nav>
      </header>

      {/* 메인 콘텐츠 */}
      <div style={{ maxWidth: "560px", margin: "0 auto", padding: "64px 24px" }}>

        {/* 보호됨 배지 */}
        <div style={{
          display: "inline-flex",
          alignItems: "center",
          gap: "6px",
          background: "rgba(34,197,94,0.1)",
          border: "1px solid rgba(34,197,94,0.2)",
          borderRadius: "99px",
          padding: "4px 14px",
          fontSize: "11px",
          fontWeight: 700,
          color: "#86efac",
          letterSpacing: "0.5px",
          marginBottom: "24px",
        }}>
          <span>●</span> PROTECTED PAGE
        </div>

        <h1 style={{
          fontSize: "26px",
          fontWeight: 800,
          letterSpacing: "-0.5px",
          marginBottom: "8px",
          color: "#f1f5f9",
        }}>
          로그인된 사용자만 볼 수 있다
        </h1>
        <p style={{ fontSize: "13px", color: "rgba(255,255,255,0.3)", marginBottom: "36px", lineHeight: 1.6 }}>
          middleware.ts가 비로그인 접근을 홈으로 redirect시킨다
        </p>

        {/* 세션 정보 카드 */}
        <div style={{
          background: "rgba(255,255,255,0.04)",
          border: "1px solid rgba(255,255,255,0.09)",
          borderRadius: "16px",
          overflow: "hidden",
          marginBottom: "16px",
        }}>
          {/* 프로필 섹션 */}
          <div style={{
            padding: "24px",
            borderBottom: "1px solid rgba(255,255,255,0.06)",
            display: "flex",
            alignItems: "center",
            gap: "16px",
          }}>
            {session.user.image && (
              <Image
                src={session.user.image}
                alt="avatar"
                width={52}
                height={52}
                style={{ borderRadius: "50%" }}
              />
            )}
            <div>
              <p style={{ fontWeight: 700, fontSize: "16px", color: "#f1f5f9" }}>
                {session.user.name}
              </p>
              <p style={{ fontSize: "12px", color: "rgba(255,255,255,0.4)", marginTop: "2px" }}>
                {session.user.email}
              </p>
            </div>
          </div>

          {/* 세션 JSON */}
          <div style={{ padding: "20px 24px" }}>
            <p style={{
              fontSize: "10px",
              fontWeight: 700,
              letterSpacing: "1px",
              color: "rgba(255,255,255,0.25)",
              textTransform: "uppercase",
              marginBottom: "12px",
            }}>
              Session Object
            </p>
            <pre style={{
              fontFamily: "'SF Mono', 'Fira Code', monospace",
              fontSize: "11.5px",
              lineHeight: 1.7,
              color: "#86efac",
              background: "rgba(0,0,0,0.3)",
              padding: "16px",
              borderRadius: "10px",
              overflow: "auto",
            }}>
              {JSON.stringify(session, null, 2)}
            </pre>
          </div>
        </div>

        {/* 버튼 영역 */}
        <div style={{ display: "flex", gap: "10px" }}>
          <Link
            href="/"
            style={{
              flex: 1,
              background: "rgba(255,255,255,0.05)",
              border: "1px solid rgba(255,255,255,0.1)",
              borderRadius: "10px",
              padding: "11px",
              fontSize: "13px",
              fontWeight: 600,
              color: "rgba(255,255,255,0.45)",
              textDecoration: "none",
              textAlign: "center",
            }}
          >
            ← 홈으로
          </Link>
          <form action={handleSignOut} style={{ flex: 1 }}>
            <button
              type="submit"
              style={{
                width: "100%",
                background: "rgba(239,68,68,0.1)",
                border: "1px solid rgba(239,68,68,0.2)",
                borderRadius: "10px",
                padding: "11px",
                fontSize: "13px",
                fontWeight: 600,
                color: "#fca5a5",
                cursor: "pointer",
              }}
            >
              로그아웃
            </button>
          </form>
        </div>
      </div>
    </main>
  );
}
