/**
 * Tutorial 31 — Transformers.js Web Worker
 * 메인 스레드와 postMessage로 통신:
 *   받는 메시지: { type: 'load' } | { type: 'caption', dataUrl: string }
 *   보내는 메시지:
 *     { type: 'progress', status, file?, progress? }
 *     { type: 'ready' }
 *     { type: 'result', text: string }
 *     { type: 'error', message: string }
 */
import { pipeline, env } from 'https://cdn.jsdelivr.net/npm/@xenova/transformers@2.17.2';

// 로컬 모델 비활성화 (CDN에서만 로드)
env.allowLocalModels = false;

let captioner = null;

self.onmessage = async ({ data }) => {

  // ── 모델 로드 ───────────────────────────────────────
  if (data.type === 'load') {
    try {
      captioner = await pipeline(
        'image-to-text',
        'Xenova/vit-gpt2-image-captioning',
        {
          progress_callback: (p) => {
            self.postMessage({ type: 'progress', ...p });
          },
        }
      );
      self.postMessage({ type: 'ready' });
    } catch (err) {
      self.postMessage({ type: 'error', message: `모델 로드 실패: ${err.message}` });
    }
  }

  // ── 이미지 캡셔닝 ────────────────────────────────────
  if (data.type === 'caption') {
    if (!captioner) {
      self.postMessage({ type: 'error', message: '모델이 아직 준비되지 않았습니다.' });
      return;
    }
    try {
      const output = await captioner(data.dataUrl);
      const text   = output?.[0]?.generated_text ?? '(결과 없음)';
      self.postMessage({ type: 'result', text });
    } catch (err) {
      self.postMessage({ type: 'error', message: `추론 실패: ${err.message}` });
    }
  }

};
