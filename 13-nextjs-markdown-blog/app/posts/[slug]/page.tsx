import { notFound } from 'next/navigation';
import Link from 'next/link';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import rehypeHighlight from 'rehype-highlight';
import { getAllSlugs, getPostBySlug } from '@/lib/posts';
import type { Metadata } from 'next';

type Props = { params: Promise<{ slug: string }> };

/* 빌드 시 정적 페이지 생성 */
export async function generateStaticParams() {
  return getAllSlugs().map(slug => ({ slug }));
}

/* 페이지별 메타데이터 */
export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const { slug } = await params;
  try {
    const post = getPostBySlug(slug);
    return { title: post.title, description: post.description };
  } catch {
    return {};
  }
}

export default async function PostPage({ params }: Props) {
  const { slug } = await params;

  let post;
  try {
    post = getPostBySlug(slug);
  } catch {
    notFound();
  }

  return (
    <article>
      {/* 뒤로 가기 */}
      <Link
        href="/"
        className="inline-flex items-center gap-1.5 text-xs text-white/35 hover:text-white/60 transition-colors mb-8"
      >
        ← 목록으로
      </Link>

      {/* 포스트 헤더 */}
      <header className="mb-10 pb-8 border-b border-white/[0.07]">
        {/* 태그 */}
        {post.tags.length > 0 && (
          <div className="flex flex-wrap gap-1.5 mb-4">
            {post.tags.map(tag => (
              <span
                key={tag}
                className="text-xs px-2 py-0.5 rounded bg-blue-500/10 border border-blue-500/20 text-blue-300/70"
              >
                {tag}
              </span>
            ))}
          </div>
        )}

        <h1 className="text-2xl sm:text-3xl font-bold text-white leading-snug mb-3">
          {post.title}
        </h1>

        {post.description && (
          <p className="text-sm text-white/45 leading-relaxed mb-4">
            {post.description}
          </p>
        )}

        <time className="text-xs text-white/25">{post.date}</time>
      </header>

      {/* 마크다운 본문 */}
      <div className="prose max-w-none">
        <ReactMarkdown
          remarkPlugins={[remarkGfm]}
          rehypePlugins={[rehypeHighlight]}
        >
          {post.content}
        </ReactMarkdown>
      </div>

      {/* 하단 네비게이션 */}
      <div className="mt-16 pt-8 border-t border-white/[0.07]">
        <Link
          href="/"
          className="inline-flex items-center gap-1.5 text-sm text-white/40 hover:text-white/70 transition-colors"
        >
          ← 모든 포스트 보기
        </Link>
      </div>
    </article>
  );
}
