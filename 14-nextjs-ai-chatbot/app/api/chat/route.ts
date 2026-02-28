import { openai } from '@ai-sdk/openai';
import { streamText, convertToModelMessages } from 'ai';

export const maxDuration = 30;

export async function POST(req: Request) {
  const { messages } = await req.json();

  const result = streamText({
    model: openai('gpt-4o-mini'),
    system: '당신은 친절하고 유능한 AI 어시스턴트입니다. 한국어로 대화합니다.',
    messages: await convertToModelMessages(messages),
  });

  return result.toTextStreamResponse();
}
