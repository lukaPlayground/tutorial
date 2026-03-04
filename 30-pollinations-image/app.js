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
const errorChecking   = document.getElementById('errorChecking');
const errorDefault    = document.getElementById('errorDefault');
const errorServerDown = document.getElementById('errorServerDown');

// ── State ────────────────────────────────────────────────
const historyItems = [];   // { url, prompt, model, width, height, seed }
let currentItem    = null;
// ── 서버 상태 진단용 테스트 URL ────────────────────────
const TEST_URL = 'https://image.pollinations.ai/prompt/cat?width=32&height=32&nologo=true&seed=1';

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

// ── 서버 상태 진단 ────────────────────────────────────
/**
 * mainImg.onerror 발생 시 호출.
 * 테스트 이미지 로드로 서버 다운 vs 프롬프트 문제 구분.
 */
function checkServerStatus() {
  setState('error');
  errorChecking.hidden   = false;
  errorDefault.hidden    = true;
  errorServerDown.hidden = true;

  const testImg = new Image();
  testImg.onload  = () => showError('prompt');
  testImg.onerror = () => showError('server');
  testImg.src = TEST_URL;
}

function showError(type) {
  errorChecking.hidden   = true;
  errorDefault.hidden    = (type !== 'prompt');
  errorServerDown.hidden = (type !== 'server');
}

// ── img 이벤트 ───────────────────────────────────────────
mainImg.onload  = () => { setState('done');  addToHistory(currentItem); };
mainImg.onerror = checkServerStatus;

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

// ── 히스토리 ─────────────────────────────────────────────
function escapeHtml(str) {
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function addToHistory(item) {
  historyItems.unshift(item);                        // 최신이 앞으로
  if (historyItems.length > 20) historyItems.pop();  // 최대 20개 FIFO
  renderHistory();
}

function renderHistory() {
  if (historyItems.length === 0) return;
  historySection.hidden = false;

  historyGrid.innerHTML = historyItems.map((item, i) => `
    <div class="history-item" data-index="${i}"
         title="${escapeHtml(item.prompt)}\n${item.model} ${item.width}×${item.height}">
      <img src="${item.url}" alt="" loading="lazy">
    </div>
  `).join('');

  historyGrid.querySelectorAll('.history-item').forEach(el => {
    el.addEventListener('click', () => {
      restoreHistory(parseInt(el.dataset.index, 10));
    });
  });
}

function restoreHistory(index) {
  const item = historyItems[index];
  if (!item) return;

  promptEl.value  = item.prompt;
  modelEl.value   = item.model;
  widthEl.value   = item.width;
  heightEl.value  = item.height;
  seedEl.value    = item.seed ?? '';

  currentItem = item;
  mainImg.src = item.url;
  setState('done');
}

// ── 다운로드 ─────────────────────────────────────────────
downloadBtn.addEventListener('click', async () => {
  if (!currentItem) return;
  try {
    const res  = await fetch(currentItem.url);
    const blob = await res.blob();
    const a    = Object.assign(document.createElement('a'), {
      href:     URL.createObjectURL(blob),
      download: `pollinations-${currentItem.seed ?? Date.now()}.png`,
    });
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(a.href);
  } catch {
    alert('다운로드 실패. 이미지를 우클릭 → "다른 이름으로 저장" 해주세요.');
  }
});

// ── 프롬프트 복사 ─────────────────────────────────────────
copyPromptBtn.addEventListener('click', async () => {
  if (!currentItem) return;
  try {
    await navigator.clipboard.writeText(currentItem.prompt);
    copyPromptBtn.textContent = '✅ 복사됨!';
    setTimeout(() => { copyPromptBtn.textContent = '🔗 프롬프트 복사'; }, 1500);
  } catch {
    alert('클립보드 복사 실패. 브라우저 권한을 확인하세요.');
  }
});
