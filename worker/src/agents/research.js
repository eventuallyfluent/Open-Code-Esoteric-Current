export function createResearchAgent(deepseek) {
  return {
    async discoverTopics(categories = []) {
      const systemPrompt = `You are a research librarian specializing in esoteric studies. Your role: search the web for genuinely noteworthy current developments — recently published books, upcoming events, new courses, recent academic papers, new organizations or teachers.

Categories: ${categories.join(', ')}.

Return a JSON array of objects with: title (string), category (string), reason (string — why this is noteworthy and what evidence you found), confidence (number 0-1). Maximum 5 items.

IMPORTANT: Use web search to find real, current developments. Do not make up topics from your training data. Every topic should be based on something you found through search.`;

      const res = await deepseek.chat([
        { role: 'system', content: systemPrompt },
        { role: 'user', content: 'Search the web for genuinely noteworthy current developments in esoteric studies — recently published books, upcoming events, new academic papers, new courses, or significant community developments.' },
      ], { temperature: 0.7 });

      const text = res.choices?.[0]?.message?.content || '[]';
      try {
        return JSON.parse(text.replace(/```(?:json)?\n?/g, '').trim());
      } catch {
        return [];
      }
    },
  };
}
