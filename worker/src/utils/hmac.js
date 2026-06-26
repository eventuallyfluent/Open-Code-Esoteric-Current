import { createHmac, randomBytes } from 'node:crypto';

export function signRequest(method, path, body, secret) {
  const timestamp = Math.floor(Date.now() / 1000);
  const nonce = randomBytes(16).toString('hex');
  const data = `${method}\n${path}\n${body}\n${timestamp}\n${nonce}`;
  const signature = createHmac('sha256', secret).update(data).digest('hex');
  return { nonce, timestamp, signature };
}

export function sign(payload, secret, nonce = randomBytes(16).toString('hex')) {
  const timestamp = Math.floor(Date.now() / 1000);
  const data = `${nonce}:${timestamp}:${JSON.stringify(payload)}`;
  const signature = createHmac('sha256', secret).update(data).digest('hex');
  return { nonce, timestamp, signature };
}
