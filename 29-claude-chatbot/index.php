<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI 챗봇 — Claude API</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: #0f1117;
    color: #e2e8f0;
    height: 100vh;
    display: flex;
    overflow: hidden;
  }

  /* ── Sidebar ── */
  #sidebar {
    width: 300px;
    min-width: 300px;
    background: #1a1f2e;
    border-right: 1px solid rgba(255,255,255,0.07);
    display: flex;
    flex-direction: column;
    padding: 20px 18px;
    gap: 14px;
    overflow-y: auto;
  }

  .logo {
    font-size: 17px;
    font-weight: 700;
    color: #e2e8f0;
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 4px;
  }

  .btn-new {
    padding: 9px 14px;
    background: linear-gradient(135deg, #6366f1, #818cf8);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;
  }
  .btn-new:hover { opacity: 0.85; }

  .divider { height: 1px; background: rgba(255,255,255,0.07); }

  .s-label {
    font-size: 10.5px;
    font-weight: 700;
    color: rgba(226,232,240,0.4);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 5px;
  }

  textarea#systemPrompt {
    width: 100%;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 8px;
    padding: 9px 11px;
    color: #cbd5e1;
    font-size: 12px;
    line-height: 1.65;
    resize: vertical;
    font-family: inherit;
    outline: none;
    min-height: 90px;
  }
  textarea#systemPrompt:focus { border-color: rgba(99,102,241,0.5); }

  select#modelSelect {
    width: 100%;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 8px;
    padding: 8px 11px;
    color: #cbd5e1;
    font-size: 12px;
    outline: none;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    padding-right: 28px;
  }
  select#modelSelect:focus { border-color: rgba(99,102,241,0.5); }

  #usageBox {
    margin-top: auto;
    padding: 10px 12px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 8px;
    font-size: 11px;
    color: rgba(226,232,240,0.4);
    line-height: 1.8;
  }
  #usageBox strong { color: rgba(226,232,240,0.65); }

  /* ── Main Chat ── */
  #chatMain {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  #messages {
    flex: 1;
    overflow-y: auto;
    padding: 24px 28px;
    display: flex;
    flex-direction: column;
    gap: 18px;
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.1) transparent;
  }

  /* Welcome */
  .welcome {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    gap: 10px;
    text-align: center;
    padding: 60px 20px;
    color: rgba(226,232,240,0.35);
  }
  .welcome-icon { font-size: 44px; }
  .welcome-title { font-size: 19px; font-weight: 700; color: rgba(226,232,240,0.6); }
  .welcome-sub   { font-size: 13.5px; }

  /* Message rows */
  .msg-row {
    display: flex;
    gap: 10px;
    max-width: 85%;
  }
  .msg-row.user {
    align-self: flex-end;
    flex-direction: row-reverse;
  }
  .msg-row.assistant { align-self: flex-start; }

  .msg-avatar {
    width: 30px; height: 30px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
    margin-top: 2px;
  }
  .msg-row.user .msg-avatar {
    background: linear-gradient(135deg, #6366f1, #818cf8);
  }
  .msg-row.assistant .msg-avatar {
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.1);
  }

  .msg-bubble {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 10px 14px;
    font-size: 14px;
    line-height: 1.75;
    color: #e2e8f0;
    white-space: pre-wrap;
    word-break: break-word;
  }
  .msg-row.user .msg-bubble {
    background: rgba(99,102,241,0.14);
    border-color: rgba(99,102,241,0.28);
  }
  .msg-bubble.loading::after {
    content: '▋';
    animation: blink 0.75s infinite;
    color: #818cf8;
  }
  .msg-bubble.error {
    color: #f87171;
    background: rgba(239,68,68,0.06);
    border-color: rgba(239,68,68,0.2);
  }
  @keyframes blink { 50% { opacity: 0; } }

  /* ── Input Area ── */
  #inputArea {
    padding: 14px 28px 20px;
    border-top: 1px solid rgba(255,255,255,0.07);
    display: flex;
    gap: 10px;
    align-items: flex-end;
  }

  #userInput {
    flex: 1;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.09);
    border-radius: 10px;
    padding: 10px 14px;
    color: #e2e8f0;
    font-size: 14px;
    line-height: 1.5;
    resize: none;
    outline: none;
    max-height: 160px;
    overflow-y: auto;
    font-family: inherit;
    scrollbar-width: thin;
  }
  #userInput:focus { border-color: rgba(99,102,241,0.5); }
  #userInput::placeholder { color: rgba(226,232,240,0.28); }

  #sendBtn {
    padding: 10px 18px;
    background: linear-gradient(135deg, #6366f1, #818cf8);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;
    white-space: nowrap;
    flex-shrink: 0;
    height: 40px;
  }
  #sendBtn:hover:not(:disabled) { opacity: 0.85; }
  #sendBtn:disabled { opacity: 0.35; cursor: not-allowed; }
</style>
</head>
<body>

<!-- ── Sidebar ── -->
<div id="sidebar">
  <div class="logo">🤖 Claude Chat</div>
  <button class="btn-new" id="newChatBtn">+ 새 대화</button>
  <div class="divider"></div>

  <div>
    <div class="s-label">시스템 프롬프트</div>
    <textarea id="systemPrompt" rows="5">당신은 도움이 되는 AI 어시스턴트입니다. 한국어로 답변해주세요.</textarea>
  </div>

  <div>
    <div class="s-label">모델 선택</div>
    <select id="modelSelect">
      <option value="claude-opus-4-6">claude-opus-4-6 (가장 강력)</option>
      <option value="claude-sonnet-4-6">claude-sonnet-4-6 (균형)</option>
      <option value="claude-haiku-4-5">claude-haiku-4-5 (빠름/저비용)</option>
    </select>
  </div>

  <div id="usageBox">토큰 사용량이 여기에 표시됩니다</div>
</div>

<!-- ── Chat Main ── -->
<div id="chatMain">
  <div id="messages">
    <div class="welcome" id="welcome">
      <div class="welcome-icon">🤖</div>
      <div class="welcome-title">Claude AI 챗봇</div>
      <div class="welcome-sub">메시지를 입력해서 대화를 시작하세요</div>
    </div>
  </div>

  <div id="inputArea">
    <textarea id="userInput"
      placeholder="메시지 입력 (Enter: 전송 / Shift+Enter: 줄바꿈)"
      rows="1"></textarea>
    <button id="sendBtn">전송 ↑</button>
  </div>
</div>

<script>
const $id = id => document.getElementById(id);
const messagesEl = $id('messages');

let conversationHistory = []; // { role, content }[]
let totalIn = 0, totalOut = 0;
let sending = false;

// ── 유틸 ─────────────────────────────────────────────────────
function escHtml(t) {
  const d = document.createElement('div');
  d.textContent = t;
  return d.innerHTML;
}

function scrollBottom() {
  messagesEl.scrollTop = messagesEl.scrollHeight;
}

// ── 메시지 버블 추가 ─────────────────────────────────────────
function addBubble(role, text = '', loading = false) {
  const welcome = document.getElementById('welcome');
  if (welcome) welcome.remove();

  const row = document.createElement('div');
  row.className = `msg-row ${role}`;

  const avatar = role === 'user' ? '👤' : '🤖';
  row.innerHTML = `
    <div class="msg-avatar">${avatar}</div>
    <div class="msg-bubble${loading ? ' loading' : ''}">${escHtml(text)}</div>
  `;
  messagesEl.appendChild(row);
  scrollBottom();
  return row.querySelector('.msg-bubble');
}

// ── 토큰 사용량 업데이트 ──────────────────────────────────────
function updateUsage(usage) {
  if (!usage) return;
  totalIn  += usage.input_tokens  || 0;
  totalOut += usage.output_tokens || 0;
  $id('usageBox').innerHTML =
    `<strong>이번 요청</strong><br>` +
    `입력: ${(usage.input_tokens  || 0).toLocaleString()} 토큰<br>` +
    `출력: ${(usage.output_tokens || 0).toLocaleString()} 토큰<br>` +
    `<strong>누적 합계</strong><br>` +
    `입력: ${totalIn.toLocaleString()} 토큰<br>` +
    `출력: ${totalOut.toLocaleString()} 토큰`;
}

// ── 메시지 전송 ───────────────────────────────────────────────
async function sendMessage() {
  if (sending) return;
  const text = $id('userInput').value.trim();
  if (!text) return;

  $id('userInput').value = '';
  $id('userInput').style.height = 'auto';
  $id('sendBtn').disabled = true;
  sending = true;

  // 사용자 메시지 표시 + 히스토리 추가
  conversationHistory.push({ role: 'user', content: text });
  addBubble('user', text);

  // AI 버블 (로딩 커서)
  const aiBubble = addBubble('assistant', '', true);

  try {
    const res = await fetch('chat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        messages: conversationHistory,
        system:   $id('systemPrompt').value.trim(),
        model:    $id('modelSelect').value,
      }),
    });

    const data = await res.json();

    if (data.error) throw new Error(data.error);

    const aiText = data.content || '';
    aiBubble.classList.remove('loading');
    aiBubble.textContent = aiText;

    // 히스토리에 AI 응답 추가
    conversationHistory.push({ role: 'assistant', content: aiText });
    updateUsage(data.usage);

  } catch (err) {
    aiBubble.classList.remove('loading');
    aiBubble.classList.add('error');
    aiBubble.textContent = '오류: ' + err.message;
    // 실패한 user 메시지 롤백
    conversationHistory.pop();
  } finally {
    sending = false;
    $id('sendBtn').disabled = false;
    scrollBottom();
  }
}

// ── 새 대화 ───────────────────────────────────────────────────
$id('newChatBtn').addEventListener('click', () => {
  conversationHistory = [];
  totalIn = totalOut = 0;
  messagesEl.innerHTML = `
    <div class="welcome" id="welcome">
      <div class="welcome-icon">🤖</div>
      <div class="welcome-title">Claude AI 챗봇</div>
      <div class="welcome-sub">메시지를 입력해서 대화를 시작하세요</div>
    </div>`;
  $id('usageBox').textContent = '토큰 사용량이 여기에 표시됩니다';
});

// ── 전송 버튼 / Enter 키 ─────────────────────────────────────
$id('sendBtn').addEventListener('click', sendMessage);
$id('userInput').addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
});

// ── textarea 자동 높이 ────────────────────────────────────────
$id('userInput').addEventListener('input', () => {
  const el = $id('userInput');
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 160) + 'px';
});
</script>
</body>
</html>
