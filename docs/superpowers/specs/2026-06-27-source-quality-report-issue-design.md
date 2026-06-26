# Source Quality Filtering and Report Issue — Design Spec

## Overview

Two independent but complementary features to improve content quality on The Esoteric Current catalogue:

1. **Source domain blocking** — prevents low-quality sources (Wikipedia, Archive.org, general encyclopedias) from being collected by the automation worker in the first place
2. **Report issue button** — allows any visitor to flag a finding as problematic, with admin review workflow

These are Workstream 1 of a three-phase programme:

- **Workstream 1** (this spec): source blocking + report issue — immediate content quality wins
- **Workstream 2**: Category taxonomy overhaul — topic channels, resource types, filter system
- **Workstream 3**: Visual redesign — FutureTools-quality catalogue UI

---

## 1. Source Domain Blocking

### Location

Worker codebase — the collection phase where the agent processes URLs before submitting them to the WordPress API.

### Mechanism

- Add a `BLOCKED_DOMAINS` constant to the worker's configuration
- During collection, after gathering candidate URLs, check each URL's hostname against the blocklist
- If the domain matches, skip that finding entirely — it is never sent to the `/ec/v1/callback` endpoint
- Skipped findings are logged to stdout (visible in GitHub Actions logs) but produce no API call
- No database changes; no plugin changes

### Blocklist (initial)

```
wikipedia.org
archive.org
encyclopedia.com
britannica.com
```

### Future extensibility

The blocklist can be promoted to a plugin-side configuration (wp_options) later if admin control is desired. For now, hardcoded in the worker is sufficient.

### Edge cases

- Subdomains (en.wikipedia.org, www.archive.org) must also match
- URL parsing must handle missing protocols, relative URLs gracefully
- Blocked findings should not count against "findings produced" metrics

---

## 2. Report Issue Button

### Database

New table `ec_finding_flags`:

```sql
CREATE TABLE ec_finding_flags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    finding_id BIGINT UNSIGNED NOT NULL,
    reason VARCHAR(50) NOT NULL DEFAULT 'other',
    ip_address VARCHAR(45) NOT NULL DEFAULT '',
    user_agent VARCHAR(512) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME DEFAULT NULL,
    reviewed_by VARCHAR(60) DEFAULT NULL,
    action_taken VARCHAR(50) DEFAULT NULL,
    INDEX idx_finding (finding_id),
    INDEX idx_reviewed (reviewed_at)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### REST API

**`POST /ec/v1/finding/{id}/flag`**

Request body:
```json
{
    "reason": "low-quality"
}
```

Reason values: `low-quality`, `wrong-category`, `broken-link`, `other`

Response (201):
```json
{
    "status": "flagged",
    "message": "Thank you. We'll review this."
}
```

No authentication required. Rate limited to 5 per IP per 60 seconds (reuses existing `Rate_Limiter` class).

### Frontend (Card UI)

- Each catalogue card (Editorial Feed Block) gains a small flag icon button
- Clicking opens an inline dropdown or simple prompt with the four reason options
- After selection, fires a POST to the flag endpoint
- Shows a brief "Thank you" confirmation (no page reload)
- No login gate — any visitor can flag
- The flag button is visually unobtrusive: small, muted, in the card footer area

### Admin Review Page

New subpage under the EC plugin menu: **Flags** (`admin.php?page=ec-flags`)

- Table of unreviewed flags grouped by finding
- Columns: Finding title, reason, flag count, most recent flag date, IP (if visible)
- Per-row actions: **Dismiss** (mark reviewed, no action), **Unpublish** (set finding status to `rejected`), **Recategorize** (opens edit view)
- Bulk actions: Dismiss selected, Unpublish selected
- After review, flag entry gets `reviewed_at`, `reviewed_by`, `action_taken` populated
- Reviewed flags are hidden from the default view (filterable via tab)

### Rate Limiting

Reuses the existing `Rate_Limiter::check()` method with key `flag_<ip>`, limit 5 per 60 seconds. Returns 429 if exceeded.

---

## 3. Schema Migration

Plugin version bump: `1.2.0` → `1.3.0`

Migration `migrate_1_3_0()`:
- `CREATE TABLE IF NOT EXISTS ec_finding_flags (...)`

---

## 4. Files Changed

| File | Change |
|------|--------|
| `worker/src/index.js` or `worker/src/utils.js` | Add `BLOCKED_DOMAINS` + filter logic |
| `plugin/src/Database/Schema.php` | Add `1.3.0` version |
| `plugin/src/Database/Migration.php` | Add `migrate_1_3_0()` |
| `plugin/src/Api/Flag_Controller.php` | New — flag endpoint |
| `plugin/src/Plugin.php` | Register Flag_Controller route |
| `plugin/src/Admin/Flags_Page.php` | New — admin review page |
| `plugin/src/Blocks/Editorial_Feed_Block.php` | Add flag button to card |
| `plugin/assets/flag.js` | New — flag button JS behaviour |
| `plugin/assets/theme.css` | Flag button styles |

---

## 5. Out of Scope (Workstream 2 & 3)

Not implemented here:
- Topic channel taxonomy
- Resource type classification
- Advanced filter panel
- Visual redesign
- Quality scoring algorithms
- User accounts or moderation queues

---

## 6. Open Questions (Resolved)

- **Auth requirement for flags?** No — anonymous, anyone can flag
- **Rate limit?** Yes — 5/IP/60s
- **Blocklist editable from admin?** Not in this phase — hardcoded in worker
- **What happens to existing low-quality findings?** Unchanged — this only affects future collections
