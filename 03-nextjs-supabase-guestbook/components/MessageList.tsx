'use client'

import { useTransition } from 'react'
import { deleteMessage } from '@/app/actions'
import type { Message } from '@/lib/supabase'

function timeAgo(dateStr: string) {
  const diff = Date.now() - new Date(dateStr).getTime()
  const m = Math.floor(diff / 60000)
  const h = Math.floor(diff / 3600000)
  const d = Math.floor(diff / 86400000)
  if (m < 1)  return '방금 전'
  if (m < 60) return `${m}분 전`
  if (h < 24) return `${h}시간 전`
  return `${d}일 전`
}

function MessageItem({ msg }: { msg: Message }) {
  const [isPending, startTransition] = useTransition()

  function handleDelete() {
    if (!confirm('삭제하시겠습니까?')) return
    startTransition(() => deleteMessage(msg.id))
  }

  return (
    <li
      className="rounded-2xl p-5 flex flex-col gap-2 transition-opacity"
      style={{
        background: 'rgba(255,255,255,0.03)',
        border: '1px solid rgba(255,255,255,0.06)',
        opacity: isPending ? 0.4 : 1,
      }}
    >
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <div
            className="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white"
            style={{ background: 'linear-gradient(135deg, #3b82f6, #60a5fa)' }}
          >
            {msg.name[0].toUpperCase()}
          </div>
          <span className="text-sm font-semibold" style={{ color: '#e2e8f0' }}>
            {msg.name}
          </span>
        </div>
        <div className="flex items-center gap-3">
          <span className="text-xs" style={{ color: 'rgba(226,232,240,0.35)' }}>
            {timeAgo(msg.created_at)}
          </span>
          <button
            onClick={handleDelete}
            disabled={isPending}
            className="text-xs transition-colors disabled:opacity-30"
            style={{ color: 'rgba(226,232,240,0.3)' }}
            onMouseEnter={e => (e.currentTarget.style.color = '#f87171')}
            onMouseLeave={e => (e.currentTarget.style.color = 'rgba(226,232,240,0.3)')}
          >
            삭제
          </button>
        </div>
      </div>
      <p className="text-sm leading-relaxed" style={{ color: 'rgba(226,232,240,0.7)' }}>
        {msg.content}
      </p>
    </li>
  )
}

export default function MessageList({ messages }: { messages: Message[] }) {
  if (messages.length === 0) {
    return (
      <p className="text-center py-12 text-sm" style={{ color: 'rgba(226,232,240,0.3)' }}>
        아직 방명록이 없습니다. 첫 번째로 남겨보세요!
      </p>
    )
  }

  return (
    <ul className="flex flex-col gap-3">
      {messages.map(msg => (
        <MessageItem key={msg.id} msg={msg} />
      ))}
    </ul>
  )
}
