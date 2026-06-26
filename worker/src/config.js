import { env } from 'node:process';

const required = ['DEEPSEEK_API_KEY', 'WORDPRESS_URL', 'WORDPRESS_API_SECRET'];

export function loadConfig() {
  const missing = required.filter(k => !env[k]);
  if (missing.length > 0) {
    throw new Error(`Missing required env vars: ${missing.join(', ')}`);
  }
  return Object.freeze({
    deepseekApiKey: env.DEEPSEEK_API_KEY,
    deepseekModel: env.DEEPSEEK_MODEL || 'deepseek-chat',
    wordpressUrl: env.WORDPRESS_URL.replace(/\/+$/, ''),
    wordpressApiSecret: env.WORDPRESS_API_SECRET,
    logLevel: env.LOG_LEVEL || 'info',
    dryRun: env.DRY_RUN === 'true' || process.argv.includes('--dry-run'),
    maxRetries: Number(env.MAX_RETRIES || '3'),
  });
}
