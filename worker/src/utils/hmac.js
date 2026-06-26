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
