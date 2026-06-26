import { describe, it } from 'node:test';
import assert from 'node:assert/strict';

describe('Config loading', () => {
  it('throws when required env vars are missing', async () => {
    const orig = { ...process.env };
    delete process.env.DEEPSEEK_API_KEY;
    delete process.env.WORDPRESS_URL;
    delete process.env.WORDPRESS_API_SECRET;
    const { loadConfig } = await import('../src/config.js');
    assert.throws(() => loadConfig(), /Missing required env vars/);
    Object.assign(process.env, orig);
  });

  it('returns frozen config object', async () => {
    process.env.DEEPSEEK_API_KEY = 'sk-test';
    process.env.WORDPRESS_URL = 'https://example.com';
    process.env.WORDPRESS_API_SECRET = 'test-secret';
    const { loadConfig } = await import('../src/config.js');
    const cfg = loadConfig();
    assert.throws(() => { cfg.deepseekApiKey = 'other'; });
  });
});
