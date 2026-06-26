export function createResearchAgent(deepseek) {
  return {
    async discoverTopics(categories = []) {
      const systemPrompt = `You are a research librarian specializing in esoteric studies. Your role: identify noteworthy current developments, publications, discussions, and emerging topics in esoteric traditions. Categories: ${categories.join(', ')}.

Return a JSON array of objects with: title (string), category (string), reason (string — why this is noteworthy now), confidence (number 0-1). Maximum 5 items.`;

      const res = await deepseek.chat([
        { role: 'system', content: systemPrompt },
        { role: 'user', content: 'What is currently noteworthy in esoteric studies?' },
      ], { temperature: 0.8 });

      const text = res.choices?.[0]?.message?.content || '[]';
      try {
        return JSON.parse(text.replace(/```(?:json)?\n?/g, '').trim());
      } catch {
        return [];
      }
    },
  };
}
