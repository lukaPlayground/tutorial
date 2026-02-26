#!/usr/bin/env python3
"""
YouTube 자막 추출기
사용법: python main.py <YouTube URL 또는 Video ID> [옵션]
"""

import re
import sys
import argparse
from pathlib import Path
from youtube_transcript_api import YouTubeTranscriptApi, NoTranscriptFound, TranscriptsDisabled


# ── 유틸 함수 ──────────────────────────────────────────────

def extract_video_id(url_or_id: str) -> str:
    """URL 또는 ID에서 YouTube Video ID를 추출한다."""
    # 이미 ID 형식이면 그대로 반환 (11자리 영숫자 + _ -)
    if re.fullmatch(r'[a-zA-Z0-9_-]{11}', url_or_id):
        return url_or_id

    # URL에서 추출
    patterns = [
        r'(?:v=)([a-zA-Z0-9_-]{11})',       # ?v=ID
        r'(?:youtu\.be/)([a-zA-Z0-9_-]{11})', # youtu.be/ID
        r'(?:embed/)([a-zA-Z0-9_-]{11})',     # /embed/ID
        r'(?:shorts/)([a-zA-Z0-9_-]{11})',    # /shorts/ID
    ]
    for pattern in patterns:
        match = re.search(pattern, url_or_id)
        if match:
            return match.group(1)

    raise ValueError(f"유효한 YouTube URL 또는 Video ID가 아닙니다: {url_or_id}")


def format_timestamp(seconds: float) -> str:
    """초 단위를 HH:MM:SS 형식으로 변환한다."""
    total = int(seconds)
    h = total // 3600
    m = (total % 3600) // 60
    s = total % 60
    if h:
        return f"{h:02d}:{m:02d}:{s:02d}"
    return f"{m:02d}:{s:02d}"


def list_available_languages(api: YouTubeTranscriptApi, video_id: str) -> None:
    """사용 가능한 자막 언어 목록을 출력한다."""
    transcript_list = api.list(video_id)

    manually, generated = [], []
    for t in transcript_list:
        if t.is_generated:
            generated.append(t)
        else:
            manually.append(t)

    if manually:
        print("\n[수동 자막]")
        for t in manually:
            print(f"  {t.language_code:8s} {t.language}")

    if generated:
        print("\n[자동 생성 자막]")
        for t in generated:
            print(f"  {t.language_code:8s} {t.language}")


def fetch_transcript(api: YouTubeTranscriptApi, video_id: str, languages: list[str]) -> list[dict]:
    """자막을 가져온다. languages 순서대로 우선 시도한다."""
    try:
        fetched = api.fetch(video_id, languages=languages)
        return [{'text': s.text, 'start': s.start, 'duration': s.duration} for s in fetched]
    except NoTranscriptFound:
        # 지정 언어 없으면 사용 가능한 첫 번째로 시도
        transcript_list = api.list(video_id)
        fallback = next(iter(transcript_list))
        print(f"[info] 요청 언어 없음. '{fallback.language_code}' 자막으로 대체합니다.", file=sys.stderr)
        fetched = fallback.fetch()
        return [{'text': s.text, 'start': s.start, 'duration': s.duration} for s in fetched]


def build_output(entries: list[dict], with_timestamp: bool) -> str:
    """자막 엔트리를 문자열로 변환한다."""
    lines = []
    for entry in entries:
        text = entry['text'].strip()
        if not text:
            continue
        if with_timestamp:
            ts = format_timestamp(entry['start'])
            lines.append(f"[{ts}] {text}")
        else:
            lines.append(text)

    if with_timestamp:
        return '\n'.join(lines)
    # 타임스탬프 없으면 문단 단위로 합친다 (5줄마다 빈 줄 삽입)
    return '\n'.join(
        line + ('\n' if (i + 1) % 5 == 0 else '')
        for i, line in enumerate(lines)
    ).strip()


# ── CLI ────────────────────────────────────────────────────

def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description='YouTube 자막 추출기',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
예시:
  python main.py https://www.youtube.com/watch?v=dQw4w9WgXcQ
  python main.py dQw4w9WgXcQ -l ko en
  python main.py dQw4w9WgXcQ -o transcript.txt
  python main.py dQw4w9WgXcQ --list
  python main.py dQw4w9WgXcQ --timestamp
        """
    )
    parser.add_argument('url', help='YouTube URL 또는 Video ID')
    parser.add_argument(
        '-l', '--lang',
        nargs='+',
        default=['ko', 'en'],
        metavar='LANG',
        help='자막 언어 우선순위 (기본값: ko en)'
    )
    parser.add_argument(
        '-o', '--output',
        metavar='FILE',
        help='출력 파일 경로 (미지정 시 터미널 출력)'
    )
    parser.add_argument(
        '--timestamp',
        action='store_true',
        help='타임스탬프 포함 출력'
    )
    parser.add_argument(
        '--list',
        action='store_true',
        help='사용 가능한 자막 언어 목록만 출력'
    )
    return parser


def main():
    parser = build_parser()
    args = parser.parse_args()

    try:
        video_id = extract_video_id(args.url)
        print(f"Video ID: {video_id}", file=sys.stderr)
    except ValueError as e:
        print(f"[오류] {e}", file=sys.stderr)
        sys.exit(1)

    api = YouTubeTranscriptApi()

    # 언어 목록만 출력
    if args.list:
        try:
            list_available_languages(api, video_id)
        except TranscriptsDisabled:
            print("[오류] 이 영상은 자막이 비활성화되어 있습니다.", file=sys.stderr)
            sys.exit(1)
        return

    # 자막 fetch
    try:
        print(f"언어 시도: {args.lang}", file=sys.stderr)
        entries = fetch_transcript(api, video_id, args.lang)
    except TranscriptsDisabled:
        print("[오류] 이 영상은 자막이 비활성화되어 있습니다.", file=sys.stderr)
        sys.exit(1)
    except Exception as e:
        print(f"[오류] 자막 가져오기 실패: {e}", file=sys.stderr)
        sys.exit(1)

    # 출력
    output = build_output(entries, with_timestamp=args.timestamp)
    total_chars = len(output)

    if args.output:
        path = Path(args.output)
        path.write_text(output, encoding='utf-8')
        print(f"[완료] '{path}' 저장됨 ({total_chars:,}자)", file=sys.stderr)
    else:
        print(output)
        print(f"\n--- {total_chars:,}자, {len(entries)}개 구간 ---", file=sys.stderr)


if __name__ == '__main__':
    main()
