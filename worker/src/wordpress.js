import { signRequest } from './utils/hmac.js';
import { withRetry } from './utils/retry.js';

const API_URL_PREFIX = '/wp-json/ec/v1';
const API_ROUTE_PREFIX = '/ec/v1';

export function createWordPressClient(config, log) {
  function apiUrl(endpoint) {
    return `${API_URL_PREFIX}/${endpoint}`;
  }

  function apiRoute(endpoint) {
    return `${API_ROUTE_PREFIX}/${endpoint}`;
  }

  async function apiPost(endpoint, payload) {
    const url = new URL(apiUrl(endpoint), config.wordpressUrl).href;
    const route = apiRoute(endpoint);

    return withRetry(async () => {
      const body = JSON.stringify(payload);
      const { nonce, timestamp, signature } = signRequest('POST', route, body, config.wordpressApiSecret);
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
    const url = new URL(apiUrl(endpoint), config.wordpressUrl).href;

    return withRetry(async () => {
      const res = await fetch(url, { method: 'GET' });
      if (!res.ok) {
        const text = await res.text();
        throw new Error(`WP API ${res.status}: ${text}`);
      }
      return res.json();
    }, { maxRetries: config.maxRetries, log });
  }

  async function getTopics() {
    const topics = await apiGet('topics');
    if (!Array.isArray(topics)) return [];
    return topics.filter(t => t.title).map(t => t.title);
  }

  return {
    health:        ()        => apiGet('health'),
    claim:         (topics)  => apiPost('claim', { topics }),
    submitArticle: (article) => apiPost('article', article),
    submitNote:    (content) => apiPost('note', content),
    sendCallback:  (data)    => apiPost('callback', data),
    getTopics,
  };
}
