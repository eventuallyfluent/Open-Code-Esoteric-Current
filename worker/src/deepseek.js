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
          enable_search: true,
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
