#!/usr/bin/env python3
"""
Markdown 파일을 파싱해 JSON 페이로드로 변환.
사용법: python process_md.py <파일경로>
출력: JSON (stdout)
"""

import sys
import json
import re
from pathlib import Path

import frontmatter
import markdown as md_lib


MARKDOWN_EXTENSIONS = [
    'markdown.extensions.fenced_code',
    'markdown.extensions.tables',
    'markdown.extensions.nl2br',
    'markdown.extensions.sane_lists',
]


def parse_file(file_path: str) -> dict:
    """front matter 파싱 + Markdown → HTML 변환."""
    path = Path(file_path)
    if not path.exists():
        raise FileNotFoundError(f"파일 없음: {file_path}")

    post = frontmatter.load(str(path))
    meta = post.metadata
    body = post.content

    # Markdown → HTML
    html = md_lib.markdown(body, extensions=MARKDOWN_EXTENSIONS)

    # tags: 리스트 → 쉼표 구분 문자열
    tags_raw = meta.get('tags', [])
    if isinstance(tags_raw, list):
        tags = ','.join(str(t) for t in tags_raw)
    else:
        tags = str(tags_raw)

    return {
        'title': str(meta.get('title', path.stem)),
        'content': html,
        'tags': tags,
        'category': str(meta.get('category', '')),
        'visibility': '0',   # 0=비공개(임시저장), 3=발행
        'filename': path.name,
    }


def main():
    if len(sys.argv) < 2:
        print('사용법: python process_md.py <파일경로>', file=sys.stderr)
        sys.exit(1)

    try:
        payload = parse_file(sys.argv[1])
        print(json.dumps(payload, ensure_ascii=False))
    except FileNotFoundError as e:
        print(f'오류: {e}', file=sys.stderr)
        sys.exit(1)
    except Exception as e:
        print(f'처리 실패: {e}', file=sys.stderr)
        sys.exit(1)


if __name__ == '__main__':
    main()
