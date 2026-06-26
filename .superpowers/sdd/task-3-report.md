# Task 3 Report: Template parts and patterns

## Status: Complete

## Steps Completed

| Step | File | Status |
|------|------|--------|
| 1 | `theme/parts/masthead.html` | Created |
| 2 | `theme/parts/signals-rail.html` | Created |
| 3 | `theme/parts/policy-strip.html` | Created |
| 4 | `theme/patterns/masthead.php` | Created |
| 5 | `theme/functions.php` | Modified — added `init` hook loading `patterns/masthead.php` |
| 6 | Commit | Done |

## Commit

```
3c135c2 feat(theme): template parts and masthead pattern
```

Files: `theme/parts/masthead.html`, `theme/parts/signals-rail.html`, `theme/parts/policy-strip.html`, `theme/patterns/masthead.php`, `theme/functions.php`

## Verification

- All 3 template part files created under `theme/parts/` with correct WordPress block markup
- `theme/patterns/masthead.php` created with `register_block_pattern` call using slug `observatory-index/masthead`
- `theme/functions.php` updated with `add_action('init', ...)` hook that `require_once`s the pattern file
- Git commit logged with 5 files, 35 insertions

## Concerns

- The pattern file calls `register_block_pattern` from within the `ObservatoryIndex` namespace context (inherited via `require_once`). PHP's function fallback resolves unqualified calls to global, so this works — but a leading `\` could be added for explicitness.
- No runtime test possible without a WordPress instance.

## Report file

`C:\Dev\Open Code Esoteric Current\.superpowers\sdd\task-3-report.md`
