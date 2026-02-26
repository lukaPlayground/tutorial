"""
YouTube 자막 추출기 — Streamlit 웹 UI
실행: streamlit run app.py
"""

import re
import streamlit as st
from youtube_transcript_api import YouTubeTranscriptApi, NoTranscriptFound, TranscriptsDisabled


# ── 페이지 설정 ──────────────────────────────────────────────

st.set_page_config(
    page_title="유튜브 자막 추출기",
    page_icon="🎬",
    layout="centered",
)

st.markdown("""
<style>
  .block-container { padding-top: 2rem; }
  textarea { font-size: 14px !important; }
</style>
""", unsafe_allow_html=True)


# ── 유틸 ────────────────────────────────────────────────────

def extract_video_id(url_or_id: str) -> str:
    if re.fullmatch(r'[a-zA-Z0-9_-]{11}', url_or_id):
        return url_or_id
    patterns = [
        r'(?:v=)([a-zA-Z0-9_-]{11})',
        r'(?:youtu\.be/)([a-zA-Z0-9_-]{11})',
        r'(?:embed/)([a-zA-Z0-9_-]{11})',
        r'(?:shorts/)([a-zA-Z0-9_-]{11})',
    ]
    for p in patterns:
        m = re.search(p, url_or_id)
        if m:
            return m.group(1)
    raise ValueError("유효한 YouTube URL 또는 Video ID가 아닙니다.")


def format_timestamp(seconds: float) -> str:
    t = int(seconds)
    h, m, s = t // 3600, (t % 3600) // 60, t % 60
    return f"{h:02d}:{m:02d}:{s:02d}" if h else f"{m:02d}:{s:02d}"


def get_languages(api: YouTubeTranscriptApi, video_id: str) -> list[dict]:
    langs = []
    try:
        for t in api.list(video_id):
            langs.append({
                'code': t.language_code,
                'name': t.language,
                'generated': t.is_generated,
            })
    except Exception:
        pass
    return langs


def fetch(api: YouTubeTranscriptApi, video_id: str, languages: list[str], with_ts: bool) -> str:
    try:
        result = api.fetch(video_id, languages=languages)
        entries = [{'text': s.text, 'start': s.start} for s in result]
    except NoTranscriptFound:
        tl = api.list(video_id)
        fallback = next(iter(tl))
        entries = [{'text': s.text, 'start': s.start} for s in fallback.fetch()]
        st.info(f"요청 언어를 찾지 못했습니다. '{fallback.language_code}' 자막을 사용합니다.")

    lines = []
    for i, e in enumerate(entries):
        text = e['text'].strip()
        if not text:
            continue
        if with_ts:
            lines.append(f"[{format_timestamp(e['start'])}] {text}")
        else:
            lines.append(text)
            if (i + 1) % 5 == 0:
                lines.append('')

    return '\n'.join(lines).strip()


# ── UI ──────────────────────────────────────────────────────

st.title("🎬 유튜브 자막 추출기")
st.caption("API 키 없이 자막을 텍스트로 추출합니다.")

# 입력
url_input = st.text_input(
    "YouTube URL 또는 Video ID",
    placeholder="https://www.youtube.com/watch?v=...",
)

col1, col2 = st.columns([2, 1])
with col1:
    lang_input = st.text_input(
        "언어 우선순위 (공백 구분)",
        value="ko en",
        help="예: ko en ja — 앞에 있을수록 우선 시도합니다.",
    )
with col2:
    with_timestamp = st.checkbox("타임스탬프 포함", value=False)

# 버튼
run = st.button("자막 추출", type="primary", use_container_width=True)

# 실행
if run and url_input:
    try:
        video_id = extract_video_id(url_input.strip())
    except ValueError as e:
        st.error(str(e))
        st.stop()

    languages = [l.strip() for l in lang_input.split() if l.strip()]
    if not languages:
        languages = ['ko', 'en']

    api = YouTubeTranscriptApi()

    # 사용 가능한 언어 보여주기
    with st.expander("사용 가능한 자막 언어"):
        langs = get_languages(api, video_id)
        if langs:
            manual   = [l for l in langs if not l['generated']]
            auto_gen = [l for l in langs if l['generated']]
            if manual:
                st.markdown("**수동 자막**")
                for l in manual:
                    st.markdown(f"- `{l['code']}` {l['name']}")
            if auto_gen:
                st.markdown("**자동 생성**")
                for l in auto_gen:
                    st.markdown(f"- `{l['code']}` {l['name']} *(자동)*")
        else:
            st.write("언어 목록을 불러올 수 없습니다.")

    with st.spinner("자막 불러오는 중..."):
        try:
            text = fetch(api, video_id, languages, with_timestamp)
        except TranscriptsDisabled:
            st.error("이 영상은 자막이 비활성화되어 있습니다.")
            st.stop()
        except Exception as e:
            st.error(f"자막 가져오기 실패: {e}")
            st.stop()

    char_count = len(text)
    line_count = text.count('\n') + 1

    st.success(f"추출 완료 — {char_count:,}자 / {line_count:,}줄")

    # 결과 텍스트
    st.text_area("추출된 자막", value=text, height=400)

    # 다운로드
    st.download_button(
        label="텍스트 파일로 저장",
        data=text.encode('utf-8'),
        file_name=f"transcript_{video_id}.txt",
        mime="text/plain",
        use_container_width=True,
    )

elif run and not url_input:
    st.warning("URL 또는 Video ID를 입력해주세요.")
