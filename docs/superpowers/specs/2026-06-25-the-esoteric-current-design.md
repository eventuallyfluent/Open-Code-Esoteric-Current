# The Esoteric Current — Product Design Document

## Product Overview

The Esoteric Current is an automated esoteric news, research, resource, and editorial publication built around WordPress, AI research agents, and a human editorial workflow.

Inspired by Future Tools but focused on esotericism — aggregates, indexes, and publishes content about Hermeticism, Alchemy, Astrology, Kabbalah, Gnosticism, Shamanism, Dzogchen, Ceremonial Magic, and related traditions.

## Architecture

### Repository structure

```
Open Code Esoteric Current/
├── plugin/              # esoteric-current-core WordPress plugin
├── theme/               # observatory-index block theme
├── worker/              # one-shot CLI automation runner (Node.js)
├── docs/
├── tests/
│   ├── php/             # plugin tests
│   ├── worker/          # runner tests
│   └── theme/           # theme smoke tests
├── composer.json
├── package.json
└── .github/workflows/   # CI, scheduled runs, manual dispatch
```

### Components

1. **esoteric-current-core** (WordPress plugin) — data storage, business rules, admin UI, REST endpoints, six server-rendered blocks
2. **observatory-index** (WordPress block theme) — pure presentation. Templates, patterns, styles. No private table access.
3. **automation-runner** (Node.js CLI) — one-shot script in GitHub Actions. Claims work from WordPress via signed HTTP, executes it, posts results back, exits.

### Data flow

```
Internet → Worker (GH Actions) → Plugin REST API → DB → Plugin Blocks → Theme → Browser
Feeds/Sources → Plugin Ingestion → DB → Editorial Queue → Editor → Publication
Submissions → Plugin → Editorial Queue → Editor → Publication
```

## Plugin: esoteric-current-core

### Custom tables

| Table | Purpose |
|-------|---------|
| `ec_sources` | Registered feeds, websites, RSS/Atom URLs |
| `ec_source_items` | Items fetched from sources |
| `ec_research_topics` | AI agent briefs |
| `ec_agent_runs` | Worker execution records |
| `ec_findings` | Items found by agents |
| `ec_resources` | Curated reference entries |
| `ec_issues` | Publication issues |
| `ec_submissions` | Public URL submissions |
| `ec_editorial_queue` | Unified review queue |
| `ec_run_log` | Structured worker logs |

### Content types

Books, Events, News/Articles, Research Papers, Interviews, Podcasts, Videos, Organizations, People, Resources, Developments

### Topic taxonomy

Hermeticism, Alchemy, Astrology, Kabbalah, Gnosticism, Neoplatonism, Theosophy, Rosicrucianism, Ceremonial Magic, Shamanism, Dzogchen, Esoteric Buddhism, Tantra, Taoist Alchemy, Mysticism (Christian/Sufi/Jewish), Occultism, Paganism, Consciousness Studies, Psychedelics, Enochian, Chaos Magic, Spiritual Practice, Alternative History

### Editorial workflow

`discovered → collected → awaiting_research → researching → awaiting_review → approved → scheduled → published → archived`

Terminal: `rejected`, `duplicate`, `failed`

### Server-rendered blocks

- `ec/unified-search` — Full-text search across findings, resources, editorial
- `ec/editorial-feed` — Curated feed of recent/scheduled items
- `ec/resource-index` — Browsable resource index with filters
- `ec/source-record` — Detail for a registered source
- `ec/issue-contents` — Table of contents for an issue
- `ec/submission-form` — Public URL submission with rate limiting

### Admin screens

Dashboard, Research Briefs, Agent Runs, Findings, Sources, Source Items, Editorial Queue, Resources, Issues, Submissions, Automation, Settings, System Health

### Security

- HMAC SHA-256 signed automation with timestamp + nonce + replay protection
- Capability checks and nonces on admin actions
- Prepared SQL, sanitization, output escaping
- SSRF protection on URL inputs
- Rate limiting and spam protection on submissions

## Theme: observatory-index

### Visual direction

- **Palette**: Black, warm white, muted gold, deep burgundy, dark navy, restrained archival tones
- **Typography**: Serif for headlines/editorial, sans-serif for nav/metadata/filters
- **Layout**: Academic journal meets observatory records meets intelligence dossier
- **Components**: Sharp rectangular cards, thin rules, index lines, numbered sections
- **Tone**: Intelligent, mysterious, restrained, credible, contemporary
- **Avoid**: Purple gradients, glowing effects, crystal-shop styling, rounded SaaS cards

### Templates

home, single, page, archive, search, 404, submission

### Parts

header, footer, masthead, signals-rail, policy-strip

## Worker: automation-runner

### Structure

```
worker/
├── package.json
├── src/
│   ├── main.mjs                 # CLI entry
│   ├── claim.mjs                # HMAC-signed claim from WP
│   ├── pipeline/
│   │   ├── feed-discovery.mjs
│   │   ├── source-extraction.mjs
│   │   ├── agent-orchestrator.mjs
│   │   └── callback.mjs
│   ├── providers/
│   │   ├── model-client.mjs     # DeepSeek abstraction
│   └── browser/
│       ├── playwright.mjs
│       └── captcha-detect.mjs
```

### Execution

1. Parse CLI args
2. Load config from env vars
3. Sign claim request with HMAC + timestamp + nonce
4. POST to WordPress
5. If work → feeds → agents → collect
6. POST results with HMAC signature
7. Exit 0 or non-zero

## Development Phases

### Phase 1 — Plugin
Schema, migrations, services, repositories, admin screens, 6 blocks, security, tests

### Phase 2 — Theme
Observatory Index with all templates, patterns, styling, responsive, accessibility

### Phase 3 — Worker
CLI, claim/callback security, feed ingestion, DeepSeek integration, GitHub Actions, tests

## Cost model

| Item | Cost |
|------|------|
| WordPress hosting | Existing hosting |
| GitHub Actions | $0 |
| Feed parsing | $0 |
| DeepSeek API | Variable, usage-based |
| Playwright | $0 |
| Paid search API | None by design |
