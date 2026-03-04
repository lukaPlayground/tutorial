#!/usr/bin/env python3
"""
Ollama PDF 요약 자동화 스크립트
사용법: python summarize.py ./pdfs/ [--model llama3.2] [--output ./output] [--lang ko]
"""

import argparse
import os
import sys
from pathlib import Path
from datetime import datetime


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
    os.makedirs(output_dir, exist_ok=True)

    # 처리 결과 추적
    success, failed = 0, 0

    for pdf_path in pdfs:
        print(f"\n처리 중: {pdf_path.name}")
        try:
            # TODO: 텍스트 추출 + 요약 (Task 3, 4에서 구현)
            print(f"  [STUB] {pdf_path.name} — 아직 구현 전")
            success += 1
        except Exception as e:
            print(f"  [ERROR] {pdf_path.name}: {e}")
            failed += 1

    # 최종 결과
    print(f"\n완료 — 성공: {success}개, 실패: {failed}개")


if __name__ == "__main__":
    main()
