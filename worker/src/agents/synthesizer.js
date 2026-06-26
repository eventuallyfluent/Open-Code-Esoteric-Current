export function createSynthesizerAgent(deepseek) {
  return {
    async synthesize(briefing) {
      const systemPrompt = `You are a senior editor at an esoteric publication. Format the following research briefing into a publishable article. Return JSON: title, content (HTML-formatted article body, 800-1200 words), excerpt (1-2 sentences), tags (array of strings), estimated_reading_time_minutes (number). Tone: authoritative, clear, slightly formal. Use <h2> for section breaks, <blockquote> for notable quotes.`;

      const res = await deepseek.chat([
        { role: 'system', content: systemPrompt },
        { role: 'user', content: JSON.stringify(briefing, null, 2) },
      ], { temperature: 0.5, maxTokens: 16384 });

      const text = res.choices?.[0]?.message?.content || '{}';
      try {
        return JSON.parse(text.replace(/```(?:json)?\n?/g, '').trim());
      } catch {
        return null;
      }
    },
  };
}
