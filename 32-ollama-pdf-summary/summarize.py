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
import ollama


# ── 프롬프트 템플릿 ─────────────────────────────────────
FINAL_PROMPT_KO = """다음은 PDF 문서의 내용입니다. 아래 형식으로 요약해주세요:

## 요약
(3~5문장으로 전체 내용 요약)

## 핵심 포인트
- (핵심 포인트 3~5개, 각 1~2문장)

문서 내용:
{text}"""

FINAL_PROMPT_EN = """Here is the content of a PDF document. Please summarize it in the following format:

## Summary
(3-5 sentences summarizing the entire content)

## Key Points
- (3-5 key points, 1-2 sentences each)

Document content:
{text}"""

CHUNK_PROMPT_KO = """다음은 PDF 문서의 일부입니다. 핵심 내용을 3~5문장으로 요약해주세요.

문서 내용:
{chunk_text}"""

CHUNK_PROMPT_EN = """Here is a portion of a PDF document. Please summarize the key content in 3-5 sentences.

Document content:
{chunk_text}"""


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
    with fitz.open(str(pdf_path)) as doc:
        pages = []
        for page in doc:
            text = page.get_text().strip()
            if text:
                pages.append(text)
    return pages


def chunk_pages(pages: list[str], max_chars: int = 20000) -> list[str]:
    """
    페이지 경계 기준으로 텍스트를 청크로 분할.
    페이지 중간에서 자르지 않음. 단일 페이지가 max_chars를 초과하면
    그 페이지만으로 청크를 구성 (초과 허용).
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


def call_ollama(prompt: str, model: str) -> str:
    """Ollama API 호출. 연결 실패 시 안내 메시지 후 종료."""
    try:
        response = ollama.chat(
            model=model,
            messages=[{"role": "user", "content": prompt}]
        )
        return response["message"]["content"].strip()
    except Exception as e:
        err = str(e)
        if "Connection" in err or "refused" in err.lower():
            print("\nOllama를 먼저 실행하세요: ollama serve")
            sys.exit(1)
        raise


def summarize_text(text: str, model: str, lang: str, chunks: list[str]) -> str:
    """
    단일 청크: 바로 최종 요약 요청.
    다중 청크: 각 청크 개별 요약 → 합산 후 최종 요약.
    """
    final_tmpl = FINAL_PROMPT_KO if lang == "ko" else FINAL_PROMPT_EN
    chunk_tmpl = CHUNK_PROMPT_KO if lang == "ko" else CHUNK_PROMPT_EN

    if len(chunks) == 1:
        return call_ollama(final_tmpl.format(text=text), model)

    # 다중 청크: 각 청크 요약
    chunk_summaries = []
    for i, chunk in enumerate(chunks, 1):
        print(f"    청크 {i}/{len(chunks)} 요약 중...")
        chunk_summaries.append(call_ollama(chunk_tmpl.format(chunk_text=chunk), model))

    # 청크 요약들을 합쳐 최종 요약
    print(f"    최종 요약 생성 중...")
    combined = "\n\n".join(chunk_summaries)
    return call_ollama(final_tmpl.format(text=combined), model)


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
            print(f"  요약 생성 중... (모델: {args.model})")
            summary = summarize_text(full_text, args.model, args.lang, chunks)
            print(f"  요약 완료 ({len(summary)}자)")
            success += 1
        except Exception as e:
            print(f"  [ERROR] {pdf_path.name}: {e}")
            failed += 1

    # 최종 결과
    print(f"\n완료 — 성공: {success}개, 실패: {failed}개")


if __name__ == "__main__":
    main()
