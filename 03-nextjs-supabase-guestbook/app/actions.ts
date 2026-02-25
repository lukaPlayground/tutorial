'use server'

import { revalidatePath } from 'next/cache'
import { supabase } from '@/lib/supabase'

export async function addMessage(formData: FormData) {
  const name    = (formData.get('name') as string).trim()
  const content = (formData.get('content') as string).trim()

  if (!name || !content) return { error: '이름과 내용을 모두 입력해주세요.' }
  if (name.length > 20)    return { error: '이름은 20자 이내로 입력해주세요.' }
  if (content.length > 200) return { error: '내용은 200자 이내로 입력해주세요.' }

  const { error } = await supabase
    .from('messages')
    .insert({ name, content })

  if (error) return { error: '저장에 실패했습니다.' }

  revalidatePath('/')
  return { success: true }
}

export async function deleteMessage(id: number) {
  const { error } = await supabase
    .from('messages')
    .delete()
    .eq('id', id)

  if (error) return { error: '삭제에 실패했습니다.' }

  revalidatePath('/')
  return { success: true }
}
