"""
FastAPI 도서 관리 REST API
핵심 기능 데모:
  - Pydantic 모델 (입력 검증, 응답 직렬화)
  - CRUD 엔드포인트 (GET / POST / PUT / DELETE)
  - 경로 파라미터, 쿼리 파라미터
  - HTTPException (404, 422 등)
  - 자동 문서화 (Swagger UI: /docs, ReDoc: /redoc)
  - 인메모리 데이터 저장 (DB 없이 실습)
"""

from fastapi import FastAPI, HTTPException, Query
from pydantic import BaseModel, Field
from typing import Optional
from datetime import datetime

app = FastAPI(
    title="도서 관리 API",
    description="FastAPI 튜토리얼 — CRUD + 검색 + 자동 문서화",
    version="1.0.0",
)


# ── Pydantic 모델 ────────────────────────────────────
class BookBase(BaseModel):
    title: str = Field(..., min_length=1, max_length=200, description="책 제목")
    author: str = Field(..., min_length=1, max_length=100, description="저자")
    price: float = Field(..., gt=0, description="가격 (0 초과)")
    rating: int = Field(..., ge=1, le=5, description="평점 (1~5)")
    genre: Optional[str] = Field(None, max_length=50, description="장르")


class BookCreate(BookBase):
    """POST /books 요청 바디"""
    pass


class BookUpdate(BaseModel):
    """PUT /books/{id} 요청 바디 — 모든 필드 선택"""
    title: Optional[str] = Field(None, min_length=1, max_length=200)
    author: Optional[str] = Field(None, min_length=1, max_length=100)
    price: Optional[float] = Field(None, gt=0)
    rating: Optional[int] = Field(None, ge=1, le=5)
    genre: Optional[str] = Field(None, max_length=50)


class BookResponse(BookBase):
    """응답 모델 — id, created_at 포함"""
    id: int
    created_at: str

    model_config = {"from_attributes": True}


# ── 인메모리 저장소 ──────────────────────────────────
_books: dict[int, dict] = {}
_next_id: int = 1

# 샘플 데이터 초기화
_INITIAL_BOOKS = [
    {"title": "파친코",             "author": "이민진",       "price": 17800, "rating": 5, "genre": "소설"},
    {"title": "채식주의자",          "author": "한강",         "price": 13000, "rating": 5, "genre": "소설"},
    {"title": "클린 코드",           "author": "Robert Martin","price": 33000, "rating": 5, "genre": "개발"},
    {"title": "파이썬 완벽 가이드",   "author": "Bill Lubanovic","price": 42000, "rating": 4, "genre": "개발"},
    {"title": "총균쇠",             "author": "재레드 다이아몬드","price": 22000, "rating": 4, "genre": "역사"},
    {"title": "사피엔스",            "author": "유발 하라리",   "price": 18000, "rating": 4, "genre": "역사"},
    {"title": "아몬드",             "author": "손원평",        "price": 13500, "rating": 4, "genre": "소설"},
    {"title": "82년생 김지영",       "author": "조남주",        "price": 13000, "rating": 3, "genre": "소설"},
]

for book_data in _INITIAL_BOOKS:
    _books[_next_id] = {**book_data, "id": _next_id, "created_at": "2024-01-01T00:00:00"}
    _next_id += 1


# ── 헬퍼 ─────────────────────────────────────────────
def _get_book_or_404(book_id: int) -> dict:
    book = _books.get(book_id)
    if not book:
        raise HTTPException(status_code=404, detail=f"ID {book_id} 책을 찾을 수 없습니다.")
    return book


# ── 엔드포인트 ───────────────────────────────────────

@app.get("/", tags=["root"])
def root():
    """서버 상태 확인"""
    return {"message": "도서 관리 API", "docs": "/docs", "total_books": len(_books)}


@app.get("/books", response_model=list[BookResponse], tags=["books"])
def list_books(
    limit: int = Query(10, ge=1, le=100, description="반환할 최대 개수"),
    offset: int = Query(0, ge=0, description="건너뛸 개수"),
    genre: Optional[str] = Query(None, description="장르 필터"),
    min_rating: int = Query(1, ge=1, le=5, description="최소 평점"),
):
    """
    도서 목록 조회

    - **limit**: 한 번에 반환할 최대 개수 (기본 10)
    - **offset**: 페이지네이션 오프셋
    - **genre**: 특정 장르만 필터링
    - **min_rating**: 이 평점 이상인 책만 반환
    """
    books = list(_books.values())

    if genre:
        books = [b for b in books if b.get("genre") == genre]
    books = [b for b in books if b["rating"] >= min_rating]

    return books[offset: offset + limit]


@app.get("/books/search", response_model=list[BookResponse], tags=["books"])
def search_books(
    q: str = Query(..., min_length=1, description="제목 또는 저자 검색어"),
):
    """제목 또는 저자로 검색 (대소문자 무시)"""
    q_lower = q.lower()
    results = [
        b for b in _books.values()
        if q_lower in b["title"].lower() or q_lower in b["author"].lower()
    ]
    if not results:
        raise HTTPException(status_code=404, detail=f"'{q}' 검색 결과가 없습니다.")
    return results


@app.get("/books/{book_id}", response_model=BookResponse, tags=["books"])
def get_book(book_id: int):
    """ID로 단일 도서 조회"""
    return _get_book_or_404(book_id)


@app.post("/books", response_model=BookResponse, status_code=201, tags=["books"])
def create_book(book: BookCreate):
    """새 도서 추가"""
    global _next_id
    new_book = {
        **book.model_dump(),
        "id": _next_id,
        "created_at": datetime.now().isoformat(),
    }
    _books[_next_id] = new_book
    _next_id += 1
    return new_book


@app.put("/books/{book_id}", response_model=BookResponse, tags=["books"])
def update_book(book_id: int, updates: BookUpdate):
    """도서 정보 부분 수정 (입력한 필드만 변경)"""
    book = _get_book_or_404(book_id)
    for field, value in updates.model_dump(exclude_none=True).items():
        book[field] = value
    return book


@app.delete("/books/{book_id}", status_code=204, tags=["books"])
def delete_book(book_id: int):
    """도서 삭제"""
    _get_book_or_404(book_id)
    del _books[book_id]


@app.get("/books/genre/{genre}", response_model=list[BookResponse], tags=["books"])
def books_by_genre(genre: str):
    """장르별 도서 목록"""
    results = [b for b in _books.values() if b.get("genre", "").lower() == genre.lower()]
    if not results:
        raise HTTPException(status_code=404, detail=f"'{genre}' 장르 책이 없습니다.")
    return results


if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:app", host="0.0.0.0", port=8000, reload=True)
