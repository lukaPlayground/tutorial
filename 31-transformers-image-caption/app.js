'use strict';

// ── DOM refs ─────────────────────────────────────────
const statusBadge  = document.getElementById('statusBadge');
const progressWrap = document.getElementById('progressWrap');
const progressFill = document.getElementById('progressFill');
const progressText = document.getElementById('progressText');
const dropZone     = document.getElementById('dropZone');
const fileInput    = document.getElementById('fileInput');
const resultLayout = document.getElementById('resultLayout');
const previewImg   = document.getElementById('previewImg');
const captionBody  = document.getElementById('captionBody');
const inferSpinner = document.getElementById('inferSpinner');
const copyBtn      = document.getElementById('copyBtn');

let currentCaption = '';

// ── Web Worker 초기화 ─────────────────────────────────
const worker = new Worker('./worker.js', { type: 'module' });
worker.postMessage({ type: 'load' });

// ── Worker 메시지 핸들러 ──────────────────────────────
worker.onmessage = ({ data }) => {
  switch (data.type) {

    case 'progress':
      onProgress(data);
      break;

    case 'ready':
      // 모델 준비 완료
      progressWrap.hidden = true;
      setStatus('ready', '● READY');
      dropZone.classList.remove('disabled');
      break;

    case 'result':
      currentCaption = data.text;
      showCaption(data.text);
      setStatus('ready', '● READY');
      break;

    case 'error':
      showError(data.message);
      setStatus('ready', '● READY');
      break;
  }
};

worker.onerror = (e) => {
  showError(`Worker 오류: ${e.message}`);
};

// ── 진행률 처리 ───────────────────────────────────────
function onProgress(data) {
  // status: 'downloading' | 'loading' | 'done' | 'ready' etc.
  if (data.status !== 'downloading' && data.status !== 'progress') return;

  const pct  = Math.round(data.progress ?? 0);
  const file = data.file ? data.file.split('/').pop() : '';

  progressFill.style.width = `${pct}%`;
  progressText.textContent = `${pct}% — ${file || '모델'} 로딩 중...`;
}

// ── 상태 배지 ──────────────────────────────────────────
function setStatus(state, label) {
  statusBadge.textContent = label;
  statusBadge.className   = `badge badge-${state}`;
}

// ── 캡션 표시 ─────────────────────────────────────────
function showCaption(text) {
  inferSpinner.hidden = true;
  captionBody.innerHTML = `<span class="caption-text-value">"${text}"</span>`;
  copyBtn.hidden = false;
}

function showError(msg) {
  inferSpinner.hidden = true;
  captionBody.innerHTML = `<span style="color:#f87171;font-size:13px;">⚠️ ${msg}</span>`;
  copyBtn.hidden = true;
}

// ── 파일 처리 ─────────────────────────────────────────
function processFile(file) {
  if (!file || !file.type.startsWith('image/')) {
    showError('이미지 파일만 지원합니다 (JPG, PNG, WebP, GIF).');
    return;
  }

  const reader = new FileReader();
  reader.onload = ({ target }) => {
    const dataUrl = target.result;

    // 미리보기
    previewImg.src     = dataUrl;
    resultLayout.hidden = false;

    // 추론 시작
    inferSpinner.hidden = false;
    captionBody.innerHTML = '';
    captionBody.appendChild(inferSpinner);
    copyBtn.hidden = true;
    currentCaption = '';
    setStatus('inferring', '🔄 추론 중...');

    worker.postMessage({ type: 'caption', dataUrl });
  };
  reader.readAsDataURL(file);
}

// ── 드래그앤드롭 ──────────────────────────────────────
dropZone.addEventListener('click', () => fileInput.click());

dropZone.addEventListener('dragover', (e) => {
  e.preventDefault();
  dropZone.classList.add('drag-over');
});

dropZone.addEventListener('dragleave', (e) => {
  if (!dropZone.contains(e.relatedTarget)) {
    dropZone.classList.remove('drag-over');
  }
});

dropZone.addEventListener('drop', (e) => {
  e.preventDefault();
  dropZone.classList.remove('drag-over');
  const file = e.dataTransfer.files[0];
  if (file) processFile(file);
});

fileInput.addEventListener('change', (e) => {
  const file = e.target.files[0];
  if (file) {
    processFile(file);
    fileInput.value = ''; // 동일 파일 재선택 허용
  }
});

// ── 복사 버튼 ─────────────────────────────────────────
copyBtn.addEventListener('click', async () => {
  if (!currentCaption) return;
  try {
    await navigator.clipboard.writeText(currentCaption);
    copyBtn.textContent = '✅ 복사됨!';
    setTimeout(() => { copyBtn.textContent = '📋 복사'; }, 1500);
  } catch {
    alert('클립보드 복사 실패. 브라우저 권한을 확인하세요.');
  }
});

// ── 초기 상태: 드롭존 비활성화 (모델 준비 전) ─────────
dropZone.classList.add('disabled');
