# Theme Redesign: Modern Dark Catalogue

## Overview

Redesign the Future Tools-styled catalogue for The Esoteric Current. Drop the atmospheric/grain aesthetic for a **sharp, clean dark catalogue** — think Arc DevTools dark mode, not occult moodboard. Use the existing palette (black `#0a0a0a`, warm white `#e8e3da`, gold `#c4a45a`, burgundy `#4a1a2e`, navy `#0f1b2d`, archival `#d4cfc4`) but apply it sparingly and deliberately.

## What We Remove

| Element | Why |
|---------|-----|
| Grain texture overlay (`body::before`) | Makes everything look muddy, not sharp |
| 12-column editorial grid with 1px borders | Busy, distracts from content |
| Status bar in header | Noise, no clear purpose for catalogue |
| "Live Intelligence" sidebar section | Not relevant to catalogue browsing |
| Lead feature layout | Overcomplicated, not used |
| Relevance score bars on cards | Visual noise, not core info |
| ↗ arrows after card title links | Distracting, Future Tools doesn't use them |
| Teal hover (`#0d212a`) on cards | Doesn't match palette (was leftover from teal era) |
| Concentric circle SVG logo | Not sharp at small sizes, complex |

## What We Keep

- Dark background (`#0a0a0a`), panel (`#121212`), border (`#2a2520`)
- Gold (`#c4a45a`) as primary accent color
- Font stack: Source Serif 4 (headlines), Inter (body), IBM Plex Mono (labels)
- `ec-` prefixed CSS classes
- All template structure (FSE block templates)

## Design Spec

### Logo

Replace concentric circles with a **pure typographic wordmark**:

```
The Esoteric Current
⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯
```

- Text: Source Serif 4, 1.125rem, warm white
- Underscore: 2px gold line, 40px wide, 4px below text
- No tagline, no rings, no dot
- Wrapped in `<a href="/">` as before

### Header

```
┌─────────────────────────────────────────────────────┐
│ Logo  |  Home  Catalogue  Submit  │  [ Search ]     │
└─────────────────────────────────────────────────────┘
```

- Background: `var(--ec-bg-dark)` with 1px bottom border
- Nav links: Inter (not mono), 0.8125rem, muted, uppercase removed
- Active link: gold underline
- Search input: functional (submits to `/?s=`), placeholder text, gold focus
- No Subscribe button (no backend)
- No hamburger needed until mobile (<768px)
- Logo + nav left-aligned, search right-aligned

### Homepage

```
┌──────────────────────────────────────────────────────┐
│  Catalogue                                            │
│  A curated index of esoteric research and discovery   │
│  [ Search the catalogue... ]                          │
│                                                        │
│  ┌──────┐ ┌──────┐ ┌──────┐                           │
│  │ CARD │ │ CARD │ │ CARD │                           │
│  │      │ │      │ │      │                           │
│  └──────┘ └──────┘ └──────┘                           │
│  ┌──────┐ ┌──────┐ ┌──────┐                           │
│  │ CARD │ │ CARD │ │ CARD │                           │
│  │      │ │      │ │      │                           │
│  └──────┘ └──────┘ └──────┘                           │
│                                                        │
│  [Alchemy] [Hermeticism] [Kabbalah] [Gnosticism] ...   │
└──────────────────────────────────────────────────────┘
```

- No 12-col grid, no editorial borders
- Hero area: centered, max-width 720px
  - h1: "Catalogue", Source Serif 4, 2.5rem
  - p: tagline, Inter, 1rem, muted
  - Search input: full-width within 720px, styled with gold border on focus
- Card grid: 3 columns (→ 2 → 1 responsive), gap 1rem, no 1px grid borders

### Cards

```
┌─────────────────────────┐
│ BOOK             88%    │  ← type badge + confidence
│                         │
│ The Kybalion:           │  ← title (serif, white)
│ Centenary Edition       │
│                         │
│ A new annotated edition │  ← excerpt (Inter, muted, 2-line clamp)
│ of the foundational...  │
│                         │
│ publisher.example.com  │  ← footer: source + relative date
│ 2h ago                  │
└─────────────────────────┘
```

- Background: `var(--ec-panel)` with 1px `var(--ec-border)` border
- Border-radius: 0 (keep sharp/square per spec)
- Hover: border color changes to `var(--ec-gold)`, subtle translateY(-2px) lift
- Padding: 1.25rem
- Gap between elements: 0.625rem
- No relevance bar, no ↗ arrow
- Card links to `/finding/{id}/` detail page
- Type badge colors preserved from existing classes
- Confidence score shown as `N%` in mono, muted

### Detail Page

Already implemented as shortcode within the theme's page template. Just need to:
- Remove the inline `<style>` block (move styles to theme.css)
- Keep the markup structure: breadcrumb, title, description, Visit Source button, metadata

### Topic Bar

- Same as current design, just adjust spacing
- Gold border on hover for chips
- Keep `ec-topic-chip` class styling

### Footer

- Keep current structure (3 columns: brand, sections, resources)
- Remove ISSN line from bottom bar (not needed for a catalogue)
- Keep copyright

### Responsive

- 3 columns → 2 at 1024px → 1 at 768px
- Search bar goes full-width on mobile
- Nav links collapse behind hamburger on mobile (keep hamburger from current design)

### Color Usage

| Element | Color |
|---------|-------|
| Page background | `#0a0a0a` |
| Panel/card background | `#121212` |
| Borders | `#2a2520` |
| Body text | `#e8e3da` |
| Muted/secondary text | `#8a8580` |
| Primary accent (links, active) | `#c4a45a` |
| Type badge colors | Per existing scheme (gold, burgundy, navy, archival) |
| Hover accent | `#c4a45a` (gold only, no teal) |

### Typography

| Use | Font | Size | Weight |
|-----|------|------|--------|
| Page title (h1) | Source Serif 4 | 2.5rem | 400 |
| Card title | Source Serif 4 | 1rem | 400 |
| Body / description | Inter | 0.9375rem | 400 |
| Nav links | Inter | 0.8125rem | 500 |
| Type badges / labels | IBM Plex Mono | 0.625rem | 500 |
| Card footer | IBM Plex Mono | 0.625rem | 400 |
| Metadata labels | IBM Plex Mono | 0.5625rem | 400 |

## Files to Modify

1. `theme/assets/theme.css` — major rewrite (~600 lines)
2. `theme/theme.json` — minor: update button styles, remove unused blocks
3. `theme/parts/header.html` — remove status bar, new logo, simplified nav
4. `theme/parts/footer.html` — remove ISSN line
5. `theme/templates/home.html` — simplified hero, no lead/intelligence sections
6. `plugin/src/Frontend/Finding_Router.php` — remove inline `<style>`, add CSS to theme.css
7. `plugin/src/Blocks/Editorial_Feed_Block.php` — CSS classes already correct, no changes needed
