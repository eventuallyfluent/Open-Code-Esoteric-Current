# Phase 3 — automation-runner Worker Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a one-shot Node.js CLI worker that runs in GitHub Actions, orchestrates DeepSeek AI agents to discover esoteric research, and sends results to the WordPress REST API.

**Architecture:** Node.js 20 CLI, no Express/Koa — pure script. Calls DeepSeek API for agent orchestration. Posts findings back to WordPress via HMAC-signed REST calls. Designed for one-shot execution in GitHub Actions hosted runner — no permanently running process, no database on the worker side.

**Tech Stack:** Node.js 20, `undici` for HTTP, DeepSeek API, GitHub Actions workflow, `dotenv` for local dev, Playwright (optional, for RSS feed inspection).

### Security Constraints

- No secrets committed — all tokens/secrets via environment variables or GitHub Secrets
- HMAC SHA-256 signing for all WordPress REST API calls (using plugin's `ec_hmac_sign()` compatible algorithm)
- Rate-limited retry logic (exponential backoff) for both DeepSeek and WordPress API calls
- All output goes to stdout — no local file writes in production
- `undici` HTTP client (bundled with Node 20+) — no axios/fetch polyfill dependency

## Workspace

```
worker/
├── package.json
├── .env.example
├── .github/
│   └── workflows/
│       └── automation.yml
├── src/
│   ├── index.js              # Entry point — orchestrates the flow
│   ├── config.js             # Env var loader with validation
│   ├── deepseek.js           # DeepSeek API client
│   ├── wordpress.js          # HMAC-signed WordPress REST client
│   ├── agents/
│   │   ├── research.js       # Research agent — discovers esoteric topics
│   │   ├── collector.js      # Collection agent — deep-dive on specific topic
│   │   └── synthesizer.js    # Synthesis agent — formats into post/article
│   └── utils/
│       ├── hmac.js            # HMAC SHA-256 signing (compatible with plugin)
│       ├── retry.js           # Exponential backoff retry wrapper
│       └── logger.js          # Structured JSON logger
└── tests/
    ├── hmac.test.js
    ├── config.test.js
    ├── wordpress.test.js
    └── agents.test.js
```

---
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
  "description": "One-shot automation worker for The Esoteric Current — runs in GitHub Actions",
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
```

- [ ] **Step 6: Commit**

```bash
git add worker/
git commit -m "feat(worker): scaffold — package.json, env config, logger, entry point"
```

---
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
### Task 3: DeepSeek agent clients

**Files:**
- Create: `C:\Dev\Open Code Esoteric Current\worker\src\deepseek.js`
- Create: `C:\Dev\Open Code Esoteric Current\worker\src\agents\research.js`
- Create: `C:\Dev\Open Code Esoteric Current\worker\src\agents\collector.js`
- Create: `C:\Dev\Open Code Esoteric Current\worker\src\agents\synthesizer.js`

- [ ] **Step 1: Create deepseek.js**

```javascript
import { withRetry } from './utils/retry.js';

export function createDeepSeekClient(config, log) {
  async function chat(messages, options = {}) {
    const { model = config.deepseekModel, temperature = 0.7, maxTokens = 4096 } = options;

    return withRetry(async () => {
      const res = await fetch('https://api.deepseek.com/v1/chat/completions', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${config.deepseekApiKey}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          model,
          messages,
          temperature,
          max_tokens: maxTokens,
        }),
      });
      if (!res.ok) {
        const text = await res.text();
        throw new Error(`DeepSeek API ${res.status}: ${text}`);
      }
      return res.json();
    }, { maxRetries: config.maxRetries, log });
  }

  return { chat };
}
```

- [ ] **Step 2: Create agents/research.js**

Research agent receives a list of esoteric topics/categories (from config or WordPress) and asks DeepSeek to discover current noteworthy developments, new publications, or emerging topics.

```javascript
export function createResearchAgent(deepseek) {
  return {
    async discoverTopics(categories = []) {
      const systemPrompt = `You are a research librarian specializing in esoteric studies. Your role: identify noteworthy current developments, publications, discussions, and emerging topics in esoteric traditions. Categories: ${categories.join(', ')}.

Return a JSON array of objects with: title (string), category (string), reason (string — why this is noteworthy now), confidence (number 0-1). Maximum 5 items.`;

      const res = await deepseek.chat([
        { role: 'system', content: systemPrompt },
        { role: 'user', content: 'What is currently noteworthy in esoteric studies?' },
      ], { temperature: 0.8 });

      const text = res.choices?.[0]?.message?.content || '[]';
      try {
        return JSON.parse(text.replace(/```(?:json)?\n?/g, '').trim());
      } catch {
        return [];
      }
    },
  };
}
```

- [ ] **Step 3: Create agents/collector.js**

Collection agent takes a specific topic and deep-dives, returning structured research.

```javascript
export function createCollectorAgent(deepseek) {
  return {
    async deepDive(topic) {
      const systemPrompt = `You are a research assistant compiling a briefing on an esoteric topic. Return a JSON object with: title (string), summary (string, 2-3 paragraphs), key_points (array of strings), sources (array of {title, url?, relevance}), related_traditions (array of strings), significance_score (number 0-1). Be specific and cite verifiable information.`;

      const res = await deepseek.chat([
        { role: 'system', content: systemPrompt },
        { role: 'user', content: `Compile a detailed briefing on: ${topic.title} — ${topic.reason}` },
      ], { temperature: 0.6, maxTokens: 8192 });

      const text = res.choices?.[0]?.message?.content || '{}';
      try {
        return JSON.parse(text.replace(/```(?:json)?\n?/g, '').trim());
      } catch {
        return null;
      }
    },
  };
}
```

- [ ] **Step 4: Create agents/synthesizer.js**

Synthesizer takes the research and formats it into a publishable article structure.

```javascript
export function createSynthesizerAgent(deepseek) {
  return {
    async synthesize(briefing) {
      const systemPrompt = `You are a senior editor at an esoteric publication. Format the following research briefing into a publishable article. Return JSON: title, content (HTML-formatted article body, 800-1200 words), excerpt (1-2 sentences), tags (array of strings), estimated_reading_time_minutes (number). Tone: authoritative, clear, slightly formal. Use <h2> for section breaks, <blockquote> for notable quotes.`;

      const res = await deepseek.chat([
        { role: 'system', content: systemPrompt },
        { role: 'user', content: JSON.stringify(briefing, null, 2) },
      ], { temperature: 0.5, maxTokens: 16384 });

      const text = res.choices?.[0]?.message?.content || '{}';
      try {
        return JSON.parse(text.replace(/```(?:json)?\n?/g, '').trim());
      } catch {
        return null;
      }
    },
  };
}
```

- [ ] **Step 5: Commit**

```bash
git add worker/src/deepseek.js worker/src/agents/
git commit -m "feat(worker): DeepSeek client and agent implementations — research, collector, synthesizer"
```

---
### Task 4: Orchestrator (index.js) — connect the pipeline

**Files:**
- Modify: `C:\Dev\Open Code Esoteric Current\worker\src\index.js`

- [ ] **Step 1: Rewrite index.js with full orchestration**

```javascript
import { loadConfig } from './config.js';
import { createLogger } from './utils/logger.js';
import { createDeepSeekClient } from './deepseek.js';
import { createWordPressClient } from './wordpress.js';
import { createResearchAgent } from './agents/research.js';
import { createCollectorAgent } from './agents/collector.js';
import { createSynthesizerAgent } from './agents/synthesizer.js';

const ESOTERIC_CATEGORIES = [
  'Hermeticism',
  'Alchemy',
  'Astrology',
  'Ceremonial Magic',
  'Kabbalah',
  'Gnosticism',
  'Dzogchen',
  'Shamanism',
  'Tantra',
  'Sufism',
  'Mysticism',
  'Esoteric Christianity',
  'Theosophy',
  'Rosicrucianism',
  'Neoplatonism',
];

async function main() {
  const config = loadConfig();
  const log = createLogger(config.logLevel);

  log.info('worker-start', { dryRun: config.dryRun, model: config.deepseekModel });

  // Check WordPress connectivity
  const wp = createWordPressClient(config, log);
  const deepseek = createDeepSeekClient(config, log);

  if (!config.dryRun) {
    try {
      await wp.health();
      log.info('wp-health-ok');
    } catch (err) {
      log.error('wp-health-failed', err.message);
      process.exit(1);
    }
  }

  // Phase 1: Research
  log.info('phase-research-start');
  const research = createResearchAgent(deepseek);
  let topics;
  if (config.dryRun) {
    topics = [
      { title: 'Test Topic', category: 'Hermeticism', reason: 'Dry run test', confidence: 0.5 },
    ];
    log.info('dry-run: using mock topics');
  } else {
    topics = await research.discoverTopics(ESOTERIC_CATEGORIES);
    log.info('phase-research-complete', { count: topics.length });
  }

  // Phase 2: Claim topics with WordPress
  let claimResult;
  if (!config.dryRun && topics.length > 0) {
    claimResult = await wp.claim(topics);
    log.info('topics-claimed', { count: topics.length, claimed: claimResult?.claimed?.length ?? 0 });
  }

  // Phase 3: Collect + Synthesize for each topic
  const collector = createCollectorAgent(deepseek);
  const synthesizer = createSynthesizerAgent(deepseek);

  const articles = [];
  const topicsToProcess = config.dryRun ? topics : (claimResult?.claimed ?? topics);

  for (const topic of topicsToProcess) {
    log.info('process-topic', { title: topic.title });

    // Collect
    const briefing = await collector.deepDive(topic);
    if (!briefing) {
      log.warn('collect-failed', { title: topic.title });
      continue;
    }
    log.info('collect-complete', { title: topic.title, keyPoints: briefing.key_points?.length ?? 0 });

    // Synthesize
    const article = await synthesizer.synthesize(briefing);
    if (!article) {
      log.warn('synthesize-failed', { title: topic.title });
      continue;
    }
    log.info('synthesize-complete', { title: article.title, wordCount: article.content?.length ?? 0 });

    articles.push({ ...article, _briefing: briefing });

    // Submit to WordPress
    if (!config.dryRun) {
      await wp.submitArticle({
        title: article.title,
        content: article.content,
        excerpt: article.excerpt,
        tags: article.tags,
        source_category: topic.category,
        research_notes: JSON.stringify(briefing),
      });
      log.info('article-submitted', { title: article.title });
    }
  }

  log.info('worker-complete', { articlesProduced: articles.length });
}

main().catch(err => {
  console.error(JSON.stringify({ l: 'error', t: new Date().toISOString(), m: err.message, stack: err.stack }));
  process.exit(1);
});
```

- [ ] **Step 2: Commit**

```bash
git add worker/src/index.js
git commit -m "feat(worker): full orchestration pipeline — research → claim → collect → synthesize → submit"
```

---
### Task 5: GitHub Actions workflow

**Files:**
- Create: `C:\Dev\Open Code Esoteric Current\worker\.github\workflows\automation.yml`
- Create: `C:\Dev\Open Code Esoteric Current\worker\.github\workflows\schedule.yml`

- [ ] **Step 1: Create automation.yml (manual + PR trigger)**

```yaml
name: Run Automation Worker

on:
  workflow_dispatch:
    inputs:
      dry_run:
        description: 'Dry run (no API calls)'
        type: boolean
        default: false

jobs:
  run:
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: ./worker
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
          cache-dependency-path: ./worker/package.json
      - run: npm ci --omit=dev
      - run: npm start
        env:
          DEEPSEEK_API_KEY: ${{ secrets.DEEPSEEK_API_KEY }}
          WORDPRESS_URL: ${{ secrets.WORDPRESS_URL }}
          WORDPRESS_API_KEY: ${{ secrets.WORDPRESS_API_KEY }}
          WORDPRESS_API_SECRET: ${{ secrets.WORDPRESS_API_SECRET }}
          DRY_RUN: ${{ inputs.dry_run || 'false' }}
```

- [ ] **Step 2: Create schedule.yml (daily cron)**

```yaml
name: Scheduled Automation

on:
  schedule:
    # Runs at 06:00 UTC daily
    - cron: '0 6 * * *'

jobs:
  run:
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: ./worker
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
          cache-dependency-path: ./worker/package.json
      - run: npm ci --omit=dev
      - run: npm start
        env:
          DEEPSEEK_API_KEY: ${{ secrets.DEEPSEEK_API_KEY }}
          WORDPRESS_URL: ${{ secrets.WORDPRESS_URL }}
          WORDPRESS_API_KEY: ${{ secrets.WORDPRESS_API_KEY }}
          WORDPRESS_API_SECRET: ${{ secrets.WORDPRESS_API_SECRET }}
```

- [ ] **Step 3: Commit**

```bash
git add worker/.github/
git commit -m "feat(worker): GitHub Actions workflows — manual trigger and daily schedule"
```
