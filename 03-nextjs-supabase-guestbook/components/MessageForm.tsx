'use client'

import { useRef, useState, useTransition } from 'react'
import { addMessage } from '@/app/actions'

export default function MessageForm() {
  const formRef = useRef<HTMLFormElement>(null)
  const [error, setError]       = useState<string | null>(null)
  const [isPending, startTransition] = useTransition()

  function handleSubmit(formData: FormData) {
    startTransition(async () => {
      const result = await addMessage(formData)
      if (result?.error) {
        setError(result.error)
      } else {
        setError(null)
        formRef.current?.reset()
      }
    })
  }

  return (
    <form
      ref={formRef}
      action={handleSubmit}
      className="rounded-2xl border p-6 flex flex-col gap-4"
      style={{ background: 'rgba(255,255,255,0.04)', borderColor: 'rgba(255,255,255,0.08)' }}
    >
      <h2 className="text-base font-semibold" style={{ color: 'rgba(226,232,240,0.7)' }}>
        방명록 남기기
      </h2>

      <input
        name="name"
        type="text"
        placeholder="이름 (최대 20자)"
        maxLength={20}
        required
        className="rounded-xl px-4 py-3 text-sm outline-none transition-colors"
        style={{
          background: 'rgba(0,0,0,0.25)',
          border: '1px solid rgba(255,255,255,0.07)',
          color: '#e2e8f0',
        }}
      />

      <textarea
        name="content"
        placeholder="내용을 입력하세요 (최대 200자)"
        maxLength={200}
        required
        rows={3}
        className="rounded-xl px-4 py-3 text-sm outline-none resize-none transition-colors"
        style={{
          background: 'rgba(0,0,0,0.25)',
          border: '1px solid rgba(255,255,255,0.07)',
          color: '#e2e8f0',
        }}
      />

      {error && (
        <p className="text-sm" style={{ color: '#f87171' }}>{error}</p>
      )}

      <button
        type="submit"
        disabled={isPending}
        className="rounded-xl py-3 text-sm font-semibold text-white transition-opacity disabled:opacity-50"
        style={{ background: 'linear-gradient(135deg, #3b82f6, #60a5fa)' }}
      >
        {isPending ? '저장 중...' : '남기기'}
      </button>
    </form>
  )
}
