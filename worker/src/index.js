import { loadConfig } from './config.js';
import { createLogger } from './utils/logger.js';

async function main() {
  const config = loadConfig();
  const log = createLogger(config.logLevel);

  log.info('worker-start', { dryRun: config.dryRun });

  // Phase 1: Research — discover topics via DeepSeek
  // Phase 2: Collect — deep-dive on each topic
  // Phase 3: Synthesize — format into articles
  // Phase 4: Submit — POST to WordPress REST API

  log.info('worker-complete');
}

main().catch(err => {
  console.error(JSON.stringify({ l: 'error', t: new Date().toISOString(), m: err.message, stack: err.stack }));
  process.exit(1);
});
