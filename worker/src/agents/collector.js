export function createCollectorAgent(deepseek) {
  return {
    async deepDive(topic) {
      const systemPrompt = `You are a search agent that finds real, currently-available esoteric resources on the web. Use web search to find actual resources — books, courses, articles, papers, events, teachers, organizations.

Return a JSON object:
{
  title: string (topic title),
  sources: [
    {
      title: string (resource title),
      url: string (REAL URL — must be a real, working URL you found via search),
      relevance: string (1 sentence why this resource matters),
      resource_type: string (book|course|article|paper|event|teacher|organization|podcast)
    }
  ]
}

CRITICAL: Every URL must be a genuine URL you found via web search. Do NOT make up URLs. Only include resources whose URLs you can verify exist. Return at least 3 sources, at most 10.`;

      const res = await deepseek.chat([
        { role: 'system', content: systemPrompt },
        { role: 'user', content: `Search the web for real, currently-available resources about: ${topic.title} — ${topic.reason}. Focus on finding actual books, courses, papers, events, or teachers that exist right now with working URLs.` },
      ], { temperature: 0.3, maxTokens: 4096 });

      const text = res.choices?.[0]?.message?.content || '{}';
      try {
        return JSON.parse(text.replace(/```(?:json)?\n?/g, '').trim());
      } catch {
        return null;
      }
    },
  };
}
