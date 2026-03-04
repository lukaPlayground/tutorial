"""테스트용 샘플 PDF 생성 스크립트"""
import fitz

doc = fitz.open()
page = doc.new_page()
page.insert_text(
    (72, 72),
    "샘플 PDF 문서\n\n"
    "이 문서는 Ollama PDF 요약기 테스트용입니다.\n\n"
    "Ollama는 로컬에서 LLM을 실행하는 무료 오픈소스 도구입니다.\n"
    "PyMuPDF(fitz)로 텍스트를 추출하고, ollama Python 라이브러리로 요약합니다.\n\n"
    "주요 기능:\n"
    "- PDF 배치 처리\n"
    "- 20,000자 기준 청킹\n"
    "- 페이지 경계 분할\n"
    "- Markdown 출력\n\n"
    "Ollama 지원 모델:\n"
    "- llama3.2 (기본)\n"
    "- mistral\n"
    "- gemma2\n"
    "- phi3\n\n"
    "사용 방법:\n"
    "1. ollama serve 실행\n"
    "2. ollama pull llama3.2\n"
    "3. python summarize.py ./sample/ --model llama3.2\n",
    fontsize=12
)
doc.save("sample/sample.pdf")
doc.close()
print("sample/sample.pdf 생성 완료")
