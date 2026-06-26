import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import { withRetry } from '../src/utils/retry.js';

describe('withRetry', () => {
  it('succeeds on first attempt', async () => {
    const result = await withRetry(() => Promise.resolve('ok'));
    assert.equal(result, 'ok');
  });

  it('retries on failure and eventually succeeds', async () => {
    let attempts = 0;
    const result = await withRetry(async () => {
      attempts++;
      if (attempts < 3) throw new Error('fail');
      return 'ok';
    }, { maxRetries: 3, baseMs: 10 });
    assert.equal(result, 'ok');
    assert.equal(attempts, 3);
  });

  it('throws after exhausting retries', async () => {
    await assert.rejects(
      () => withRetry(() => Promise.reject(new Error('always fail')), { maxRetries: 2, baseMs: 10 }),
      /always fail/
    );
  });
});
