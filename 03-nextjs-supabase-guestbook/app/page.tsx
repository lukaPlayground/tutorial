import { supabase } from '@/lib/supabase'
import MessageForm from '@/components/MessageForm'
import MessageList from '@/components/MessageList'

// 30초마다 ISR 재생성
export const revalidate = 30

async function getMessages() {
  const { data, error } = await supabase
    .from('messages')
    .select('*')
    .order('created_at', { ascending: false })
    .limit(50)

  if (error) return []
  return data
}

export default async function Home() {
  const messages = await getMessages()

  return (
    <main className="min-h-screen px-4 py-16">
      <div className="mx-auto max-w-xl">
        {/* Header */}
        <div className="mb-10">
          <h1 className="text-3xl font-bold tracking-tight mb-2" style={{ color: '#e2e8f0' }}>
            방명록
          </h1>
          <p className="text-sm" style={{ color: 'rgba(226,232,240,0.4)' }}>
            Next.js + Supabase · {messages.length}개의 메시지
          </p>
        </div>

        {/* Form */}
        <div className="mb-8">
          <MessageForm />
        </div>

        {/* List */}
        <MessageList messages={messages} />
      </div>
    </main>
  )
}
