## Goal
- Build a Future Tools-style automated content index catalogue for The Esoteric Current — agents discover esoteric news/books/interviews, store as findings in custom tables, admin approves, card grid displays on frontend.

## Constraints & Preferences
- WordPress Full Site Editing block theme, no page builders, no JS build step
- Concept = Future Tools catalogue index (not a blog), not FT styling
- Palette per design spec: Black `#0a0a0a`, warm white `#e8e3da`, muted gold `#c4a45a`, deep burgundy `#4a1a2e`, dark navy `#0f1b2d`, archival tone `#d4cfc4` (no teal/orange/red)
- Typography: Source Serif 4 (headlines), Inter (body), IBM Plex Mono (metadata/labels)
- No rounded cards, neon, purple gradients, occult clichés, crystals, pentagrams
- All CSS classes prefixed with `ec-`
- Theme slug: `observatory-index`
- Theme redesign: Modern Dark Catalogue — clean/sharp, no grain texture, no status bar, no 12-col editorial grid, no lead feature, simplified wordmark logo

## Progress
### Done
- Dark block theme with spec palette/fonts — style.css, theme.json, editor.css
- theme.css fully rewritten for Modern Dark Catalogue (637 lines): removed grain texture, grid lines, editorial grid, concentric logo, status bar, lead feature, live intelligence, verification panel, developments, explore, orbital rings, relevance bars, ↗ arrows, duplicate rules. Added wordmark logo underscore, Inter for nav/search, card lift hover with box-shadow, detail page CSS, 1rem card gap, refined hero with `.ec-hero` class, thinner borders, tighter typography scale.
- Header rewritten: typographic wordmark + gold underscore, Inter nav (not mono), search wraps in `<form>`, no Subscribe button, no status bar
- Footer: ISSN line removed
- Homepage simplified: centered "Catalogue" hero + search + card grid, no lead feature, no intelligence sidebar, no signals-rail
- Finding_Router.php: 43-line inline `<style>` block removed (styles moved to theme.css)
- All 6 templates (home, archive, search, index, page, submission, single, 404, submission) — functional
- 6 page-content patterns moved from `patterns/` to `inc/patterns/` to avoid WordPress auto-discovery conflict
- Parts: header, footer, masthead, signals-rail, policy-strip
- theme.json: palette applied — gold `#c4a45a`, burgundy `#4a1a2e`, navy `#0f1b2d`, archival `#d4cfc4`
- All teal/orange/red replaced with gold/burgundy/navy/archival; color-based class names renamed to semantic
- 11 feed type badge classes with proper colors; topic chips changed to `<a href="/?s=...">` links
- Plugin renamed from `esoteric-current-core` to `esoteric-current` (new slug)
- Plugin activation creates 10 custom tables
- Dashboard page: diagnostic counts + "Seed Test Data" / "Remove Demo Data" buttons
- Research Topics admin page — full add/edit form with all fields, CRUD, nonce security
- Dashboard updated: Quick Start box when no topics exist, "Create Your First Topic" button
- Research Topics page: renamed from "Research Briefs" → "Research Topics", status badges (green Active / red Paused), priority badges, "Due now" indicators, "Run Now" button per topic (sets `next_run_at = NULL`), research goal shown below title
- API secret management — `ec_get_api_secret()` helper, Settings page field, auto-generated on activation
- Claim endpoint — batch claim returns all due topics
- Worker — processes all claimed topics in parallel via `Promise.all`
- Bug fixes: restored `platform_check.php` in ZIP; `/wp-json` prefix for URLs; `$_SERVER['REMOTE_ADDR']` in place of `get_remote_addr()`; Callback_Controller uses `ec_get_api_secret()` directly
- Detail page at `/finding/{id}/`: rewrite rule → Catalogue page via `pagename=catalogue` + `ec_finding_id` query var; `[ec_finding_detail]` shortcode renders finding inline
- GitHub Actions workflows at `.github/workflows/`, Node 22, actions v5
- Worker end-to-end verified: research, claim, collect, synthesize, submit article all succeed
- Findings admin page: approve/reject/publish buttons, bulk actions; approval calls `add_to_editorial_queue()`
- Demo data removal uses typed confirmation (`prompt('Type DELETE')`)
- Theme ZIP: `observatory-index.zip` at project root
- Plugin ZIP: `esoteric-current-updated.zip` at project root
- WordPress Studio local dev environment discovered at `C:\Users\stude\Studio\the-esoteric-current` — site URL `http://localhost:8881/`
- WP_DEBUG enabled via `wp-config.php` and `studio site set --debug-log --debug-display`
- Old `esoteric-current-core` plugin deactivated to avoid block registration conflicts
- New `esoteric-current` plugin copied directly to `wp-content/plugins/esoteric-current/`
- `observatory-index` theme copied directly to `wp-content/themes/observatory-index/` and activated
- Critical error root cause found: `Call to a member function add_rule() on null` in `rewrite.php:143` — `add_rewrite_rule()` called on `plugins_loaded` before `$wp_rewrite` is ready (fatal in WP-CLI context)
- Fix: split version-check logic from `plugins_loaded` to `init` hook where `$wp_rewrite` is always available
- `ec_schema_version` option conflict discovered: old plugin stored `2026062502`, new plugin expected `1.0.0`/`1.0.1` — all migrations skipped, `ec_editorial_queue` table never created, causing SQL errors in `Editorial_Feed_Block`
- Fix: changed schema option key from `ec_schema_version` to `ec_core_schema_version` to avoid conflict with old plugin
- **Root cause of missing `ec_editorial_queue` table found:** `dbDelta()` incompatible with WordPress SQLite integration — runs `ALTER TABLE CHANGE COLUMN` on existing tables, causing UNIQUE constraint violation on `_wp_sqlite_mysql_information_schema_statistics` internal table, which prevents migration completion and `ec_core_schema_version` from being saved
- **First fix applied:** replaced all 10 `dbDelta($sql)` calls with `$wpdb->query("CREATE TABLE IF NOT EXISTS ...")` in `Migration.php` — avoids ALTER TABLE issue entirely; `IF NOT EXISTS` skips existing tables silently
- **Verified:** after site reload, `ec_editorial_queue` table exists, `ec_core_schema_version` = `1.0.1`, debug log clean
- Admin credentials reset: `admin` / `admin123`
- Admin dashboard loads successfully with all 12 EC plugin menu items visible
- **Root cause of seed test data failure found:** WordPress SQLite integration MySQL compatibility layer maps INSERT VALUES by column **ORDER** (not by name) using its internal `_wp_sqlite_mysql_information_schema_columns` table. Old plugin's stale schema had `primary_source_hash BLOB` column at position 6 — the compatibility layer injected a hard-coded `''` for that position, which failed with "cannot store TEXT value in BLOB column". `CREATE TABLE IF NOT EXISTS` left the old schema intact, so the stale MySQL info schema persisted.
- **Second fix:** added `migrate_1_1_0()` to `Migration.php` that `DROP TABLE IF EXISTS` all 10 tables then recreates them fresh with our schema. Updated `Schema.php` to include version `1.1.0`.
- **Verified:** fresh tables have correct columns only (no `topic_uuid`, `primary_source_hash`, `finding_uuid`, etc.), MySQL info schema cleaned up
- **Seed test data successfully loaded:** 5 research topics + 12 approved findings added, homepage card grid renders them with titles/excerpts/topic chips, debug log clean
- **Finding_Router bug fixed:** nested `add_action('init', [self::class, 'register_rewrite'])` inside `init()` call never executed the callback because `init` was already firing when the inner hook was registered. Fixed by inlining `register_rewrite()` directly into `init()` — rewrite rule `^finding/(\d+)/?$` now properly registered
- **Detail page verified:** `/finding/1/` returns HTTP 200 with full ec-detail-item rendering (title, excerpt, Visit Source link, topic chips, relevance/confidence scores, source host, date)
- Debug log clean — all core flows work: homepage grid, detail page, admin dashboard

### In Progress
- (none)

### Blocked
- (none)

## Key Decisions
- Use `ec/editorial-feed` block from plugin as primary homepage content (not `wp:query` for blog posts)
- Plugin uses custom database tables (not WP custom post types) — theme displays via plugin blocks
- Agent runner is external Node.js CLI via GitHub Actions (not WordPress-internal)
- Theme auto-creates required pages on activation via `after_switch_theme` hook
- Plugin slug `esoteric-current` avoids conflict with stuck old `esoteric-current-core` directory
- API secret stored as wp_option, settable from Settings page — no wp-config.php edit needed
- Detail page uses rewrite rule → shortcode within page template (not custom template)
- Logo simplified to typographic wordmark with gold underscore (no concentric circles)
- Research Topics list: "Run Now" sets `next_run_at = NULL` (makes topic due immediately) rather than triggering GitHub Actions API directly
- Version-check rewrite logic moved from `plugins_loaded` to `init` to ensure `$wp_rewrite` is ready
- Schema option key renamed from `ec_schema_version` to `ec_core_schema_version` to avoid collision with old plugin
- `dbDelta()` replaced with `$wpdb->query("CREATE TABLE IF NOT EXISTS ...")` across all 10 table creation methods to maintain SQLite compatibility in WordPress Studio dev environment
- Migration version `1.1.0` drops stale old-plugin tables entirely and recreates fresh with our schema to fix MySQL info schema column ORDER mismatch
- Finding_Router rewrite rule registered directly in `init()` body, not via nested `add_action('init', ...)` — nested add_action never fires because init is already in progress

## Next Steps
1. Disable WP_DEBUG before production (optional — set `WP_DEBUG_DISPLAY = false` to hide from visitors while keeping logging)
2. Consider removing seed test data from production deployment or guarding with `wp_get_environment_type() === 'local'`

## Critical Context
- User feedback after redesign: "not sharp like Future Tools" — CSS was polished with tighter spacing, 1440px max-width, card lift hover with `box-shadow`, Inter nav at weight 450, refined borders
- `add_rewrite_rule()` crashes on `plugins_loaded` when `$wp_rewrite` is null (WP-CLI context) — always use `init` hook for rewrite registration
- Old plugin's `ec_schema_version` option (`2026062502`) caused new plugin to skip all migrations because its versioning scheme (`1.0.0`, `1.0.1`) compares as lower
- WordPress auto-discovers `.php` files in theme `patterns/` directory by parsing file headers — programmatic `register_block_pattern()` calls in the same directory cause "Slug field missing" notices
- **WordPress SQLite MySQL compatibility layer maps INSERT VALUES by column ORDER (not by name).** The `_wp_sqlite_mysql_information_schema_columns` table defines the column order. When `$wpdb->insert()` runs with named columns, the compatibility layer re-maps by ordinal position. Extra columns with incompatible types (e.g., BLOB) cause "cannot store TEXT value in BLOB column" errors. Fix: `DROP TABLE IF EXISTS` + `CREATE TABLE` to clean up both the SQLite table AND the MySQL info schema.
- **Nested `add_action('init', ...)` inside an `init` callback never executes.** When registered during an already-firing hook, it schedules for the NEXT occurrence of that hook — which never comes in WordPress's single-pass hook system. Always register actions directly in the callback body.
- `WP_REST_Request::get_remote_addr()` does not exist — use `$_SERVER['REMOTE_ADDR']` instead
- Worker API paths: `/wp-json/ec/v1/` for URLs, `/ec/v1/` for HMAC signing
- `npm ci` on worker requires `package-lock.json` — committed
- GitHub Actions runner uses Node 24 — actions must target compatible Node (use `@v5`)
- Callback_Controller: must use `ec_get_api_secret()` (not `EC_API_SECRET` constant)
- Findings approval must also insert into `ec_editorial_queue` with `workflow_state = 'published'`
- Admin login: `admin` / `admin123` (reset from auto-generated bcrypt hash)
- Site renders at `http://localhost:8881/` — admin dashboard loads with all 12 EC pages

## Relevant Files
- `docs/superpowers/specs/2026-06-25-the-esoteric-current-design.md`: authoritative design spec
- `docs/superpowers/specs/2026-06-26-theme-redesign-design.md`: approved redesign spec
- `docs/superpowers/plans/2026-06-26-theme-redesign.md`: implementation plan
- `plugin/src/Database/Migration.php`: `migrate_1_1_0()` drops all stale tables and recreates fresh; `migrate_1_0_0()` and `migrate_1_0_1()` use `$wpdb->query("CREATE TABLE IF NOT EXISTS ...")` (not dbDelta) for SQLite compatibility
- `plugin/src/Database/Schema.php`: version list `['1.0.0', '1.0.1', '1.1.0']`
- `plugin/src/Frontend/Finding_Router.php`: rewrite rule registered directly in `init()` (no nested add_action) — `^finding/(\d+)/?$` → `index.php?pagename=catalogue&ec_finding_id=$matches[1]`
- `plugin/src/Admin/Dashboard_Page.php`: seed test data generates 5 topics + 12 findings, works on fresh tables only
- `C:\Users\stude\Studio\the-esoteric-current\`: WordPress Studio local dev environment, site at `http://localhost:8881/`
