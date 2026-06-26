# The Esoteric Current

Automated esoteric research discovery platform. DeepSeek agents find books, research papers, news, interviews, and events — you approve, and findings display as a searchable card catalogue.

## Architecture

```
Agents (DeepSeek) → GitHub Actions → WordPress REST API → ec_findings table → Admin approves → Card grid
                                        ↑                        ↓
                                   EC_API_SECRET auth     ec_editorial_queue (workflow_state = 'published')
```

- **Plugin** (`esoteric-current`): Custom database tables, admin UI, REST API endpoints, Gutenberg blocks
- **Theme** (`observatory-index`): Dark block theme (black/warm-white/gold/burgundy/navy/archival palette), card grid display
- **Worker** (`worker/`): Node.js agent runner that calls DeepSeek API and submits findings via REST API
- **Orchestration**: GitHub Actions runs the worker on schedule or via manual dispatch

## Requirements

- WordPress ≥ 6.4
- PHP ≥ 8.1
- MySQL / MariaDB
- Node.js ≥ 20 (for local worker)
- DeepSeek API key

## Quick Start

### 1. Plugin

1. Upload `esoteric-current.zip` to WP Admin → Plugins → Add New → Upload
2. Activate
3. Add to `wp-config.php`:
   ```php
   define('EC_API_SECRET', 'generate-a-random-secret-here');
   ```
4. Go to **Esoteric Current → Dashboard** and click **Seed Test Data** to populate sample findings

### 2. Theme

1. Upload `observatory-index.zip` to WP Admin → Appearance → Themes → Add New → Upload
2. Activate
3. The theme auto-creates required pages (About, Submit a Source, Privacy, Terms, Contact, Subscribe)
4. Seed test data creates 12 published findings visible on the homepage card grid

### 3. Configure

WP Admin → **Esoteric Current** menu:

| Page | Purpose |
|------|---------|
| Dashboard | System overview + Seed Test Data button |
| Settings | Model provider, finding limits, cost caps |
| Research Briefs | Topics the agents research |
| Findings | Review and approve agent-discovered content |
| Editorial Queue | Workflow management (discovered → published) |
| Automation | Status dashboard (read-only) |
| Agent Runs | View agent execution history |
| Sources | Manage RSS feeds and content sources |
| System Health | Verify database tables and configuration |

## Agent Pipeline

### GitHub Actions (recommended)

Push this repo to GitHub, then set these **repository secrets** (Settings → Secrets and variables → Actions):

| Secret | Value |
|--------|-------|
| `DEEPSEEK_API_KEY` | Your DeepSeek API key |
| `WORDPRESS_URL` | `https://theesotericcurrent.com` |
| `WORDPRESS_API_SECRET` | Same as `EC_API_SECRET` in `wp-config.php` |

**Run manually:** GitHub → Actions → **Run Automation Worker** → **Run workflow**

**Scheduled runs:** The `schedule.yml` workflow runs daily at 06:00 UTC.

### Local Run

```bash
cd worker
cp .env.example .env   # edit with your keys
npm install
npm start               # live run
npm start -- --dry-run  # test without API calls
```

### What Happens

1. **Claim** — Worker calls `POST /ec/v1/claim` and claims a due research topic
2. **Research** — DeepSeek researches the topic (news, books, papers, events)
3. **Submit** — Results are sent back via `POST /ec/v1/callback`
4. **Findings** — Appear in **Editorial Queue** with status `discovered`
5. **Approve** — Change `workflow_state` to `published` → card appears on homepage

## Content Types

Eleven content types with spec-palette color badges:

| Type | Badge Color |
|------|-------------|
| Books | Burgundy |
| Events | Navy |
| News / Articles | Gold |
| Research Papers | Archival Tone |
| Interviews | Navy |
| Podcasts | Gold |
| Videos | Burgundy |
| Organizations | Navy |
| People | Archival Tone |
| Resources | Gold |
| Developments | Burgundy |

## Topic Taxonomy

Fifteen tracked esoteric traditions: Hermeticism, Alchemy, Astrology, Kabbalah, Gnosticism, Neoplatonism, Theosophy, Rosicrucianism, Ceremonial Magic, Shamanism, Dzogchen, Tantra, Sufism, Mysticism, Esoteric Christianity.

## Theme Customization

The theme is a WordPress Full Site Editing block theme:

- **Homepage**: Uses `ec/editorial-feed` block for the card grid display
- **Customize**: Appearance → Editor — modify templates, colors, fonts
- **Pages**: Standard WP pages use `page.html` template
- **Palette**: Black `#0a0a0a`, warm white `#e8e3da`, muted gold `#c4a45a`, deep burgundy `#4a1a2e`, dark navy `#0f1b2d`, archival tone `#d4cfc4`
- **Typography**: Source Serif 4 (headlines), Inter (body), IBM Plex Mono (metadata)

## Files

```
.
├── .github/workflows/            # GitHub Actions (manual + scheduled)
│   ├── automation.yml
│   └── schedule.yml
├── plugin/                        # WordPress plugin
│   ├── esoteric-current.php       # Plugin entry
│   ├── src/
│   │   ├── Api/                   # REST API controllers (claim, callback, article, health)
│   │   ├── Blocks/                # Gutenberg blocks (editorial-feed, submission-form, etc.)
│   │   ├── Admin/                 # Admin pages (Dashboard, Findings, Queue, etc.)
│   │   ├── Database/              # Schema and migrations (10 tables)
│   │   ├── Repository/            # Data access layer
│   │   ├── Security/              # HMAC auth, nonces, rate limiting
│   │   └── ...                    # Ingestion, integration, workflow
│   └── vendor/
├── theme/                         # WordPress block theme
│   ├── style.css
│   ├── theme.json
│   ├── functions.php              # Fonts, block styles, auto-page-creation
│   ├── assets/theme.css           # Full design system (~1300 lines)
│   ├── parts/                     # header, footer, masthead, signals-rail, policy-strip
│   ├── patterns/                  # Ready-to-use page content (about, contact, privacy, etc.)
│   └── templates/                 # home, archive, search, page, submission, 404, index, single
├── worker/                        # Node.js agent runner
│   ├── src/index.js               # Main entry
│   ├── src/agents/                # Research, collector, synthesizer agents
│   ├── src/utils/                 # HMAC signing, logger, retry
│   └── package.json
├── seed-test-content.sql          # Manual SQL seed (phpMyAdmin fallback)
└── README.md
```
