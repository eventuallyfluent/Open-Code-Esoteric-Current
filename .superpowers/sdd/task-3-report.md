# Task 3 Report: Flag REST Endpoint

## What I implemented

Created `Flag_Controller.php` — an anonymous REST endpoint that accepts flag submissions against findings:

- **Route:** `POST /ec/v1/finding/{id}/flag`
- **Auth:** Anonymous (`__return_true` permission callback)
- **Rate limit:** 5 requests per 60 seconds per IP (via `Rate_Limiter`)
- **Body:** `{ "reason": "inaccurate|harmful|plagiarism|spam|other" }`
- **Validation:** finding must exist (checked via `Finding_Repository::get_by_id()`), reason must be one of 5 allowed values
- **Insert:** Records finding_id, reason, ip_address, reviewed=0 into `ec_finding_flags`
- **Response:** `{ "success": true, "flag_id": <int> }` or standard error format

## Files changed

| File | Change |
|------|--------|
| `plugin/src/Api/Flag_Controller.php` | Created — 70-line controller class |
| `plugin/src/Plugin.php` | Added `use` import + `rest_api_init` registration for Flag_Controller |

## Self-review findings

| Check | Status | Notes |
|-------|--------|-------|
| Route format | ✓ | Matches `ec/v1` namespace, uses regex `(?P<id>\d+)` for path param |
| Registration order | ✓ | Placed after Claim_Controller in Plugin.php, before Article_Controller |
| Anonymous access | ✓ | `permission_callback => '__return_true'` |
| IP-based rate limit | ✓ | `Rate_Limiter::check('flag_' . $ip, 5, 60)` — consistent with existing pattern |
| IP capture | ✓ | `$request->get_remote_addr()` — IPv4 and IPv6 compatible (VARCHAR(45) in schema) |
| Reason validation | ✓ | Whitelist of 5 allowed values with descriptive error |
| Finding existence check | ✓ | `Finding_Repository::get_by_id()` — returns 404 if not found |
| DB insert | ✓ | Direct `$wpdb->insert()` matching the `ec_finding_flags` table columns |
| Response format | ✓ | `WP_REST_Response` with same success/error shape as other controllers |
| Nonce usage | N/A | Flag endpoint is anonymous — no HMAC/nonce needed |
| CSRF protection | N/A | WordPress REST API provides CSRF via `_wpnonce` for authenticated requests; anonymous POST is inherently limited by rate limiting |

## Concerns

- The endpoint uses `$wpdb->insert()` directly (no dedicated repository for flags). This is acceptable since there's no existing `Flag_Repository` and a single-table insert doesn't warrant one yet — but if flag CRUD grows, extracting a repository would be clean.
- No brute-force protection beyond rate limiting. This is consistent with other anonymous endpoints in the codebase.
