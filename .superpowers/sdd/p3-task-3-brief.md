### Task 3: DeepSeek agent clients

**Files:**
- Create: `C:\Dev\Open Code Esoteric Current\worker\src\deepseek.js`
- Create: `C:\Dev\Open Code Esoteric Current\worker\src\agents\research.js`
- Create: `C:\Dev\Open Code Esoteric Current\worker\src\agents\collector.js`
- Create: `C:\Dev\Open Code Esoteric Current\worker\src\agents\synthesizer.js`

- [ ] **Step 1: Create deepseek.js**

```javascript
import { withRetry } from './utils/retry.js';

export function createDeepSeekClient(config, log) {
  async function chat(messages, options = {}) {
    const { model = config.deepseekModel, temperature = 0.7, maxTokens = 4096 } = options;

    return withRetry(async () => {
      const res = await fetch('https://api.deepseek.com/v1/chat/completions', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${config.deepseekApiKey}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          model,
          messages,
          temperature,
          max_tokens: maxTokens,
        }),
      });
      if (!res.ok) {
        const text = await res.text();
        throw new Error(`DeepSeek API ${res.status}: ${text}`);
      }
      return res.json();
    }, { maxRetries: config.maxRetries, log });
  }

  return { chat };
}
```

- [ ] **Step 2: Create agents/research.js**

Research agent receives a list of esoteric topics/categories (from config or WordPress) and asks DeepSeek to discover current noteworthy developments, new publications, or emerging topics.

```javascript
export function createResearchAgent(deepseek) {
  return {
    async discoverTopics(categories = []) {
      const systemPrompt = `You are a research librarian specializing in esoteric studies. Your role: identify noteworthy current developments, publications, discussions, and emerging topics in esoteric traditions. Categories: ${categories.join(', ')}.

Return a JSON array of objects with: title (string), category (string), reason (string â€” why this is noteworthy now), confidence (number 0-1). Maximum 5 items.`;

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
```

- [ ] **Step 3: Create agents/collector.js**

Collection agent takes a specific topic and deep-dives, returning structured research.

```javascript
export function createCollectorAgent(deepseek) {
  return {
    async deepDive(topic) {
      const systemPrompt = `You are a research assistant compiling a briefing on an esoteric topic. Return a JSON object with: title (string), summary (string, 2-3 paragraphs), key_points (array of strings), sources (array of {title, url?, relevance}), related_traditions (array of strings), significance_score (number 0-1). Be specific and cite verifiable information.`;

      const res = await deepseek.chat([
        { role: 'system', content: systemPrompt },
        { role: 'user', content: `Compile a detailed briefing on: ${topic.title} â€” ${topic.reason}` },
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
```

- [ ] **Step 4: Create agents/synthesizer.js**

Synthesizer takes the research and formats it into a publishable article structure.

```javascript
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
```

- [ ] **Step 5: Commit**

```bash
git add worker/src/deepseek.js worker/src/agents/
git commit -m "feat(worker): DeepSeek client and agent implementations â€” research, collector, synthesizer"
```

---
