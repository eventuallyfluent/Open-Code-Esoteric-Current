# Task 1 Report: Block Low-Quality Source Domains

**Status:** DONE

**Commits:**
- `a3bc5fd` — feat: block low-quality source domains in worker collection

**Files changed:**
- `worker/src/index.js` — added `BLOCKED_DOMAINS` set, `filterBriefingSources()` function, and filter call in `processRun()`

**Implementation:**
The filter runs between `collector.deepDive(topic)` and `synthesizer.synthesize(briefing)` in the `processRun` function. It removes sources whose URL's hostname matches a blocked domain (exact match or subdomain match via `endsWith`).

**Blocked domains:**
- `wikipedia.org` (catches `en.wikipedia.org`, `de.wikipedia.org`, etc.)
- `archive.org` (catches `web.archive.org`, etc.)
- `britannica.com` (catches `www.britannica.com`, etc.)

**Self-review findings:**
- Null/undefined `briefing.sources`: handled by guard clause returning early
- Empty sources array: no-op, filtered count is 0, no log emitted
- Source with no `url` property: preserved (can't filter on missing data)
- Invalid/malformed URL: try/catch around `new URL()`, preserved (fail-open)
- Subdomain matching: `hostname.endsWith('.' + domain)` correctly catches subdomains like `en.wikipedia.org`
- False positive prevention: `evilwikipedia.org` does not match `wikipedia.org` (no `endsWith('.wikipedia.org')` and not exact match)
- Filtering mutates `briefing.sources` in place, which propagates correctly to both the WordPress submission payload and the callback findings

**Concerns:** None.
