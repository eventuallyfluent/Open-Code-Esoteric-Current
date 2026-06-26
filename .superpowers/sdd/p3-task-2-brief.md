### Task 2: HMAC signing and WordPress REST client

**Files:**
- Create: `C:\Dev\Open Code Esoteric Current\worker\src\utils\hmac.js`
- Create: `C:\Dev\Open Code Esoteric Current\worker\src\utils\retry.js`
- Create: `C:\Dev\Open Code Esoteric Current\worker\src\wordpress.js`

- [ ] **Step 1: Create hmac.js**

Must match the plugin's `EsotericCurrent\Core\Security\HMACVerifier::sign()` algorithm.

```javascript
import { createHmac, randomBytes } from 'node:crypto';

export function sign(payload, secret, nonce = randomBytes(16).toString('hex')) {
  const timestamp = Math.floor(Date.now() / 1000);
  const data = `${nonce}:${timestamp}:${JSON.stringify(payload)}`;
  const signature = createHmac('sha256', secret).update(data).digest('hex');
  return { nonce, timestamp, signature };
}

export function buildAuthHeaders(payload, apiKey, apiSecret) {
  const { nonce, timestamp, signature } = sign(payload, apiSecret);
  return {
    'X-EC-Key': apiKey,
    'X-EC-Nonce': nonce,
    'X-EC-Timestamp': String(timestamp),
    'X-EC-Signature': signature,
    'Content-Type': 'application/json',
  };
}
```

- [ ] **Step 2: Create retry.js**

```javascript
export async function withRetry(fn, { maxRetries = 3, baseMs = 1000, log = console } = {}) {
  for (let attempt = 0; attempt <= maxRetries; attempt++) {
    try {
      return await fn();
    } catch (err) {
      if (attempt === maxRetries) throw err;
      const wait = baseMs * Math.pow(2, attempt) + Math.random() * 1000;
      log.warn(`retry ${attempt + 1}/${maxRetries} after ${Math.round(wait)}ms: ${err.message}`);
      await new Promise(r => setTimeout(r, wait));
    }
  }
}
```

- [ ] **Step 3: Create wordpress.js**

```javascript
import { request } from 'node:http';
import { buildAuthHeaders } from './utils/hmac.js';
import { withRetry } from './utils/retry.js';

export function createWordPressClient(config, log) {
  async function apiPost(endpoint, payload) {
    const url = new URL(`/wp-json/esoteric-current/v1/${endpoint}`, config.wordpressUrl);
    const headers = buildAuthHeaders(payload, config.wordpressApiKey, config.wordpressApiSecret);

    return withRetry(async () => {
      return new Promise((resolve, reject) => {
        const body = JSON.stringify(payload);
        const req = request(url, {
          method: 'POST',
          headers: { ...headers, 'Content-Length': Buffer.byteLength(body) },
        }, (res) => {
          let data = '';
          res.on('data', chunk => data += chunk);
          res.on('end', () => {
            if (res.statusCode >= 200 && res.statusCode < 300) {
              resolve(JSON.parse(data));
            } else {
              reject(new Error(`WP API ${res.statusCode}: ${data}`));
            }
          });
        });
        req.on('error', reject);
        req.write(body);
        req.end();
      });
    }, { maxRetries: config.maxRetries, log });
  }

  return {
    health:     ()        => apiPost('health', {}),
    claim:      (topics)  => apiPost('claim', { topics }),
    submitNote: (content) => apiPost('note', content),
    submitArticle: (article) => apiPost('article', article),
  };
}
```

Actually, let me reconsider the HTTP module. Node 20 has `fetch` globally available (experimental but stable enough). Let me use that since it's simpler.

Actually, `undici` is bundled but `fetch` is globally available in Node 18+. Let me use the global `fetch`.

- [ ] **Step 3 (revised): Create wordpress.js with global fetch**

```javascript
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
```

- [ ] **Step 4: Commit**

```bash
git add worker/src/utils/hmac.js worker/src/utils/retry.js worker/src/wordpress.js
git commit -m "feat(worker): HMAC signer, retry wrapper, WordPress REST client"
```

---
