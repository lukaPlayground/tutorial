'use strict';

// ── DOM refs ────────────────────────────────────────────
const promptEl      = document.getElementById('prompt');
const modelEl       = document.getElementById('model');
const widthEl       = document.getElementById('imgWidth');
const heightEl      = document.getElementById('imgHeight');
const seedEl        = document.getElementById('seed');
const generateBtn   = document.getElementById('generateBtn');
const randomSeedBtn = document.getElementById('randomSeedBtn');
const mainImg       = document.getElementById('mainImg');
const spinner       = document.getElementById('spinner');
const placeholder   = document.getElementById('placeholder');
const errorMsg      = document.getElementById('errorMsg');
const actions       = document.getElementById('actions');
const downloadBtn   = document.getElementById('downloadBtn');
const copyPromptBtn = document.getElementById('copyPromptBtn');
const historySection= document.getElementById('historySection');
const historyGrid   = document.getElementById('historyGrid');

// ── State ────────────────────────────────────────────────
const historyItems = [];   // { url, prompt, model, width, height, seed }
let currentItem    = null;

// ── URL Builder ──────────────────────────────────────────
/**
 * Pollinations 이미지 URL 생성
 * https://image.pollinations.ai/prompt/{encoded}?width=&height=&model=&seed=&nologo=&enhance=
 */
function buildUrl(prompt, { model, width, height, seed }) {
  const resolvedSeed = seed ?? Math.floor(Math.random() * 1_000_000_000);
  const params = new URLSearchParams({
    width,
    height,
    model,
    seed:    resolvedSeed,
    nologo:  'true',
    enhance: 'true',
  });
  const encoded = encodeURIComponent(prompt);
  return `https://image.pollinations.ai/prompt/${encoded}?${params}`;
}

// ── 상태 전환 ────────────────────────────────────────────
// state: 'idle' | 'loading' | 'done' | 'error'
function setState(state) {
  placeholder.hidden   = state !== 'idle';
  spinner.hidden       = state !== 'loading';
  mainImg.hidden       = state !== 'done';
  errorMsg.hidden      = state !== 'error';
  actions.hidden       = state !== 'done';

  generateBtn.disabled    = state === 'loading';
  generateBtn.textContent = state === 'loading' ? '⏳ 생성 중...' : '✨ 생성하기';
}

// ── 이미지 생성 ──────────────────────────────────────────
function generate() {
  const prompt = promptEl.value.trim();

  // 빈 프롬프트 경고
  if (!prompt) {
    promptEl.focus();
    promptEl.classList.remove('shake');
    void promptEl.offsetWidth;          // reflow (animation 재시작)
    promptEl.classList.add('shake');
    return;
  }

  const settings = {
    model:  modelEl.value,
    width:  parseInt(widthEl.value, 10),
    height: parseInt(heightEl.value, 10),
    seed:   seedEl.value !== '' ? parseInt(seedEl.value, 10) : null,
  };

  const url = buildUrl(prompt, settings);
  currentItem = { url, prompt, ...settings };

  setState('loading');
  mainImg.src = '';       // 이전 이미지 초기화
  mainImg.src = url;      // 브라우저가 이미지 요청 → onload/onerror 처리
}

// ── img 이벤트 ───────────────────────────────────────────
mainImg.onload  = () => { setState('done');  addToHistory(currentItem); };
mainImg.onerror = () => { setState('error'); };

// ── 이벤트 바인딩 ────────────────────────────────────────
generateBtn.addEventListener('click', generate);

// Ctrl+Enter / Cmd+Enter 로 생성
promptEl.addEventListener('keydown', e => {
  if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
    e.preventDefault();
    generate();
  }
});

// 랜덤 시드 버튼
randomSeedBtn.addEventListener('click', () => {
  seedEl.value = Math.floor(Math.random() * 1_000_000_000);
});

// ── 히스토리 placeholder (Task 4에서 구현) ───────────────
function addToHistory(item) { /* Task 4 */ }
