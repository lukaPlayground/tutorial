'use server';

import { revalidatePath } from 'next/cache';
import { prisma } from '@/lib/prisma';

export async function getTodos() {
  return prisma.todo.findMany({ orderBy: { createdAt: 'desc' } });
}

export async function addTodo(text: string) {
  if (!text.trim()) return;
  await prisma.todo.create({ data: { text: text.trim() } });
  revalidatePath('/');
}

export async function toggleTodo(id: string, done: boolean) {
  await prisma.todo.update({ where: { id }, data: { done: !done } });
  revalidatePath('/');
}

export async function deleteTodo(id: string) {
  await prisma.todo.delete({ where: { id } });
  revalidatePath('/');
}
