### Task 1: Worker scaffold

**Files:**
- Create: `C:\Dev\Open Code Esoteric Current\worker\package.json`
- Create: `C:\Dev\Open Code Esoteric Current\worker\.env.example`
- Create: `C:\Dev\Open Code Esoteric Current\worker\src\index.js`
- Create: `C:\Dev\Open Code Esoteric Current\worker\src\config.js`
- Create: `C:\Dev\Open Code Esoteric Current\worker\src\utils\logger.js`

- [ ] **Step 1: Create package.json**

```json
{
  "name": "esoteric-current-worker",
  "version": "1.0.0",
  "private": true,
  "type": "module",
  "description": "One-shot automation worker for The Esoteric Current â€” runs in GitHub Actions",
  "main": "src/index.js",
  "scripts": {
    "start": "node src/index.js",
    "test": "node --test tests/**/*.test.js",
    "dry-run": "node src/index.js --dry-run"
  },
  "engines": {
    "node": ">=20.0.0"
  },
  "devDependencies": {}
}
```

- [ ] **Step 2: Create .env.example**

```env
# Required
DEEPSEEK_API_KEY=sk-your-key-here
WORDPRESS_URL=https://theesotericcurrent.com
WORDPRESS_API_KEY=your-ec-api-key
WORDPRESS_API_SECRET=your-ec-api-secret

# Optional
DEEPSEEK_MODEL=deepseek-chat
LOG_LEVEL=info
DRY_RUN=false
```

- [ ] **Step 3: Create src/config.js**

```javascript
import { env } from 'node:process';

const required = ['DEEPSEEK_API_KEY', 'WORDPRESS_URL', 'WORDPRESS_API_KEY', 'WORDPRESS_API_SECRET'];

export function loadConfig() {
  const missing = required.filter(k => !env[k]);
  if (missing.length > 0) {
    throw new Error(`Missing required env vars: ${missing.join(', ')}`);
  }
  return Object.freeze({
    deepseekApiKey: env.DEEPSEEK_API_KEY,
    deepseekModel: env.DEEPSEEK_MODEL || 'deepseek-chat',
    wordpressUrl: env.WORDPRESS_URL.replace(/\/+$/, ''),
    wordpressApiKey: env.WORDPRESS_API_KEY,
    wordpressApiSecret: env.WORDPRESS_API_SECRET,
    logLevel: env.LOG_LEVEL || 'info',
    dryRun: env.DRY_RUN === 'true' || process.argv.includes('--dry-run'),
    maxRetries: Number(env.MAX_RETRIES || '3'),
  });
}
```

- [ ] **Step 4: Create src/utils/logger.js**

```javascript
const levels = { error: 0, warn: 1, info: 2, debug: 3 };

export function createLogger(level = 'info') {
  const threshold = levels[level] ?? levels.info;
  return {
    error: (...args) => threshold >= 0 && console.error(JSON.stringify({ l: 'error', t: new Date().toISOString(), m: args.join(' ') })),
    warn:  (...args) => threshold >= 1 && console.warn( JSON.stringify({ l: 'warn',  t: new Date().toISOString(), m: args.join(' ') })),
    info:  (...args) => threshold >= 2 && console.log(  JSON.stringify({ l: 'info',  t: new Date().toISOString(), m: args.join(' ') })),
    debug: (...args) => threshold >= 3 && console.log(  JSON.stringify({ l: 'debug', t: new Date().toISOString(), m: args.join(' ') })),
  };
}
```

- [ ] **Step 5: Create src/index.js (skeleton)**

```javascript
import { loadConfig } from './config.js';
import { createLogger } from './utils/logger.js';

async function main() {
  const config = loadConfig();
  const log = createLogger(config.logLevel);

  log.info('worker-start', { dryRun: config.dryRun });

  // Phase 1: Research â€” discover topics via DeepSeek
  // Phase 2: Collect â€” deep-dive on each topic
  // Phase 3: Synthesize â€” format into articles
  // Phase 4: Submit â€” POST to WordPress REST API

  log.info('worker-complete');
}

main().catch(err => {
  console.error(JSON.stringify({ l: 'error', t: new Date().toISOString(), m: err.message, stack: err.stack }));
  process.exit(1);
});
```

- [ ] **Step 6: Commit**

```bash
git add worker/
git commit -m "feat(worker): scaffold â€” package.json, env config, logger, entry point"
```

---
