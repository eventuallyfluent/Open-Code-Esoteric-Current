# Theme Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign the block theme from atmospheric/grain aesthetic to a sharp modern dark catalogue (Future Tools-style).

**Architecture:** Block theme (FSE) with CSS in `theme.css`, block markup in `parts/` and `templates/`. Plugin provides `[ec_finding_detail]` shortcode. All CSS prefixed with `ec-`. All style changes are in `theme/assets/theme.css` — remove ~200 lines of dead code, rewrite remaining, add ~150 lines for new components.

**Tech Stack:** WordPress 6.4+, block theme (FSE), no JS build step.

## Global Constraints

- All CSS classes prefixed with `ec-`
- Palette: bg `#0a0a0a`, panel `#121212`, border `#2a2520`, text `#e8e3da`, muted `#8a8580`, gold `#c4a45a`, burgundy `#4a1a2e`, navy `#0f1b2d`, archival `#d4cfc4`
- Typography: Source Serif 4 (headlines), Inter (body), IBM Plex Mono (labels)
- No rounded corners, no grain texture, no teal/orange/red
- Fonts loaded via Google Fonts in theme's `functions.php`

---

### Task 1: Header — new logo, remove status bar, clean nav

**Files:**
- Modify: `theme/parts/header.html`

- [ ] **Step 1: Rewrite header.html**

Replace entire content. Remove status bar (`ec-status-bar` div and its CSS). Replace concentric circle logo with typographic wordmark. Update nav links to Inter (remove uppercase). Remove Subscribe button.

```html
<!-- wp:html -->
<header class="ec-header" role="banner">
  <div class="ec-header-inner">
    <a href="/" class="ec-logo-wrap" aria-label="The Esoteric Current home">
      <span class="ec-logo-wordmark">The Esoteric Current</span>
      <span class="ec-logo-underscore" aria-hidden="true"></span>
    </a>
    <nav class="ec-header-nav" aria-label="Main navigation">
      <a href="/" class="ec-active">Home</a>
      <a href="/catalogue/">Catalogue</a>
      <a href="/submissions/">Submit</a>
    </nav>
    <div class="ec-header-search" role="search">
      <form action="/" method="get">
        <input type="search" name="s" placeholder="Search the catalogue..." aria-label="Search" />
      </form>
    </div>
    <button class="ec-hamburger" type="button" aria-label="Menu">
      <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
        <line x1="3" y1="5" x2="17" y2="5" />
        <line x1="3" y1="10" x2="17" y2="10" />
        <line x1="3" y1="15" x2="17" y2="15" />
      </svg>
    </button>
  </div>
</header>
<!-- /wp:html -->
```

- [ ] **Step 2: git add and commit**

```bash
git add theme/parts/header.html
git commit -m "feat: clean header — wordmark logo, Inter nav, no status bar"
```

---

### Task 2: Footer — remove ISSN line

**Files:**
- Modify: `theme/parts/footer.html`

- [ ] **Step 1: Remove ISSN reference**

```html
  <div class="ec-footer-bottom">
    <span>&copy; 2026 The Esoteric Current. All rights reserved.</span>
  </div>
```

Replace the entire `ec-footer-bottom` div — remove the ISSN `<span>`, keep just copyright.

- [ ] **Step 2: Commit**

```bash
git add theme/parts/footer.html
git commit -m "chore: remove ISSN line from footer"
```

---

### Task 3: Homepage template — simplify to catalogue layout

**Files:**
- Modify: `theme/templates/home.html`

- [ ] **Step 1: Rewrite home.html**

Remove lead feature, intelligence sidebar, 12-column grid structure. Replace with simple hero + card grid + topic bar.

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","style":{"spacing":{"padding":{"top":"0"}}},"layout":{"type":"default"}} -->
<main class="wp-block-group">

  <!-- Hero -->
  <!-- wp:group {"style":{"spacing":{"padding":{"top":"4rem","padding-bottom":"3rem"}}},"className":"ec-container"} -->
  <div class="wp-block-group ec-container" style="padding-top:4rem;padding-bottom:3rem">
    <!-- wp:group {"layout":{"type":"constrained","contentSize":"720px"},"style":{"spacing":{"margin":{"left":"auto","right":"auto"},"text-align":"center"}}} -->
    <div class="wp-block-group" style="text-align:center;margin-left:auto;margin-right:auto;max-width:720px">
      <!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"clamp(2rem,4vw,2.75rem)","fontWeight":"400"}},"textColor":"text","fontFamily":"serif"} -->
      <h1 class="wp-block-heading has-text-color has-serif-font-family" style="font-size:clamp(2rem,4vw,2.75rem);font-weight:400">Catalogue</h1>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"muted","fontSize":"medium"} -->
      <p class="has-muted-color has-medium-font-size">A curated index of esoteric research and discovery</p>
      <!-- /wp:paragraph -->
      <!-- wp:search {"label":"","placeholder":"Search the catalogue...","buttonText":"Search","buttonPosition":"no-button","query":{"post_type":"page"},"style":{"spacing":{"margin":{"top":"1.5rem"}}}} /-->
    </div>
    <!-- /wp:group -->
  </div>
  <!-- /wp:group -->

  <!-- Findings Grid -->
  <!-- wp:group {"className":"ec-container","style":{"spacing":{"padding":{"bottom":"3rem"}}}} -->
  <div class="wp-block-group ec-container" style="padding-bottom:3rem">
    <!-- wp:ec/editorial-feed {"count":12,"display":"grid","columns":3,"show_excerpt":true} /-->
  </div>
  <!-- /wp:group -->

  <!-- Topic Categories -->
  <!-- wp:html -->
  <div class="ec-topics-bar">
    <div class="ec-container">
      <div class="ec-topics-label">Browse by Topic</div>
      <div class="ec-topics-list">
        <a href="/?s=alchemy" class="ec-topic-chip">Alchemy</a>
        <a href="/?s=hermeticism" class="ec-topic-chip">Hermeticism</a>
        <a href="/?s=astrology" class="ec-topic-chip">Astrology</a>
        <a href="/?s=kabbalah" class="ec-topic-chip">Kabbalah</a>
        <a href="/?s=gnosticism" class="ec-topic-chip">Gnosticism</a>
        <a href="/?s=neoplatonism" class="ec-topic-chip">Neoplatonism</a>
        <a href="/?s=theosophy" class="ec-topic-chip">Theosophy</a>
        <a href="/?s=ceremonial-magic" class="ec-topic-chip">Ceremonial Magic</a>
        <a href="/?s=sufism" class="ec-topic-chip">Sufism</a>
        <a href="/?s=mysticism" class="ec-topic-chip">Mysticism</a>
      </div>
    </div>
  </div>
  <!-- /wp:html -->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

- [ ] **Step 2: Commit**

```bash
git add theme/templates/home.html
git commit -m "feat: simplify homepage to catalogue layout with hero + grid"
```

---

### Task 4: Remove inline CSS from Finding_Router shortcode

**Files:**
- Modify: `plugin/src/Frontend/Finding_Router.php`

- [ ] **Step 1: Remove the `<style>` block from `render_shortcode()`**

Delete lines 101-144 (the entire `<style>...</style>` block) from `render_shortcode()`. The detail page styles will be in theme.css (Task 5).

- [ ] **Step 2: Commit**

```bash
git add plugin/src/Frontend/Finding_Router.php
git commit -m "refactor: remove inline CSS from detail shortcode, will move to theme.css"
```

---

### Task 5: theme.css — full rewrite

**Files:**
- Modify: `theme/assets/theme.css`

This is the largest task. Remove grain texture, 12-col editorial grid, lead feature, intelligence sidebar, status bar CSS, relevance bars, ↗ arrows, concentric ring logo. Add new header/logo styles, card hover lift, detail page styles.

- [ ] **Step 1: Remove dead CSS blocks**

Delete these sections from theme.css:

1. **Grain texture** — `body::before` block (lines 26-36)
2. **Grid background lines** — `.ec-grid-bg` block (lines 59-70)
3. **Editorial grid** — `.ec-editorial-grid`, `.ec-col-*` (lines 72-96)
4. **Concentric ring logo** — `.ec-logo`, `.ec-logo-ring*`, `.ec-logo-dot` (lines 106-145)
5. **Logo tagline** — `.ec-logo-tagline` (lines 159-166)
6. **Status bar** — `.ec-status-bar*` (lines 271-314)
7. **Lead feature** — `.ec-lead-feature`, `.ec-lead-content`, `.ec-lead-image`, etc. (lines 316-394)
8. **Metadata cells** — `.ec-meta-row`, `.ec-meta-cell`, `.ec-meta-label`, `.ec-meta-value` (lines 396-423) — but keep these if used on the detail page? Check: the shortcode uses `ec-meta-row`, `ec-meta-cell`, `ec-meta-label`, `ec-meta-value` for the detail metadata. KEEP these.
9. **Live Intelligence** — `.ec-intelligence*` (lines 425-530)
10. **Verification panel** — `.ec-verification*` (lines 531-600)
11. **Development section** — `.ec-devel-grid`, `.ec-devel-story*` (lines 601+)
12. **Exploration section** — `.ec-explore*` (lines 700+)
13. **Article styles** — `.ec-article*` (lines 800+)
14. **Relevance bar** — `.ec-feed-card-relevance*` (lines 1184-1195)
15. **↗ arrow** — `.ec-feed-card-title a::after` (lines 1153-1161) — remove the entire `::after` rule
16. **Teal hover** — Change `.ec-feed-card:hover` from `#0d212a` to `var(--ec-panel)` — we'll rewrite this in step 2

- [ ] **Step 2: Add new CSS**

Append to theme.css (or insert in logical sections):

```css
/* === Wordmark Logo === */
.ec-logo-wrap {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  text-decoration: none;
  gap: 4px;
  flex-shrink: 0;
}
.ec-logo-wordmark {
  font-family: var(--ec-font-serif);
  font-size: 1.125rem;
  font-weight: 400;
  color: var(--ec-text);
  letter-spacing: -0.01em;
  line-height: 1;
}
.ec-logo-underscore {
  display: block;
  width: 40px;
  height: 2px;
  background: var(--ec-gold);
}
```

Replace the concentric logo section (lines 98-166) with this.

- [ ] **Step 3: Rewrite card hover**

Find `.ec-feed-card:hover` (line 1095). Replace:

```css
.ec-feed-card:hover {
  border-color: var(--ec-gold);
  transform: translateY(-2px);
}
```

Add transition to `.ec-feed-card` (line 1086):

```css
.ec-feed-card {
  background: var(--ec-panel);
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
  transition: border-color 0.2s, transform 0.2s;
  position: relative;
  border: 1px solid var(--ec-border);
}
```

- [ ] **Step 4: Remove ↗ arrow**

Delete the entire `.ec-feed-card-title a::after` rule (lines 1153-1161) and the duplicate `.ec-feed-card-title a` rules (lines 1197-1199).

- [ ] **Step 5: Remove relevance bar**

Delete `.ec-feed-card-relevance` and `.ec-feed-card-relevance-bar` (lines 1184-1195).

- [ ] **Step 6: Update card grid gap**

Change `.ec-feed-grid` gap from `1px` to `1rem` and remove the `background: var(--ec-border)` / `border` — cards should have individual borders instead:

```css
.ec-feed-grid {
  display: grid;
  grid-template-columns: repeat(var(--ec-feed-cols, 3), 1fr);
  gap: 1rem;
}
```

- [ ] **Step 7: Update header CSS**

Update `.ec-header-nav a` to use Inter instead of mono, remove uppercase:

```css
.ec-header-nav a {
  font-family: var(--ec-font-sans);
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--ec-muted);
  text-decoration: none;
  padding: 0.25rem 0;
  position: relative;
  transition: color var(--ec-transition);
  white-space: nowrap;
}
```

- [ ] **Step 8: Update search form**

Replace inline `.ec-header-search input` with a form-aware rule:

```css
.ec-header-search form {
  display: flex;
  align-items: center;
}
.ec-header-search input {
  background: transparent;
  border: 1px solid var(--ec-border);
  padding: 0.375rem 0.75rem;
  font-family: var(--ec-font-sans);
  font-size: 0.8125rem;
  color: var(--ec-text);
  width: 200px;
  outline: none;
  transition: border-color var(--ec-transition);
}
.ec-header-search input:focus {
  border-color: var(--ec-gold);
}
.ec-header-search input::placeholder {
  color: var(--ec-muted);
}
```

- [ ] **Step 9: Add detail page CSS**

Add after the card styles section (after line 1200):

```css
/* === Detail Page === */
.ec-detail-item {
  max-width: 720px;
  margin: 3rem auto;
  padding: 0 1.5rem;
}
.ec-detail-item h1.ec-detail-title {
  font-family: var(--ec-font-serif);
  font-size: clamp(1.75rem, 4vw, 2.5rem);
  font-weight: 400;
  line-height: 1.3;
  margin: 0 0 1rem;
  color: var(--ec-text);
}
.ec-detail-breadcrumb {
  font-size: 0.85rem;
  margin-bottom: 0.75rem;
  color: var(--ec-muted);
}
.ec-detail-breadcrumb a {
  color: var(--ec-gold);
  text-decoration: none;
}
.ec-detail-breadcrumb a:hover {
  text-decoration: underline;
}
.ec-detail-sep {
  margin: 0 0.4em;
  color: var(--ec-muted);
}
.ec-detail-desc {
  font-family: var(--ec-font-sans);
  font-size: 1.05rem;
  line-height: 1.7;
  color: var(--ec-text);
  margin-bottom: 1.5rem;
}
.ec-detail-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 2rem;
}
```

- [ ] **Step 10: Commit**

```bash
git add theme/assets/theme.css
git commit -m "feat: rewrite theme.css — clean dark catalogue, remove grain/grid/status bar"
```

---

### Task 6: Final verification

**Files:**
- No file changes — just checks

- [ ] **Step 1: Verify no teal references remain**

Run: `rg -i "teal|0d212a|00a8" theme/` — should match nothing.

- [ ] **Step 2: Verify no grain texture**

Run: `rg "fractalNoise|grain" theme/` — should match nothing.

- [ ] **Step 3: Verify nav uses Inter**

Run: `rg "ec-header-nav" theme/assets/theme.css` — should show `var(--ec-font-sans)` on the font-family line.

- [ ] **Step 4: Verify no status bar**

Run: `rg "ec-status-bar" theme/` — should match nothing.

- [ ] **Step 5: Verify homepage blocks**

View the homepage — hero should show "Catalogue" heading, tagline, search, card grid below.

- [ ] **Step 6: Verify detail page**

Open any finding detail — should have breadcrumb, title, description, Visit Source button, metadata row. No inline `<style>` in page source.

- [ ] **Step 7: Commit verification**

```bash
git add -A
git commit -m "chore: cleanup — remove dead code references"
```
