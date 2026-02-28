import Link from 'next/link';
import { getAllPosts } from '@/lib/posts';

export default function Home() {
  const posts = getAllPosts();

  // 모든 태그 수집 (중복 제거)
  const allTags = Array.from(new Set(posts.flatMap(p => p.tags)));

  return (
    <div>
      {/* Hero */}
      <div className="mb-12">
        <p className="text-xs font-semibold uppercase tracking-widest text-white/30 mb-3">
          Luka&apos;s Playground
        </p>
        <h1 className="text-3xl font-bold tracking-tight text-white mb-3">
          Dev Notes
        </h1>
        <p className="text-sm text-white/45 leading-relaxed">
          Next.js · gray-matter · react-markdown으로 만든 마크다운 블로그.
          <br />
          <code className="bg-white/[0.06] px-1.5 py-0.5 rounded text-xs">posts/</code> 디렉토리의{' '}
          <code className="bg-white/[0.06] px-1.5 py-0.5 rounded text-xs">.md</code> 파일이 자동으로 포스트가 된다.
        </p>
      </div>

      {/* 태그 목록 */}
      {allTags.length > 0 && (
        <div className="flex flex-wrap gap-2 mb-10">
          {allTags.map(tag => (
            <span
              key={tag}
              className="text-xs px-2.5 py-1 rounded-full bg-white/[0.05] border border-white/[0.08] text-white/50"
            >
              {tag}
            </span>
          ))}
        </div>
      )}

      {/* 포스트 목록 */}
      <div className="space-y-3">
        {posts.map(post => (
          <Link
            key={post.slug}
            href={`/posts/${post.slug}`}
            className="block group"
          >
            <article className="rounded-xl border border-white/[0.07] bg-[#1a1d27] px-5 py-4 transition-all duration-150 hover:border-white/[0.13] hover:-translate-y-0.5">
              <div className="flex items-start justify-between gap-4">
                <div className="flex-1 min-w-0">
                  <h2 className="font-semibold text-base text-white group-hover:text-blue-300 transition-colors leading-snug mb-1.5 line-clamp-2">
                    {post.title}
                  </h2>
                  <p className="text-sm text-white/40 line-clamp-2 leading-relaxed">
                    {post.description}
                  </p>
                </div>
                <time className="text-xs text-white/25 whitespace-nowrap pt-0.5 flex-shrink-0">
                  {post.date}
                </time>
              </div>
              {post.tags.length > 0 && (
                <div className="flex flex-wrap gap-1.5 mt-3">
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
            </article>
          </Link>
        ))}
      </div>

      {posts.length === 0 && (
        <p className="text-sm text-white/30">
          posts/ 디렉토리에 .md 파일을 추가하면 포스트가 생성됩니다.
        </p>
      )}
    </div>
  );
}
