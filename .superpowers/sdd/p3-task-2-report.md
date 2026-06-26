# Phase 3 Task 2 Report: HMAC signing and WordPress REST client

## Status: Complete

### Files created

| File | Purpose |
|------|---------|
| `worker/src/utils/hmac.js` | `sign()` and `buildAuthHeaders()` — HMAC-SHA256 signing matching plugin's `HMACVerifier::sign()` |
| `worker/src/utils/retry.js` | `withRetry()` — exponential backoff with jitter |
| `worker/src/wordpress.js` | `createWordPressClient()` — REST client for `/wp-json/esoteric-current/v1/*` endpoints using global `fetch` |

### Implementation notes

- HMAC: uses `node:crypto` `createHmac('sha256')`, data format `nonce:timestamp:JSON.stringify(payload)`, random 16-byte hex nonce
- Retry: `baseMs * 2^attempt + random jitter`, defaults 3 retries / 1s base, logs warnings
- Client: global `fetch` (Node 18+), exposes `health()`, `claim(topics)`, `submitNote(content)`, `submitArticle(article)`
- Headers: `X-EC-Key`, `X-EC-Nonce`, `X-EC-Timestamp`, `X-EC-Signature`, `Content-Type: application/json`

### Commit

`d81717b` — `feat(worker): HMAC signer, retry wrapper, WordPress REST client`
