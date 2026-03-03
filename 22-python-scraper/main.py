"""
웹 스크래퍼 — books.toscrape.com
BeautifulSoup 핵심 기능 데모:
  - requests로 HTML 가져오기
  - BeautifulSoup으로 파싱 (lxml 파서)
  - CSS 선택자 (select, select_one)
  - 페이지네이션 (다음 페이지 자동 탐색)
  - 데이터 정제 (평점 텍스트 → 숫자, 가격 파싱)
  - CSV 저장
"""

import csv
import os
import time

import requests
from bs4 import BeautifulSoup

BASE_URL = "https://books.toscrape.com"

# 평점 텍스트 → 숫자 변환
RATING_MAP = {
    "One": 1, "Two": 2, "Three": 3, "Four": 4, "Five": 5
}


def fetch_page(url: str) -> BeautifulSoup:
    """URL을 가져와 BeautifulSoup 객체 반환"""
    headers = {"User-Agent": "Mozilla/5.0 (BookScraper Tutorial)"}
    response = requests.get(url, headers=headers, timeout=10)
    response.raise_for_status()
    return BeautifulSoup(response.text, "lxml")


def parse_books(soup: BeautifulSoup) -> list[dict]:
    """현재 페이지의 책 목록 파싱"""
    books = []
    articles = soup.select("article.product_pod")

    for article in articles:
        # 제목
        title_tag = article.select_one("h3 > a")
        title = title_tag["title"] if title_tag else "N/A"

        # 가격 (£ 제거 후 float)
        price_tag = article.select_one("p.price_color")
        price_text = price_tag.get_text(strip=True) if price_tag else "0"
        price = float(price_text.replace("£", "").replace("Â", "").strip())

        # 평점 (CSS 클래스에서 추출: "star-rating Three")
        rating_tag = article.select_one("p.star-rating")
        rating_class = rating_tag["class"][1] if rating_tag else "Zero"
        rating = RATING_MAP.get(rating_class, 0)

        # 재고 여부
        availability_tag = article.select_one("p.availability")
        in_stock = "In stock" in (availability_tag.get_text() if availability_tag else "")

        # 상세 페이지 링크
        link = BASE_URL + "/catalogue/" + title_tag["href"].replace("../", "") if title_tag else ""

        books.append({
            "title": title,
            "price_gbp": price,
            "rating": rating,
            "in_stock": in_stock,
            "url": link,
        })

    return books


def get_next_page_url(soup: BeautifulSoup, current_url: str) -> str | None:
    """다음 페이지 URL 반환 (없으면 None)"""
    next_btn = soup.select_one("li.next > a")
    if not next_btn:
        return None

    # 현재 URL의 디렉터리 기준으로 상대경로 해석
    base_dir = current_url.rsplit("/", 1)[0]
    return base_dir + "/" + next_btn["href"]


def scrape_books(max_pages: int = 5) -> list[dict]:
    """최대 max_pages 페이지까지 스크래핑"""
    all_books = []
    url = BASE_URL + "/catalogue/page-1.html"
    page = 1

    while url and page <= max_pages:
        print(f"  페이지 {page} 스크래핑 중... ({url})")
        soup = fetch_page(url)
        books = parse_books(soup)
        all_books.extend(books)
        print(f"    → {len(books)}권 수집 (누적: {len(all_books)}권)")

        url = get_next_page_url(soup, url)
        page += 1
        time.sleep(0.5)  # 서버 부하 방지

    return all_books


def save_csv(books: list[dict], output_path: str):
    """책 데이터를 CSV로 저장"""
    if not books:
        print("저장할 데이터가 없습니다.")
        return

    fieldnames = ["title", "price_gbp", "rating", "in_stock", "url"]
    with open(output_path, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(books)


def print_summary(books: list[dict]):
    """수집 결과 요약 출력"""
    if not books:
        return

    total = len(books)
    avg_price = sum(b["price_gbp"] for b in books) / total
    avg_rating = sum(b["rating"] for b in books) / total
    in_stock_count = sum(1 for b in books if b["in_stock"])

    # 평점별 분포
    rating_dist = {1: 0, 2: 0, 3: 0, 4: 0, 5: 0}
    for b in books:
        if b["rating"] in rating_dist:
            rating_dist[b["rating"]] += 1

    # 상위 5권 (평점 높은 순, 동률이면 가격 낮은 순)
    top5 = sorted(books, key=lambda x: (-x["rating"], x["price_gbp"]))[:5]

    print("\n" + "=" * 50)
    print("수집 결과 요약")
    print("=" * 50)
    print(f"총 권수       : {total}권")
    print(f"재고 있음     : {in_stock_count}권 ({in_stock_count/total*100:.1f}%)")
    print(f"평균 가격     : £{avg_price:.2f}")
    print(f"평균 평점     : {avg_rating:.2f} / 5.0")
    print()
    print("평점 분포:")
    for star in range(5, 0, -1):
        bar = "★" * rating_dist[star]
        print(f"  {star}점: {bar} ({rating_dist[star]}권)")
    print()
    print("평점 상위 5권:")
    for i, b in enumerate(top5, 1):
        stars = "★" * b["rating"] + "☆" * (5 - b["rating"])
        print(f"  {i}. [{stars}] £{b['price_gbp']:.2f}  {b['title'][:50]}")
    print("=" * 50)


if __name__ == "__main__":
    MAX_PAGES = 5       # 스크래핑할 최대 페이지 수 (전체: 50페이지)
    OUTPUT_FILE = os.path.join("output", "books.csv")

    os.makedirs("output", exist_ok=True)

    print(f"books.toscrape.com 스크래핑 시작 (최대 {MAX_PAGES}페이지)")
    books = scrape_books(max_pages=MAX_PAGES)

    save_csv(books, OUTPUT_FILE)
    print(f"\n✓ CSV 저장 완료: {OUTPUT_FILE} ({len(books)}권)")

    print_summary(books)
