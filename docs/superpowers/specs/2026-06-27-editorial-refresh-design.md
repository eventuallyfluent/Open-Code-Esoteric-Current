# The Esoteric Current — Editorial Refresh

## Overview

Design and pipeline overhaul to turn the site from a basic card-grid listing into a
curated editorial destination for esoteric content, and to fix the worker's content
quality.

Two workstreams executed in sequence:
1. **Design/UX** — minimal editorial visual language, component layout refresh
2. **Worker pipeline** — real URL discovery via sitemaps, clean content extraction,
   capped classification, dynamic topic list from WordPress

---

## Workstream A: Design/UX

### Visual Language

| Token | Value | Used for |
|-------|-------|----------|
| `--ec-bg` | `#181817` | Page background (instead of cold black) |
| `--ec-card` | `transparent` or `#1e1e1b` | Card surface |
| `--ec-fg` | `#ece8e0` | Body text (warm off-white) |
| `--ec-muted` | `#8a8678` | Dates, domains, secondary text |
| `--ec-accent` | `#c9a84c` | Type badges, links (muted gold) |
| `--ec-border` | `#2a2824` | Card borders, dividing lines |
| `--ec-glow` | `rgba(201, 168, 76, 0.08)` | Subtle hover state |

No purple. No cyan. Warm, quiet, minimal.

### Typography

| Role | Font | Weight | Size |
|------|------|--------|------|
| Display/headings | Syne | 500 | ~1.25–1.75rem |
| Body text | Lora | 400 | 17px |
| Metadata (dates, domains, type badges) | Inter or Plus Jakarta Sans | 500 | 11–12px |
| Type badges | Inter, uppercase, letter-spaced | 600 | 10px |

### Layout

**Max content width:** 960px (narrower, more comfortable to read)

**Cards:** 3-column grid, first card spans 2 columns (featured slot). No excerpt in
standard cards — only type badge, title, source domain, relative time. Featured card
shows excerpt + topic chips.

**Hero** — minimal. No glow/radial. Small heading (~24px), subheading, search bar.
Done.

**Resource type tabs** — text links with thin underline on active (not pill buttons).
Uppercase, letter-spaced, 12px. Order: All, Books, Courses, Research, Events,
Podcasts, Teachers, Organizations.

**Filter bar** — removed entirely. Non-functional.

**Topic browse** — compact grid of small chips. No background fill on hover, just
border change.

---

## Workstream B: Worker Pipeline

### Dynamic Topics

Replace the hardcoded `CATEGORIES` array in `worker/src/index.js`:

1. New WordPress endpoint `GET /ec/v1/topics` returns
   `[{ id, name, slug, frequency_hours, next_run, last_run }]`
2. On startup, worker fetches topics with `?due=true`
3. If no due topics, exits cleanly
4. New admin-only topics are automatically picked up next run

### Sitemap-Aware Discovery

In `diveSite()`, after getting a verified site URL:

1. Try `{origin}/sitemap_index.xml`, then `{origin}/sitemap.xml`
2. If found, parse XML for `<url><loc>` entries, deduplicate by URL,
   sample up to 10 pages
3. Fall back to RSS/Atom feed if no sitemap
4. Fall back to asking DeepSeek (current behavior) if neither exists

### Cheerio Content Extraction

Replace the current HTML-strip-all approach in `fetchPage()`:

```js
// Pseudo
const $ = cheerio.load(html);
$('script, style, nav, footer, header, aside, iframe').remove();
const main = $('main, article, .content, .post-content, .entry-content').first() || $('body');
return { text: main.text().trim().slice(0, 5000), url: page.url };
```

### Classification Capped at 3

DeepSeek prompt change in `diveSite()`:

> classification: string (exactly 2-3 comma-separated topic keywords from this
> list: Hermeticism, Alchemy, Ceremonial Magic, Kabbalah, Gnosticism, ...)

The known topic list is fetched from WP at worker startup so it stays in sync.

### Pre-flight URL Check

Before `wp.sendCallback()`, verify each finding URL returns `HTTP 200`
within 5s. Skip findings that fail. Log warning.

### What Stays Same

- `isQualityFinding()` filter
- `scoreLink()` domain scoring
- Blocked domains list
- `content_hash` dedup (crypto fix already deployed)
- HMAC auth, WordPress API client
- GitHub Actions scheduling
- The 3-phase loop (discover → dive → report)

---

## Implementation Order

Phase 1 — Design (theme.css + Plugin.php changes):
1. Typography system (Lora + Syne, weight/size adjustments)
2. Color palette (warm tones, kill purple/cyan)
3. Card grid rework (featured slot, remove excerpts from standard cards)
4. Hero, tabs, filter bar, topic browse updates
5. Deploy via FTP, clear cache

Phase 2 — Worker:
1. `GET /ec/v1/topics` endpoint in plugin
2. Dynamic topic fetch in worker (replace hardcoded array)
3. Cheerio install + sitemap parsing in `diveSite()`
4. `fetchPage()` content extraction rewrite
5. Classification cap in DeepSeek prompt
6. Pre-flight URL check
7. Git commit + push

---

## Edge Cases

- **No sitemap + no RSS**: Fall back to current DeepSeek sub-page guess — degraded but functional
- **Sitemap with 10,000 URLs**: Sample max 10, prefer most recent via lastmod
- **Worker errors on third-party site**: Sitemap fetch failure is non-fatal, log warning and continue
- **New topic with no findings yet**: Shows on topic browse but with empty state message
- **Cache purge after deploy**: Use LSCache purge API (force-purge.php exists)
