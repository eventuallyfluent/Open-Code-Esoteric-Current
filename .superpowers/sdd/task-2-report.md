# Task 2 Report — All Template Files

**Status:** DONE

**Commit:** `1971cad` — `feat(theme): all template files — home, single, page, archive, search, 404, submission`

**Files created (7):**
- `theme/templates/home.html` — Blog home with masthead pattern, query loop (perPage:10), pagination
- `theme/templates/single.html` — Single post with featured image, title, meta (date/category/tags), thin separators, prev/next nav
- `theme/templates/page.html` — Static page with title and content only
- `theme/templates/archive.html` — Archive with query-title, term-description, thin separator, query loop with pagination
- `theme/templates/search.html` — Search with input, query-title, thin separator, post-title/excerpt loop
- `theme/templates/404.html` — Centered "Not Found" heading, paragraph, search form
- `theme/templates/submission.html` — Custom template with post-title, content, and `wp:ec/submission-form` block

**Verification:** All 7 files created, 139 insertions across them. Templates consume `header`/`footer` template parts from Task 1.

**Test summary:** WordPress block markup — no runtime tests to run at template level.

**Concerns:** None.
