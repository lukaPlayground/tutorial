#!/usr/bin/env python3
"""
Ollama PDF 요약 자동화 스크립트
사용법: python summarize.py ./pdfs/ [--model llama3.2] [--output ./output] [--lang ko]
"""

import argparse
import sys
from pathlib import Path
from datetime import datetime

import fitz  # PyMuPDF


def parse_args():
    parser = argparse.ArgumentParser(
        description="Ollama 로컬 LLM으로 PDF 파일을 자동 요약합니다."
    )
    parser.add_argument("folder", help="PDF 파일이 있는 폴더 경로")
    parser.add_argument("--model", default="llama3.2", help="Ollama 모델명 (기본: llama3.2)")
    parser.add_argument("--output", default="./output", help="출력 폴더 경로 (기본: ./output)")
    parser.add_argument("--lang", choices=["ko", "en"], default="ko",
                        help="요약 언어 ko/en (기본: ko)")
    return parser.parse_args()


def scan_pdfs(folder_path: str) -> list[Path]:
    """폴더 내 *.pdf 파일 목록 반환 (1depth, 재귀 없음)"""
    folder = Path(folder_path)
    if not folder.exists():
        print(f"오류: 폴더를 찾을 수 없습니다 — {folder_path}")
        sys.exit(1)
    pdfs = sorted(folder.glob("*.pdf"))
    return pdfs


def extract_text(pdf_path: Path) -> list[str]:
    """
    PDF에서 페이지별 텍스트를 추출해 리스트로 반환.
    스캔 PDF(텍스트 없음)이면 빈 리스트 반환.
    """
    doc = fitz.open(str(pdf_path))
    pages = []
    for page in doc:
        text = page.get_text().strip()
        if text:
            pages.append(text)
    doc.close()
    return pages


def chunk_pages(pages: list[str], max_chars: int = 20000) -> list[str]:
    """
    페이지 경계 기준으로 텍스트를 청크로 분할.
    각 청크는 max_chars 이하 (페이지 중간에서 자르지 않음).
    """
    chunks = []
    current_chunk = []
    current_len = 0

    for page_text in pages:
        page_len = len(page_text)
        if current_len + page_len > max_chars and current_chunk:
            chunks.append("\n\n".join(current_chunk))
            current_chunk = [page_text]
            current_len = page_len
        else:
            current_chunk.append(page_text)
            current_len += page_len

    if current_chunk:
        chunks.append("\n\n".join(current_chunk))

    return chunks


def main():
    args = parse_args()

    # PDF 스캔
    pdfs = scan_pdfs(args.folder)
    if not pdfs:
        print("PDF 파일이 없습니다.")
        sys.exit(0)

    print(f"발견된 PDF: {len(pdfs)}개 | 모델: {args.model} | 언어: {args.lang}")

    # 출력 폴더 준비
    output_dir = Path(args.output)
    output_dir.mkdir(parents=True, exist_ok=True)

    # 처리 결과 추적
    success, failed = 0, 0

    for pdf_path in pdfs:
        print(f"\n처리 중: {pdf_path.name}")
        try:
            pages = extract_text(pdf_path)
            if not pages:
                print(f"  [SKIP] 텍스트 없음 (스캔 PDF)")
                failed += 1
                continue

            full_text = "\n\n".join(pages)
            chunks = chunk_pages(pages)
            page_count = len(pages)
            print(f"  페이지: {page_count} | 텍스트: {len(full_text):,}자 | 청크: {len(chunks)}개")
            # TODO: 요약 (Task 4에서 구현)
            success += 1
        except Exception as e:
            print(f"  [ERROR] {pdf_path.name}: {e}")
            failed += 1

    # 최종 결과
    print(f"\n완료 — 성공: {success}개, 실패: {failed}개")


if __name__ == "__main__":
    main()
