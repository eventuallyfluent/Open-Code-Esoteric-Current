import { signRequest } from './utils/hmac.js';
import { withRetry } from './utils/retry.js';

const API_NAMESPACE = '/ec/v1';

export function createWordPressClient(config, log) {
  function apiPath(endpoint) {
    return `${API_NAMESPACE}/${endpoint}`;
  }

  async function apiPost(endpoint, payload) {
    const path = apiPath(endpoint);
    const url = new URL(path, config.wordpressUrl).href;

    return withRetry(async () => {
      const body = JSON.stringify(payload);
      const { nonce, timestamp, signature } = signRequest('POST', path, body, config.wordpressApiSecret);
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'X-EC-Nonce': nonce,
          'X-EC-Timestamp': String(timestamp),
          'X-EC-Signature': signature,
          'Content-Type': 'application/json',
        },
        body,
      });
      if (!res.ok) {
        const text = await res.text();
        throw new Error(`WP API ${res.status}: ${text}`);
      }
      return res.json();
    }, { maxRetries: config.maxRetries, log });
  }

  async function apiGet(endpoint) {
    const path = apiPath(endpoint);
    const url = new URL(path, config.wordpressUrl).href;

    return withRetry(async () => {
      const res = await fetch(url, { method: 'GET' });
      if (!res.ok) {
        const text = await res.text();
        throw new Error(`WP API ${res.status}: ${text}`);
      }
      return res.json();
    }, { maxRetries: config.maxRetries, log });
  }

  return {
    health:        ()        => apiGet('health'),
    claim:         (topics)  => apiPost('claim', { topics }),
    submitArticle: (article) => apiPost('article', article),
    submitNote:    (content) => apiPost('note', content),
    sendCallback:  (data)    => apiPost('callback', data),
  };
}
