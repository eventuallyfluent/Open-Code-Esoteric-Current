# Source Quality + Report Issue Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Block low-quality source domains (Wikipedia, Archive.org) from worker output, and add anonymous "Report issue" flagging on catalogue cards with admin review.

**Architecture:** Two independent additions — (1) a domain filter in the worker's collection phase that drops unwanted sources before they reach the API, and (2) a new `ec_finding_flags` table + REST endpoint + admin review page + card UI button in the plugin.

**Tech Stack:** Node.js 22 (worker), PHP 8.3 / WordPress plugin (API + admin), CSS (card UI)

## Global Constraints

- No authentication on flag endpoint — anonymous, rate-limited only
- Rate limit: 5 flags per IP per 60 seconds (reuse existing `Rate_Limiter`)
- Worker filter happens after collection, before synthesis
- Blocklist: `wikipedia.org`, `archive.org`, `encyclopedia.com`, `britannica.com`
- Matching: hostname === domain OR hostname.endsWith('.' + domain)
- No JS build step — inline JS in rendered block output
- All CSS classes prefixed with `ec-`
- Tables prefixed with `ec_`

---

### Task 1: Worker Source Domain Filtering

**Files:**
- Modify: `worker/src/index.js:65-111`

**Interfaces:**
- Consumes: `briefing.sources` (array of `{title, url?, relevance}`)
- Produces: filtered `briefing.sources` with blocked domains removed

- [ ] **Step 1: Add BLOCKED_DOMAINS constant and filter in processRun**

Insert at the top of `worker/src/index.js`, after the imports:

```js
const BLOCKED_DOMAINS = ['wikipedia.org', 'archive.org', 'encyclopedia.com', 'britannica.com'];
function isBlockedDomain(url) {
  if (!url) return false;
  try {
    const hostname = new URL(url).hostname.toLowerCase();
    return BLOCKED_DOMAINS.some(d => hostname === d || hostname.endsWith('.' + d));
  } catch { return false; }
}
```

Inside `processRun()`, right after the `collect-complete` log (line 77) and before `synthesizer.synthesize(briefing)` (line 79), add:

```js
    const beforeFilter = briefing.sources?.length ?? 0;
    briefing.sources = (briefing.sources ?? []).filter(src => !isBlockedDomain(src.url));
    const filtered = beforeFilter - briefing.sources.length;
    if (filtered > 0) {
      log.info('sources-filtered', { topic: topic.title, before: beforeFilter, after: briefing.sources.length, blocked: filtered });
    }
```

- [ ] **Step 2: Self-review**

Verify: `isBlockedDomain('https://en.wikipedia.org/wiki/Hermeticism')` → `true`
Verify: `isBlockedDomain('https://archive.org/details/grimoire')` → `true`
Verify: `isBlockedDomain('https://global.oup.com/book')` → `false`
Verify: `isBlockedDomain(null)` → `false`
Verify: `isBlockedDomain('not-a-url')` → `false` (caught by try/catch)

- [ ] **Step 3: Commit**

```bash
git add worker/src/index.js
git commit -m "feat: block low-quality source domains in worker collection"
```

---

### Task 2: Database Migration (ec_finding_flags table)

**Files:**
- Modify: `plugin/src/Database/Schema.php:27-29`
- Modify: `plugin/src/Database/Migration.php:52`

- [ ] **Step 1: Add version 1.3.0 to Schema.php**

```php
private static function get_migration_versions(): array {
    return ['1.0.0', '1.0.1', '1.1.0', '1.2.0', '1.3.0'];
}
```

- [ ] **Step 2: Add migrate_1_3_0() to Migration.php**

Insert after `migrate_1_2_0()` (line 52):

```php
public function migrate_1_3_0(): void {
    global $wpdb;
    $this->create_finding_flags_table($wpdb);
}
```

Add the table creation method anywhere in the class (before the private CHARSET constant or after the last table method):

```php
private function create_finding_flags_table($wpdb): void {
    $table = $wpdb->prefix . 'ec_finding_flags';
    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        finding_id BIGINT UNSIGNED NOT NULL,
        reason VARCHAR(50) NOT NULL DEFAULT 'other',
        ip_address VARCHAR(45) NOT NULL DEFAULT '',
        user_agent VARCHAR(512) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        reviewed_at DATETIME DEFAULT NULL,
        reviewed_by VARCHAR(60) DEFAULT NULL,
        action_taken VARCHAR(50) DEFAULT NULL,
        KEY idx_finding_id (finding_id),
        KEY idx_unreviewed (reviewed_at)
    ) " . self::CHARSET;
    $wpdb->query($sql);
}
```

- [ ] **Step 3: Commit**

```bash
git add plugin/src/Database/Schema.php plugin/src/Database/Migration.php
git commit -m "feat: add ec_finding_flags table (v1.3.0)"
```

---

### Task 3: Flag REST Endpoint

**Files:**
- Create: `plugin/src/Api/Flag_Controller.php`
- Modify: `plugin/src/Plugin.php:9-10,34`

- [ ] **Step 1: Create Flag_Controller.php**

```php
<?php
namespace EsotericCurrent\Core\Api;

use EsotericCurrent\Core\Security\Rate_Limiter;

class Flag_Controller {
    public static function register(): void {
        register_rest_route('ec/v1', '/finding/(?P<id>\d+)/flag', [
            'methods' => 'POST',
            'callback' => [self::class, 'handle'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => ['required' => true, 'validate_callback' => function($p) { return is_numeric($p); }],
            ],
        ]);
    }

    public static function handle(\WP_REST_Request $request): \WP_REST_Response {
        $finding_id = (int) $request->get_param('id');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $reason = $request->get_param('reason');

        $allowed_reasons = ['low-quality', 'wrong-category', 'broken-link', 'other'];
        if ($reason && !in_array($reason, $allowed_reasons, true)) {
            return new \WP_REST_Response(['error' => 'Invalid reason'], 400);
        }

        if (!Rate_Limiter::check('flag_' . $ip, 5, 60)) {
            return new \WP_REST_Response(['error' => 'Too many flags. Try again later.'], 429);
        }

        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'ec_finding_flags', [
            'finding_id' => $finding_id,
            'reason' => $reason ?: 'other',
            'ip_address' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => current_time('mysql'),
        ]);

        return new \WP_REST_Response(['status' => 'flagged', 'message' => 'Thank you. We\'ll review this.'], 201);
    }
}
```

- [ ] **Step 2: Register in Plugin.php**

Add the import at the top:
```php
use EsotericCurrent\Core\Api\Flag_Controller;
```

Add the route registration in `initialize()`:
```php
add_action('rest_api_init', [Flag_Controller::class, 'register']);
```

- [ ] **Step 3: Commit**

```bash
git add plugin/src/Api/Flag_Controller.php plugin/src/Plugin.php
git commit -m "feat: add anonymous flag endpoint POST /ec/v1/finding/{id}/flag"
```

---

### Task 4: Flags Admin Review Page

**Files:**
- Create: `plugin/src/Admin/Flags_Page.php`
- Modify: `plugin/src/Admin/Admin_Menu.php:28`

- [ ] **Step 1: Create Flags_Page.php**

```php
<?php
namespace EsotericCurrent\Core\Admin;

class Flags_Page {
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        global $wpdb;

        $flag_table = $wpdb->prefix . 'ec_finding_flags';
        $finding_table = $wpdb->prefix . 'ec_findings';

        if (!empty($_POST['ec_flag_action']) && !empty($_POST['ec_flag_id'])) {
            check_admin_referer('ec_flag_action_' . $_POST['ec_flag_id']);
            $wpdb->update($flag_table, [
                'reviewed_at' => current_time('mysql'),
                'reviewed_by' => wp_get_current_user()->user_login,
                'action_taken' => $_POST['ec_flag_action'],
            ], ['id' => (int)$_POST['ec_flag_id']]);
            echo '<div class="notice notice-success"><p>Flag ' . esc_html($_POST['ec_flag_action']) . '.</p></div>';
        }

        $flags = $wpdb->get_results(
            "SELECT fl.*, f.title, f.status as finding_status
             FROM {$flag_table} fl
             LEFT JOIN {$finding_table} f ON fl.finding_id = f.id
             WHERE fl.reviewed_at IS NULL
             ORDER BY fl.created_at DESC
             LIMIT 100"
        );

        ?>
        <div class="wrap">
            <h1>Flags</h1>
            <?php if (empty($flags)): ?>
                <p>No unreviewed flags.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead><tr><th>Finding</th><th>Reason</th><th>Count</th><th>Latest Flag</th><th>IP</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php
                    $grouped = [];
                    foreach ($flags as $fl) {
                        $gkey = $fl->finding_id;
                        if (!isset($grouped[$gkey])) {
                            $grouped[$gkey] = ['finding_id' => $fl->finding_id, 'title' => $fl->title, 'reasons' => [], 'count' => 0, 'latest' => $fl->created_at, 'ips' => []];
                        }
                        $grouped[$gkey]->reasons[] = $fl->reason;
                        $grouped[$gkey]->count++;
                        $grouped[$gkey]->ips[] = $fl->ip_address;
                        if ($fl->created_at > $grouped[$gkey]->latest) {
                            $grouped[$gkey]->latest = $fl->created_at;
                        }
                    }
                    foreach ($grouped as $g):
                    ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=ec-findings')); ?>">
                                    <?php echo esc_html(mb_substr($g->title ?? 'Finding #' . $g->finding_id, 0, 80)); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html(implode(', ', array_unique($g->reasons))); ?></td>
                            <td><?php echo (int)$g->count; ?></td>
                            <td><?php echo esc_html($g->latest); ?></td>
                            <td><code><?php echo esc_html(implode(', ', array_unique($g->ips))); ?></code></td>
                            <td>
                                <form method="post" style="display:inline">
                                    <?php $first_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$flag_table} WHERE finding_id = %d AND reviewed_at IS NULL ORDER BY id ASC LIMIT 1", $g->finding_id)); ?>
                                    <?php wp_nonce_field('ec_flag_action_' . $first_id); ?>
                                    <input type="hidden" name="ec_flag_id" value="<?php echo (int)$first_id; ?>">
                                    <button type="submit" name="ec_flag_action" value="dismissed" class="button button-small">Dismiss</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
}
```

- [ ] **Step 2: Register in Admin_Menu.php**

Add to the `$pages` array (before the `'ec-settings'` line):
```php
'ec-flags'           => ['Flags', Flags_Page::class],
```

- [ ] **Step 3: Commit**

```bash
git add plugin/src/Admin/Flags_Page.php plugin/src/Admin/Admin_Menu.php
git commit -m "feat: add Flags admin review page"
```

---

### Task 5: Flag Button in Card UI

**Files:**
- Modify: `plugin/src/Blocks/Editorial_Feed_Block.php:47-94` and `96-123`
- Modify: `theme/assets/theme.css` (append card flag styles)

- [ ] **Step 1: Add flag button to grid card rendering**

In `render_grid()`, inside the card footer (after the source link and before `</div>`), add:

```php
<button class="ec-flag-btn" data-finding-id="<?php echo (int)$item->source_id; ?>" type="button" title="Report issue" aria-label="Report issue with this finding">⚑</button>
```

- [ ] **Step 2: Add flag button to list card rendering**

Same change in `render_list()`, inside the `ec-feed-meta` div.

- [ ] **Step 3: Add inline JS + flag UI at the end of render()**

Before `return ob_get_clean();` (end of `render()` method), add the flag UI HTML + JS:

```php
?>
<div id="ec-flag-modal" class="ec-flag-modal" style="display:none">
    <div class="ec-flag-modal-content">
        <p class="ec-flag-modal-title">Report issue</p>
        <p class="ec-flag-modal-desc">Why is this finding problematic?</p>
        <div class="ec-flag-options">
            <button class="ec-flag-option" data-reason="low-quality">Low-quality source</button>
            <button class="ec-flag-option" data-reason="wrong-category">Wrong category</button>
            <button class="ec-flag-option" data-reason="broken-link">Broken link</button>
            <button class="ec-flag-option" data-reason="other">Other</button>
        </div>
        <button class="ec-flag-cancel">Cancel</button>
    </div>
</div>
<script>
(function(){
    var modal = document.getElementById('ec-flag-modal');
    var currentId = null;
    document.querySelectorAll('.ec-flag-btn').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.stopPropagation();
            currentId = this.getAttribute('data-finding-id');
            modal.style.display = 'flex';
        });
    });
    document.querySelectorAll('.ec-flag-option').forEach(function(opt){
        opt.addEventListener('click', function(){
            var reason = this.getAttribute('data-reason');
            if (!currentId) return;
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo esc_url_raw(rest_url('ec/v1/finding/')); ?>' + currentId + '/flag');
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onload = function(){
                modal.style.display = 'none';
                if (xhr.status === 201) {
                    alert('Thank you. We\'ll review this.');
                } else {
                    alert('Could not submit flag. Please try again later.');
                }
            };
            xhr.onerror = function(){
                modal.style.display = 'none';
                alert('Could not submit flag. Please try again later.');
            };
            xhr.send(JSON.stringify({reason: reason}));
        });
    });
    document.querySelector('.ec-flag-cancel').addEventListener('click', function(){
        modal.style.display = 'none';
    });
    modal.addEventListener('click', function(e){
        if (e.target === modal) modal.style.display = 'none';
    });
})();
</script>
<?php
```

- [ ] **Step 4: Add CSS to theme.css**

Append to `theme/assets/theme.css`:

```css
.ec-flag-btn {
  background: none;
  border: none;
  color: var(--ec-muted);
  cursor: pointer;
  font-size: 14px;
  line-height: 1;
  padding: 2px 4px;
  opacity: 0.4;
  transition: opacity var(--ec-transition);
}
.ec-flag-btn:hover {
  opacity: 1;
  color: var(--ec-gold);
}
.ec-flag-modal {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(0,0,0,0.7);
  display: flex;
  align-items: center;
  justify-content: center;
}
.ec-flag-modal-content {
  background: var(--ec-panel);
  border: 1px solid var(--ec-border);
  border-radius: 8px;
  padding: 24px;
  max-width: 340px;
  width: 90%;
  text-align: center;
}
.ec-flag-modal-title {
  font-size: 1rem;
  margin: 0 0 4px;
}
.ec-flag-modal-desc {
  font-size: 0.8125rem;
  color: var(--ec-muted);
  margin: 0 0 16px;
}
.ec-flag-options {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 12px;
}
.ec-flag-option {
  padding: 10px;
  border: 1px solid var(--ec-border);
  border-radius: 6px;
  background: var(--ec-bg);
  color: var(--ec-text);
  cursor: pointer;
  font-size: 0.875rem;
  transition: border-color var(--ec-transition);
}
.ec-flag-option:hover {
  border-color: var(--ec-gold);
}
.ec-flag-cancel {
  background: none;
  border: none;
  color: var(--ec-muted);
  cursor: pointer;
  font-size: 0.75rem;
}
```

- [ ] **Step 5: Commit**

```bash
git add plugin/src/Blocks/Editorial_Feed_Block.php theme/assets/theme.css
git commit -m "feat: add report issue button to catalogue cards"
```

---

## Self-Review Checklist

- **Spec coverage:** Source blocking (Task 1), Flag table (Task 2), Flag endpoint (Task 3), Admin review (Task 4), Card UI (Task 5) — all covered.
- **Placeholders:** No TBD, TODO, or incomplete code in any task.
- **Type consistency:** `Rate_Limiter::check('flag_' . $ip, 5, 60)` matches existing `Rate_Limiter::check($key, $max_attempts, $window_seconds)` signature.
- **No placeholders in steps** — all code complete, all commands exact.
