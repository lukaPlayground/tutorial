"""
Telegram 봇 튜토리얼
핵심 기능 데모:
  - CommandHandler (/start, /help, /weather, /memo, /list, /clear)
  - MessageHandler (에코 — 일반 텍스트 메시지 반환)
  - InlineKeyboardMarkup + CallbackQueryHandler (버튼 인터랙션)
  - httpx로 외부 API 호출 (Open-Meteo 날씨, API 키 불필요)
  - 사용자별 메모 인메모리 저장 (user_id 기반)
  - python-telegram-bot v21 async/await 패턴
"""

import logging
import os
from datetime import datetime

import httpx
from dotenv import load_dotenv
from telegram import InlineKeyboardButton, InlineKeyboardMarkup, Update
from telegram.ext import (
    Application,
    CallbackQueryHandler,
    CommandHandler,
    ContextTypes,
    MessageHandler,
    filters,
)

load_dotenv()
BOT_TOKEN = os.getenv("BOT_TOKEN")

logging.basicConfig(
    format="%(asctime)s [%(levelname)s] %(message)s",
    level=logging.INFO,
)
logger = logging.getLogger(__name__)

# 사용자별 메모 저장소 {user_id: [memo, ...]}
user_memos: dict[int, list[str]] = {}


# ── /start ───────────────────────────────────────────
async def start(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    user = update.effective_user
    keyboard = [
        [
            InlineKeyboardButton("📋 명령어 보기", callback_data="show_help"),
            InlineKeyboardButton("🌤 날씨 예시", callback_data="weather_example"),
        ],
        [
            InlineKeyboardButton("📝 메모 예시", callback_data="memo_example"),
        ],
    ]
    reply_markup = InlineKeyboardMarkup(keyboard)

    await update.message.reply_text(
        f"안녕하세요, {user.first_name}님! 👋\n\n"
        "저는 튜토리얼 봇입니다.\n"
        "아래 버튼을 누르거나 명령어를 입력해보세요.",
        reply_markup=reply_markup,
    )


# ── /help ────────────────────────────────────────────
async def help_command(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    text = (
        "📌 *사용 가능한 명령어*\n\n"
        "/start — 시작 메시지\n"
        "/help — 이 도움말\n"
        "/weather `[도시명]` — 현재 날씨\n"
        "  예) /weather Seoul\n"
        "/memo `[내용]` — 메모 저장\n"
        "  예) /memo 우유 사기\n"
        "/list — 저장된 메모 목록\n"
        "/clear — 메모 전체 삭제\n\n"
        "💬 일반 텍스트를 입력하면 에코로 돌려줍니다."
    )
    await update.message.reply_text(text, parse_mode="Markdown")


# ── /weather ─────────────────────────────────────────
async def weather(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    if not context.args:
        await update.message.reply_text("도시명을 입력해주세요.\n예) /weather Seoul")
        return

    city = " ".join(context.args)

    async with httpx.AsyncClient(timeout=10) as client:
        # 1단계: 도시명 → 위도/경도 (Open-Meteo Geocoding)
        geo_resp = await client.get(
            "https://geocoding-api.open-meteo.com/v1/search",
            params={"name": city, "count": 1, "language": "ko"},
        )
        geo_data = geo_resp.json()

        if not geo_data.get("results"):
            await update.message.reply_text(f"'{city}' 도시를 찾을 수 없습니다.")
            return

        result = geo_data["results"][0]
        lat, lon = result["latitude"], result["longitude"]
        city_name = result.get("name", city)
        country = result.get("country", "")

        # 2단계: 날씨 조회 (Open-Meteo)
        weather_resp = await client.get(
            "https://api.open-meteo.com/v1/forecast",
            params={
                "latitude": lat,
                "longitude": lon,
                "current": "temperature_2m,relative_humidity_2m,weathercode,windspeed_10m",
                "timezone": "auto",
            },
        )
        w = weather_resp.json()["current"]

    temp = w["temperature_2m"]
    humidity = w["relative_humidity_2m"]
    wind = w["windspeed_10m"]
    code = w["weathercode"]

    # WMO 날씨 코드 → 이모지
    def weather_emoji(c: int) -> str:
        if c == 0:            return "☀️ 맑음"
        if c in (1, 2, 3):   return "⛅ 구름 조금"
        if c in range(45, 50): return "🌫 안개"
        if c in range(51, 68): return "🌧 비"
        if c in range(71, 78): return "❄️ 눈"
        if c in range(80, 83): return "🌦 소나기"
        if c in range(95, 100): return "⛈ 뇌우"
        return "🌡 알 수 없음"

    text = (
        f"🌍 *{city_name}, {country}* 현재 날씨\n\n"
        f"{weather_emoji(code)}\n"
        f"🌡 기온: *{temp}°C*\n"
        f"💧 습도: {humidity}%\n"
        f"💨 풍속: {wind} km/h\n\n"
        f"_데이터: Open-Meteo_"
    )
    await update.message.reply_text(text, parse_mode="Markdown")


# ── /memo ────────────────────────────────────────────
async def memo(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    user_id = update.effective_user.id

    if not context.args:
        await update.message.reply_text("메모 내용을 입력해주세요.\n예) /memo 우유 사기")
        return

    content = " ".join(context.args)
    user_memos.setdefault(user_id, []).append(content)
    count = len(user_memos[user_id])

    await update.message.reply_text(f"✅ 메모 저장 완료! (총 {count}개)\n📝 {content}")


# ── /list ────────────────────────────────────────────
async def list_memos(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    user_id = update.effective_user.id
    memos = user_memos.get(user_id, [])

    if not memos:
        await update.message.reply_text("저장된 메모가 없습니다.\n/memo [내용] 으로 추가해보세요.")
        return

    lines = [f"{i+1}. {m}" for i, m in enumerate(memos)]
    text = "📋 *내 메모 목록*\n\n" + "\n".join(lines)
    await update.message.reply_text(text, parse_mode="Markdown")


# ── /clear ───────────────────────────────────────────
async def clear_memos(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    user_id = update.effective_user.id
    count = len(user_memos.get(user_id, []))
    user_memos[user_id] = []
    await update.message.reply_text(f"🗑 메모 {count}개를 모두 삭제했습니다.")


# ── 에코 (일반 텍스트) ───────────────────────────────
async def echo(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    text = update.message.text
    await update.message.reply_text(f"🔁 에코: {text}")


# ── 인라인 버튼 콜백 ─────────────────────────────────
async def button_callback(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    query = update.callback_query
    await query.answer()  # 로딩 스피너 제거

    if query.data == "show_help":
        text = (
            "📌 *명령어 목록*\n\n"
            "/weather Seoul — 서울 날씨\n"
            "/memo 할 일 — 메모 저장\n"
            "/list — 메모 목록\n"
            "/clear — 메모 삭제"
        )
        await query.edit_message_text(text, parse_mode="Markdown")

    elif query.data == "weather_example":
        await query.edit_message_text(
            "날씨를 조회하려면:\n\n"
            "`/weather Seoul`\n"
            "`/weather Tokyo`\n"
            "`/weather New York`\n\n"
            "Open-Meteo API 사용 (API 키 불필요)",
            parse_mode="Markdown",
        )

    elif query.data == "memo_example":
        await query.edit_message_text(
            "메모를 사용하려면:\n\n"
            "`/memo 우유 사기`\n"
            "`/memo 회의 자료 준비`\n"
            "`/list` — 목록 보기\n"
            "`/clear` — 전체 삭제",
            parse_mode="Markdown",
        )


# ── 에러 핸들러 ──────────────────────────────────────
async def error_handler(update: object, context: ContextTypes.DEFAULT_TYPE) -> None:
    logger.error("에러 발생: %s", context.error)


# ── 메인 ─────────────────────────────────────────────
def main() -> None:
    if not BOT_TOKEN:
        raise ValueError("BOT_TOKEN이 설정되지 않았습니다. .env 파일을 확인하세요.")

    app = Application.builder().token(BOT_TOKEN).build()

    # 핸들러 등록
    app.add_handler(CommandHandler("start", start))
    app.add_handler(CommandHandler("help", help_command))
    app.add_handler(CommandHandler("weather", weather))
    app.add_handler(CommandHandler("memo", memo))
    app.add_handler(CommandHandler("list", list_memos))
    app.add_handler(CommandHandler("clear", clear_memos))
    app.add_handler(CallbackQueryHandler(button_callback))
    app.add_handler(MessageHandler(filters.TEXT & ~filters.COMMAND, echo))
    app.add_error_handler(error_handler)

    logger.info("봇 시작... (Ctrl+C로 종료)")
    app.run_polling(allowed_updates=Update.ALL_TYPES)


if __name__ == "__main__":
    main()
