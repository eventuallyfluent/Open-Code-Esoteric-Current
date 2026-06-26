import { buildAuthHeaders } from './utils/hmac.js';
import { withRetry } from './utils/retry.js';

export function createWordPressClient(config, log) {
  async function apiPost(endpoint, payload) {
    const url = new URL(`/wp-json/esoteric-current/v1/${endpoint}`, config.wordpressUrl).href;

    return withRetry(async () => {
      const headers = buildAuthHeaders(payload, config.wordpressApiKey, config.wordpressApiSecret);
      const res = await fetch(url, {
        method: 'POST',
        headers,
        body: JSON.stringify(payload),
      });
      if (!res.ok) {
        const text = await res.text();
        throw new Error(`WP API ${res.status}: ${text}`);
      }
      return res.json();
    }, { maxRetries: config.maxRetries, log });
  }

  return {
    health:     ()        => apiPost('health', {}),
    claim:      (topics)  => apiPost('claim', { topics }),
    submitNote: (content) => apiPost('note', content),
    submitArticle: (article) => apiPost('article', article),
  };
}
