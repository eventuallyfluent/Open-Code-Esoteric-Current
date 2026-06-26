export function createCollectorAgent(deepseek) {
  return {
    async deepDive(topic) {
      const systemPrompt = `You are a research assistant compiling a briefing on an esoteric topic. Return a JSON object with: title (string), summary (string, 2-3 paragraphs), key_points (array of strings), sources (array of {title, url?, relevance}), related_traditions (array of strings), significance_score (number 0-1). Be specific and cite verifiable information.`;

      const res = await deepseek.chat([
        { role: 'system', content: systemPrompt },
        { role: 'user', content: `Compile a detailed briefing on: ${topic.title} — ${topic.reason}` },
      ], { temperature: 0.6, maxTokens: 8192 });

      const text = res.choices?.[0]?.message?.content || '{}';
      try {
        return JSON.parse(text.replace(/```(?:json)?\n?/g, '').trim());
      } catch {
        return null;
      }
    },
  };
}
