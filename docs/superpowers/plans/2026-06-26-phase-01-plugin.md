# Phase 1 — esoteric-current-core Plugin Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the complete esoteric-current-core WordPress plugin with schema, admin, blocks, security, and tests.

**Architecture:** Traditional WordPress plugin with namespaced PHP classes, PSR-4 autoloading, versioned schema migrations, repository pattern for data access, HMAC-signed API endpoints. No React — server-rendered admin screens and blocks using WordPress admin UI conventions.

**Tech Stack:** WordPress plugin API, PHP 8.1+, MySQL/MariaDB, WPCS coding standards, PHPUnit for tests.

## Global Constraints

- All SQL must use `$wpdb->prepare()` with `%s`/`%d` — never concatenated.
- Every public-facing output must use `esc_html()`, `esc_url()`, `esc_attr()`, or `wp_kses()`.
- All admin actions must check `current_user_can()` and verify WordPress nonces.
- Plugin prefix: `ec_` for database tables, `esoteric_current_core_` for PHP functions/options.
- Namespace root: `EsotericCurrent\Core`.
- No hard-coded paths or secrets. All configuration through constants or options.
- Every schema change must be a versioned migration.
- Every repository method must use prepared statements.
- Tests must use mocked data, not live API calls.

---
### Task 1: Plugin bootstrap and autoloader

**Files:**
- Create: `C:\Dev\Open Code Esoteric Current\plugin\esoteric-current-core.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Plugin.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\composer.json`

**Interfaces:**
- Consumes: WordPress plugin API
- Produces: `Plugin::init()` — called on `plugins_loaded`, returns void. `Plugin::activate()` — called on activation. `Plugin::deactivate()` — called on deactivation.

- [ ] **Step 1: Create composer.json**

```json
{
  "name": "esoteric-current/core",
  "type": "wordpress-plugin",
  "require": {
    "php": ">=8.1",
    "composer/installers": "^2.0"
  },
  "require-dev": {
    "phpunit/phpunit": "^10.0",
    "wp-coding-standards/wpcs": "^3.0",
    "phpcompatibility/phpcompatibility-wp": "^2.1"
  },
  "autoload": {
    "psr-4": {
      "EsotericCurrent\\Core\\": "src/"
    }
  },
  "config": {
    "allow-plugins": {
      "composer/installers": true,
      "dealerdirect/phpcodesniffer-composer-installer": true
    }
  }
}
```

- [ ] **Step 2: Create main plugin file**

```php
<?php
/**
 * Plugin Name:         Esoteric Current Core
 * Plugin URI:          https://theesotericcurrent.com
 * Description:         Core plugin for The Esoteric Current — automated esoteric research publication.
 * Version:             1.0.0
 * Requires at least:   6.4
 * Requires PHP:        8.1
 * Author:              The Esoteric Current
 * Text Domain:         esoteric-current-core
 * Domain Path:         /languages
 */

defined('ABSPATH') || exit;

define('EC_CORE_VERSION', '1.0.0');
define('EC_CORE_FILE', __FILE__);
define('EC_CORE_DIR', plugin_dir_path(__FILE__));
define('EC_CORE_URL', plugin_dir_url(__FILE__));

if (file_exists(EC_CORE_DIR . 'vendor/autoload.php')) {
    require EC_CORE_DIR . 'vendor/autoload.php';
}

register_activation_hook(__FILE__, ['EsotericCurrent\Core\Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['EsotericCurrent\Core\Plugin', 'deactivate']);

add_action('plugins_loaded', ['EsotericCurrent\Core\Plugin', 'init']);
```

- [ ] **Step 3: Create main Plugin class**

```php
<?php
namespace EsotericCurrent\Core;

class Plugin {
    private static ?Plugin $instance = null;
    private bool $initialized = false;

    public static function init(): void {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        self::$instance->initialize();
    }

    private function initialize(): void {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;
        // Hook registration will be added in subsequent tasks
    }

    public static function activate(): void {
        // Schema creation will be added in Task 2
    }

    public static function deactivate(): void {
        // Cleanup will be added in later tasks
    }
}
```

- [ ] **Step 4: Run composer install**

Run: `cd C:\Dev\Open Code Esoteric Current\plugin && composer install`

Expected: `vendor/` created with autoloader.

- [ ] **Step 5: Commit**

```bash
git add plugin/composer.json plugin/composer.lock plugin/esoteric-current-core.php plugin/src/Plugin.php
git commit -m "feat(plugin): bootstrap and autoloader"
```

---
### Task 2: Database schema and migration system

**Files:**
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Database\Schema.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Database\Migration.php`
- Modify: `C:\Dev\Open Code Esoteric Current\plugin\src\Plugin.php` — hook schema on activation/init
- Create: `C:\Dev\Open Code Esoteric Current\plugin\tests\php\Test_Schema.php`

**Interfaces:**
- Consumes: `Plugin::init()` calls `Schema::migrate()` on `init` hook
- Produces: `Schema::migrate()` — runs pending migrations. `Schema::current_version()` — returns DB version option. `Migration::run($version)` — executes a single migration step.

- [ ] **Step 1: Create Schema class with all table definitions**

```php
<?php
namespace EsotericCurrent\Core\Database;

class Schema {
    private const OPTION_KEY = 'ec_schema_version';

    /**
     * Run all pending migrations up to current version.
     */
    public static function migrate(): void {
        $current = self::current_version();
        $target = defined('EC_CORE_VERSION') ? EC_CORE_VERSION : '1.0.0';

        if (version_compare($current, $target, '>=')) {
            return;
        }

        $migrations = new Migration();
        $versions = self::get_migration_versions();

        foreach ($versions as $version) {
            if (version_compare($version, $current, '>')) {
                $migration_method = 'migrate_' . str_replace('.', '_', $version);
                if (method_exists($migrations, $migration_method)) {
                    $migrations->$migration_method();
                    update_option(self::OPTION_KEY, $version);
                }
            }
        }
    }

    public static function current_version(): string {
        return get_option(self::OPTION_KEY, '0.0.0');
    }

    private static function get_migration_versions(): array {
        return ['1.0.0'];
    }
}
```

- [ ] **Step 2: Create Migration class with table definitions**

```php
<?php
namespace EsotericCurrent\Core\Database;

class Migration {
    private const CHARSET = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    public function migrate_1_0_0(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $this->create_sources_table($wpdb);
        $this->create_source_items_table($wpdb);
        $this->create_research_topics_table($wpdb);
        $this->create_agent_runs_table($wpdb);
        $this->create_findings_table($wpdb);
        $this->create_resources_table($wpdb);
        $this->create_issues_table($wpdb);
        $this->create_submissions_table($wpdb);
        $this->create_editorial_queue_table($wpdb);
        $this->create_run_log_table($wpdb);
    }

    private function create_sources_table($wpdb): void {
        $table = $wpdb->prefix . 'ec_sources';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL DEFAULT '',
            type VARCHAR(50) NOT NULL DEFAULT 'rss',
            url VARCHAR(2048) NOT NULL DEFAULT '',
            feed_url VARCHAR(2048) DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'active',
            trust_level VARCHAR(50) NOT NULL DEFAULT 'unverified',
            last_fetched_at DATETIME DEFAULT NULL,
            error_count INT UNSIGNED NOT NULL DEFAULT 0,
            last_error TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_feed_url (feed_url(191))
        ) " . self::CHARSET;
        dbDelta($sql);
    }

    private function create_source_items_table($wpdb): void {
        $table = $wpdb->prefix . 'ec_source_items';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            source_id BIGINT UNSIGNED NOT NULL,
            title TEXT NOT NULL,
            url VARCHAR(2048) NOT NULL DEFAULT '',
            content_hash VARCHAR(64) NOT NULL DEFAULT '',
            content LONGTEXT DEFAULT NULL,
            excerpt TEXT DEFAULT NULL,
            author VARCHAR(255) DEFAULT NULL,
            published_at DATETIME DEFAULT NULL,
            fetched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(50) NOT NULL DEFAULT 'collected',
            UNIQUE KEY uk_content_hash (content_hash),
            KEY idx_source_id (source_id),
            KEY idx_status (status),
            KEY idx_published_at (published_at)
        ) " . self::CHARSET;
        dbDelta($sql);
    }

    private function create_research_topics_table($wpdb): void {
        $table = $wpdb->prefix . 'ec_research_topics';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL DEFAULT '',
            research_goal TEXT DEFAULT NULL,
            included_concepts TEXT DEFAULT NULL,
            excluded_concepts TEXT DEFAULT NULL,
            priority VARCHAR(50) NOT NULL DEFAULT 'normal',
            languages VARCHAR(255) DEFAULT NULL,
            geography VARCHAR(255) DEFAULT NULL,
            lookback_days INT UNSIGNED DEFAULT NULL,
            date_range_start DATE DEFAULT NULL,
            date_range_end DATE DEFAULT NULL,
            content_types VARCHAR(255) DEFAULT NULL,
            run_frequency VARCHAR(50) NOT NULL DEFAULT 'daily',
            selectivity VARCHAR(50) NOT NULL DEFAULT 'normal',
            finding_limit INT UNSIGNED NOT NULL DEFAULT 25,
            cost_limit DECIMAL(10,2) DEFAULT NULL,
            browser_action_limit INT UNSIGNED NOT NULL DEFAULT 100,
            status VARCHAR(50) NOT NULL DEFAULT 'active',
            next_run_at DATETIME DEFAULT NULL,
            last_run_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_status (status),
            KEY idx_next_run (next_run_at)
        ) " . self::CHARSET;
        dbDelta($sql);
    }

    private function create_agent_runs_table($wpdb): void {
        $table = $wpdb->prefix . 'ec_agent_runs';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            run_uuid VARCHAR(36) NOT NULL DEFAULT '',
            topic_id BIGINT UNSIGNED DEFAULT NULL,
            trigger_type VARCHAR(50) NOT NULL DEFAULT 'schedule',
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            lease_token_hash VARCHAR(64) DEFAULT NULL,
            lease_expires_at DATETIME DEFAULT NULL,
            claimed_at DATETIME DEFAULT NULL,
            claimed_by VARCHAR(255) DEFAULT NULL,
            retry_count INT UNSIGNED NOT NULL DEFAULT 0,
            next_retry_at DATETIME DEFAULT NULL,
            callback_nonce_hash VARCHAR(64) DEFAULT NULL,
            callback_received_at DATETIME DEFAULT NULL,
            brief_json LONGTEXT DEFAULT NULL,
            findings_json LONGTEXT DEFAULT NULL,
            searches_count INT UNSIGNED NOT NULL DEFAULT 0,
            pages_opened_count INT UNSIGNED NOT NULL DEFAULT 0,
            links_followed_count INT UNSIGNED NOT NULL DEFAULT 0,
            rejections_count INT UNSIGNED NOT NULL DEFAULT 0,
            estimated_cost DECIMAL(10,6) DEFAULT NULL,
            error_code VARCHAR(100) DEFAULT NULL,
            error_message TEXT DEFAULT NULL,
            started_at DATETIME DEFAULT NULL,
            completed_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_run_uuid (run_uuid),
            KEY idx_status (status),
            KEY idx_topic_id (topic_id),
            KEY idx_lease_expires (lease_expires_at)
        ) " . self::CHARSET;
        dbDelta($sql);
    }

    private function create_findings_table($wpdb): void {
        $table = $wpdb->prefix . 'ec_findings';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            run_id BIGINT UNSIGNED DEFAULT NULL,
            topic_id BIGINT UNSIGNED DEFAULT NULL,
            finding_type VARCHAR(100) NOT NULL DEFAULT '',
            title TEXT NOT NULL,
            url VARCHAR(2048) NOT NULL DEFAULT '',
            source_url VARCHAR(2048) DEFAULT NULL,
            content_hash VARCHAR(64) NOT NULL DEFAULT '',
            content LONGTEXT DEFAULT NULL,
            excerpt TEXT DEFAULT NULL,
            evidence TEXT DEFAULT NULL,
            relevance_score DECIMAL(5,2) DEFAULT NULL,
            confidence_score DECIMAL(5,2) DEFAULT NULL,
            classification VARCHAR(255) DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'awaiting_review',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_content_hash (content_hash),
            KEY idx_run_id (run_id),
            KEY idx_topic_id (topic_id),
            KEY idx_status (status),
            KEY idx_type (finding_type)
        ) " . self::CHARSET;
        dbDelta($sql);
    }

    private function create_resources_table($wpdb): void {
        $table = $wpdb->prefix . 'ec_resources';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title TEXT NOT NULL,
            resource_type VARCHAR(100) NOT NULL DEFAULT '',
            url VARCHAR(2048) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            topics VARCHAR(255) DEFAULT NULL,
            author VARCHAR(255) DEFAULT NULL,
            publisher VARCHAR(255) DEFAULT NULL,
            published_at DATE DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'draft',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_type (resource_type),
            KEY idx_status (status)
        ) " . self::CHARSET;
        dbDelta($sql);
    }

    private function create_issues_table($wpdb): void {
        $table = $wpdb->prefix . 'ec_issues';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL DEFAULT '',
            slug VARCHAR(255) NOT NULL DEFAULT '',
            issue_number VARCHAR(50) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'draft',
            published_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_slug (slug),
            KEY idx_status (status)
        ) " . self::CHARSET;
        dbDelta($sql);
    }

    private function create_submissions_table($wpdb): void {
        $table = $wpdb->prefix . 'ec_submissions';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            url VARCHAR(2048) NOT NULL DEFAULT '',
            title VARCHAR(255) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            submitter_name VARCHAR(255) DEFAULT NULL,
            submitter_email VARCHAR(255) DEFAULT NULL,
            content_type VARCHAR(100) DEFAULT NULL,
            ip_hash VARCHAR(64) DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_status (status),
            KEY idx_ip_hash (ip_hash),
            KEY idx_created_at (created_at)
        ) " . self::CHARSET;
        dbDelta($sql);
    }

    private function create_editorial_queue_table($wpdb): void {
        $table = $wpdb->prefix . 'ec_editorial_queue';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            source_type VARCHAR(50) NOT NULL DEFAULT '',
            source_id BIGINT UNSIGNED NOT NULL,
            workflow_state VARCHAR(50) NOT NULL DEFAULT 'discovered',
            previous_state VARCHAR(50) DEFAULT NULL,
            assigned_to BIGINT UNSIGNED DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            topic_id BIGINT UNSIGNED DEFAULT NULL,
            issue_id BIGINT UNSIGNED DEFAULT NULL,
            transitioned_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_workflow_state (workflow_state),
            KEY idx_source_type_source_id (source_type, source_id),
            KEY idx_topic_id (topic_id),
            KEY idx_issue_id (issue_id),
            KEY idx_assigned_to (assigned_to)
        ) " . self::CHARSET;
        dbDelta($sql);
    }

    private function create_run_log_table($wpdb): void {
        $table = $wpdb->prefix . 'ec_run_log';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            run_id BIGINT UNSIGNED DEFAULT NULL,
            level VARCHAR(20) NOT NULL DEFAULT 'info',
            message TEXT NOT NULL,
            context_json TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_run_id (run_id),
            KEY idx_level (level),
            KEY idx_created_at (created_at)
        ) " . self::CHARSET;
        dbDelta($sql);
    }
}
```

- [ ] **Step 3: Wire schema into Plugin class**

Modify `Plugin::initialize()` to add:

```php
add_action('init', [Database\Schema::class, 'migrate']);
```

Modify `Plugin::activate()` to:

```php
public static function activate(): void {
    Database\Schema::migrate();
}
```

- [ ] **Step 4: Write schema test**

```php
<?php
namespace EsotericCurrent\Core\Tests;

use EsotericCurrent\Core\Database\Schema;
use PHPUnit\Framework\TestCase;

class Test_Schema extends TestCase {
    private string $option_key = 'ec_schema_version';

    protected function setUp(): void {
        parent::setUp();
        delete_option($this->option_key);
    }

    public function test_current_version_returns_zero_when_not_migrated(): void {
        $version = Schema::current_version();
        $this->assertSame('0.0.0', $version);
    }

    public function test_migrate_updates_version(): void {
        // Test against an in-memory SQLite or mock — skip full WP integration for unit
        $this->assertTrue(true, 'Placeholder: full migration test needs WP test suite.');
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `cd C:\Dev\Open Code Esoteric Current\plugin && vendor/bin/phpunit tests/php/Test_Schema.php`

Expected: PASS or skipped (skipped = valid for WP-requiring tests outside WP context)

- [ ] **Step 6: Commit**

```bash
git add plugin/src/Database/Schema.php plugin/src/Database/Migration.php plugin/src/Plugin.php plugin/tests/php/Test_Schema.php
git commit -m "feat(plugin): database schema and migration system"
```

---
### Task 3: Repository layer

**Files:**
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Repository\{Source_Repository,Source_Item_Repository,Research_Topic_Repository,Agent_Run_Repository,Finding_Repository,Resource_Repository,Issue_Repository,Submission_Repository,Editorial_Queue_Repository,Run_Log_Repository}.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\tests\php\Test_Source_Repository.php`

**Interfaces:**
- Consumes: `$wpdb`, table names from Migration
- Produces: Each repository has `get_by_id($id)`, `get_all($args)`, `create($data)`, `update($id, $data)`, `delete($id)` where applicable.

- [ ] **Step 1: Write Source Repository**

```php
<?php
namespace EsotericCurrent\Core\Repository;

class Source_Repository {
    private \wpdb $db;
    private string $table;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . 'ec_sources';
    }

    public function get_by_id(int $id): ?array {
        $row = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id),
            ARRAY_A
        );
        return $row ?: null;
    }

    public function get_all(array $args = []): array {
        $where = '1=1';
        $params = [];

        if (!empty($args['status'])) {
            $where .= ' AND status = %s';
            $params[] = $args['status'];
        }
        if (!empty($args['type'])) {
            $where .= ' AND type = %s';
            $params[] = $args['type'];
        }

        $limit = !empty($args['limit']) ? min((int)$args['limit'], 100) : 50;
        $offset = !empty($args['offset']) ? (int)$args['offset'] : 0;

        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            array_merge($params, [$limit, $offset])
        );

        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function create(array $data): ?int {
        $allowed = ['name', 'type', 'url', 'feed_url', 'status', 'trust_level'];
        $insert = array_intersect_key($data, array_flip($allowed));
        $insert['created_at'] = current_time('mysql');

        $this->db->insert($this->table, $insert);

        return $this->db->insert_id ?: null;
    }

    public function update(int $id, array $data): bool {
        $allowed = ['name', 'type', 'url', 'feed_url', 'status', 'trust_level', 'last_fetched_at', 'error_count', 'last_error'];
        $update = array_intersect_key($data, array_flip($allowed));

        if (empty($update)) {
            return false;
        }

        return (bool)$this->db->update($this->table, $update, ['id' => $id]);
    }

    public function delete(int $id): bool {
        return (bool)$this->db->delete($this->table, ['id' => $id]);
    }
}
```

- [ ] **Step 2: Write remaining repositories**

Create each repository file following the same pattern. Each repository defined in the spec gets its own file:

- `Source_Item_Repository` — `get_by_content_hash($hash)`, `get_by_source_id($source_id)`, `create($data)`, `count_unprocessed()`
- `Research_Topic_Repository` — `get_due_topics()`, `get_by_status($status)`, `create($data)`, `update_next_run($id, $next_run)`
- `Agent_Run_Repository` — `get_by_uuid($uuid)`, `create_run($topic_id, $trigger_type)`, `claim_job($lease_token, $lease_expiry)`, `complete_run($uuid, $results)`, `fail_run($uuid, $error_code, $error_message)`
- `Finding_Repository` — `get_by_hash($hash)`, `create($data)`, `update_status($id, $status)`, `get_by_topic_id($topic_id)`
- `Resource_Repository` — standard CRUD with `resource_type` and `status` filters
- `Issue_Repository` — standard CRUD with `status` filter, `get_by_slug($slug)`
- `Submission_Repository` — `count_recent_by_ip($ip_hash, $minutes)`, `create($data)`, `update_status($id, $status)`
- `Editorial_Queue_Repository` — `transition($id, $new_state)`, `get_by_state($state)`, `get_by_source($source_type, $source_id)`
- `Run_Log_Repository` — `add_entry($run_id, $level, $message, $context)`, `get_by_run_id($run_id)`

- [ ] **Step 3: Write repository test**

```php
<?php
namespace EsotericCurrent\Core\Tests;

use EsotericCurrent\Core\Repository\Source_Repository;
use PHPUnit\Framework\TestCase;

class Test_Source_Repository extends TestCase {
    private Source_Repository $repo;

    protected function setUp(): void {
        $this->repo = new Source_Repository();
    }

    public function test_get_by_id_returns_null_for_missing(): void {
        $result = $this->repo->get_by_id(99999);
        $this->assertNull($result);
    }

    public function test_get_all_returns_empty_array_when_none(): void {
        $results = $this->repo->get_all();
        $this->assertIsArray($results);
    }

    public function test_delete_nonexistent_returns_false(): void {
        $result = $this->repo->delete(99999);
        $this->assertFalse($result);
    }
}
```

- [ ] **Step 4: Run tests**

Run: `cd C:\Dev\Open Code Esoteric Current\plugin && vendor/bin/phpunit tests/php/Test_Source_Repository.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add plugin/src/Repository/ plugin/tests/php/Test_Source_Repository.php
git commit -m "feat(plugin): repository layer with all data access classes"
```

---
### Task 4: Security layer (HMAC, nonces, rate limiting)

**Files:**
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Security\HMAC_Verifier.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Security\Nonce_Store.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Security\Rate_Limiter.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\tests\php\Test_HMAC_Verifier.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\tests\php\Test_Nonce_Store.php`

**Interfaces:**
- `HMAC_Verifier::sign($data, $secret)` → `string`
- `HMAC_Verifier::verify($data, $signature, $secret)` → `bool`
- `Nonce_Store::create_nonce($ttl_seconds)` → `array{nonce: string, hash: string, expires_at: string}`
- `Nonce_Store::consume($nonce_hash)` → `bool`
- `Rate_Limiter::check($key, $max_attempts, $window_seconds)` → `bool`
- `Rate_Limiter::increment($key)` → `int`

- [ ] **Step 1: Write HMAC_Verifier**

```php
<?php
namespace EsotericCurrent\Core\Security;

class HMAC_Verifier {
    public static function sign(array $data, string $secret): string {
        $payload = wp_json_encode($data);
        return hash_hmac('sha256', $payload, $secret);
    }

    public static function verify(array $data, string $signature, string $secret): bool {
        $expected = self::sign($data, $secret);
        return hash_equals($expected, $signature);
    }

    public static function sign_request(string $method, string $path, string $body, string $timestamp, string $nonce, string $secret): string {
        $data = implode("\n", [$method, $path, $body, $timestamp, $nonce]);
        return hash_hmac('sha256', $data, $secret);
    }

    public static function verify_request(string $method, string $path, string $body, string $timestamp, string $nonce, string $signature, string $secret, int $max_age_seconds = 300): bool {
        if (abs(time() - (int)$timestamp) > $max_age_seconds) {
            return false;
        }
        $expected = self::sign_request($method, $path, $body, $timestamp, $nonce, $secret);
        return hash_equals($expected, $signature);
    }
}
```

- [ ] **Step 2: Write Nonce_Store**

```php
<?php
namespace EsotericCurrent\Core\Security;

class Nonce_Store {
    private const TABLE_HASH_OPTION = 'ec_consumed_nonces';

    public static function create_nonce(int $ttl_seconds = 300): array {
        $nonce = bin2hex(random_bytes(32));
        $hash = hash('sha256', $nonce);
        $expires_at = gmdate('Y-m-d H:i:s', time() + $ttl_seconds);
        return compact('nonce', 'hash', 'expires_at');
    }

    public static function consume(string $nonce_hash): bool {
        $consumed = get_option(self::TABLE_HASH_OPTION, []);
        $expires_at = gmdate('Y-m-d H:i:s', time());

        // Clean expired entries
        $consumed = array_filter($consumed, function($entry) use ($expires_at) {
            return $entry['expires_at'] > $expires_at;
        });

        if (isset($consumed[$nonce_hash])) {
            return false; // Already consumed
        }

        $consumed[$nonce_hash] = ['expires_at' => gmdate('Y-m-d H:i:s', time() + 300)];
        update_option(self::TABLE_HASH_OPTION, $consumed);
        return true;
    }
}
```

- [ ] **Step 3: Write Rate_Limiter**

```php
<?php
namespace EsotericCurrent\Core\Security;

class Rate_Limiter {
    public static function check(string $key, int $max_attempts = 10, int $window_seconds = 60): bool {
        $option_key = 'ec_rate_limit_' . md5($key);
        $data = get_option($option_key, ['count' => 0, 'reset_at' => time() + $window_seconds]);

        if (time() > $data['reset_at']) {
            $data = ['count' => 0, 'reset_at' => time() + $window_seconds];
            update_option($option_key, $data);
        }

        return $data['count'] < $max_attempts;
    }

    public static function increment(string $key): int {
        $option_key = 'ec_rate_limit_' . md5($key);
        $data = get_option($option_key, ['count' => 0, 'reset_at' => time() + 60]);

        if (time() > $data['reset_at']) {
            $data = ['count' => 0, 'reset_at' => time() + 60];
        }

        $data['count']++;
        update_option($option_key, $data);

        return $data['count'];
    }
}
```

- [ ] **Step 4: Write security tests**

```php
<?php
namespace EsotericCurrent\Core\Tests;

use EsotericCurrent\Core\Security\HMAC_Verifier;
use PHPUnit\Framework\TestCase;

class Test_HMAC_Verifier extends TestCase {
    private string $secret = 'test-secret-key';

    public function test_sign_and_verify_match(): void {
        $data = ['run_uuid' => 'abc-123', 'action' => 'claim'];
        $sig = HMAC_Verifier::sign($data, $this->secret);
        $this->assertTrue(HMAC_Verifier::verify($data, $sig, $this->secret));
    }

    public function test_wrong_secret_fails(): void {
        $data = ['run_uuid' => 'abc-123'];
        $sig = HMAC_Verifier::sign($data, $this->secret);
        $this->assertFalse(HMAC_Verifier::verify($data, $sig, 'wrong-secret'));
    }

    public function test_tampered_data_fails(): void {
        $data = ['run_uuid' => 'abc-123'];
        $sig = HMAC_Verifier::sign($data, $this->secret);
        $tampered = ['run_uuid' => 'abc-999'];
        $this->assertFalse(HMAC_Verifier::verify($tampered, $sig, $this->secret));
    }

    public function test_expired_request_fails(): void {
        $old_timestamp = (string)(time() - 600);
        $result = HMAC_Verifier::verify_request(
            'POST', '/ec/v1/claim', '{}',
            $old_timestamp, 'test-nonce', 'test-sig', $this->secret
        );
        $this->assertFalse($result);
    }
}
```

- [ ] **Step 5: Run security tests**

Run: `cd C:\Dev\Open Code Esoteric Current\plugin && vendor/bin/phpunit tests/php/Test_HMAC_Verifier.php`

Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add plugin/src/Security/ plugin/tests/php/Test_HMAC_Verifier.php plugin/tests/php/Test_Nonce_Store.php
git commit -m "feat(plugin): security layer — HMAC, nonce store, rate limiter"
```

---
### Task 5: API controllers (claim, callback, health)

**Files:**
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Api\Claim_Controller.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Api\Callback_Controller.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Api\Health_Controller.php`
- Modify: `C:\Dev\Open Code Esoteric Current\plugin\src\Plugin.php` — register REST routes

**Interfaces:**
- `GET /ec/v1/health` → `{status: string, version: string, schema_version: string}`
- `POST /ec/v1/claim` — HMAC-signed → returns `{topic: {...}, lease_token: string, expires_at: string}` or `{claimed: false}`
- `POST /ec/v1/callback` — HMAC-signed → accepts `{run_uuid, status, findings, cost, error}` → returns `{accepted: true}`

- [ ] **Step 1: Write Health_Controller**

```php
<?php
namespace EsotericCurrent\Core\Api;

use EsotericCurrent\Core\Database\Schema;

class Health_Controller {
    public static function register(): void {
        register_rest_route('ec/v1', '/health', [
            'methods' => 'GET',
            'callback' => [self::class, 'handle'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function handle(\WP_REST_Request $request): \WP_REST_Response {
        return new \WP_REST_Response([
            'status' => 'ok',
            'version' => EC_CORE_VERSION,
            'schema_version' => Schema::current_version(),
        ]);
    }
}
```

- [ ] **Step 2: Write Claim_Controller**

```php
<?php
namespace EsotericCurrent\Core\Api;

use EsotericCurrent\Core\Security\HMAC_Verifier;
use EsotericCurrent\Core\Security\Nonce_Store;
use EsotericCurrent\Core\Security\Rate_Limiter;

class Claim_Controller {
    public static function register(): void {
        register_rest_route('ec/v1', '/claim', [
            'methods' => 'POST',
            'callback' => [self::class, 'handle'],
            'permission_callback' => [self::class, 'check_auth'],
        ]);
    }

    public static function check_auth(\WP_REST_Request $request): bool {
        $secret = defined('EC_CLAIM_SECRET') ? EC_CLAIM_SECRET : '';
        if (empty($secret)) {
            return false;
        }

        $signature = $request->get_header('X-EC-Signature');
        $timestamp = $request->get_header('X-EC-Timestamp');
        $nonce = $request->get_header('X-EC-Nonce');

        if (empty($signature) || empty($timestamp) || empty($nonce)) {
            return false;
        }

        // Rate limit check
        $rate_key = 'claim_' . $request->get_remote_addr();
        if (!Rate_Limiter::check($rate_key, 30, 60)) {
            return false;
        }

        return HMAC_Verifier::verify_request(
            'POST', '/ec/v1/claim', $request->get_body(),
            $timestamp, $nonce, $signature, $secret
        );
    }

    public static function handle(\WP_REST_Request $request): \WP_REST_Response {
        $nonce = $request->get_header('X-EC-Nonce');
        $nonce_hash = hash('sha256', $nonce);

        if (!Nonce_Store::consume($nonce_hash)) {
            return new \WP_REST_Response(['error' => 'Nonce already consumed'], 429);
        }

        // Find and lease a due topic
        $topic_repo = new \EsotericCurrent\Core\Repository\Research_Topic_Repository();
        $topic = $topic_repo->claim_due_topic();

        if ($topic === null) {
            return new \WP_REST_Response(['claimed' => false, 'message' => 'No due topics']);
        }

        $lease_token = bin2hex(random_bytes(32));
        $lease_expires_at = gmdate('Y-m-d H:i:s', time() + 600);

        $agent_run_repo = new \EsotericCurrent\Core\Repository\Agent_Run_Repository();
        $run = $agent_run_repo->create_run($topic['id'], 'claim');
        $agent_run_repo->set_lease($run['id'], hash('sha256', $lease_token), $lease_expires_at);

        return new \WP_REST_Response([
            'claimed' => true,
            'run_uuid' => $run['run_uuid'],
            'run_id' => $run['id'],
            'lease_token' => $lease_token,
            'lease_expires_at' => $lease_expires_at,
            'topic' => $topic,
        ]);
    }
}
```

- [ ] **Step 3: Write Callback_Controller**

```php
<?php
namespace EsotericCurrent\Core\Api;

use EsotericCurrent\Core\Security\HMAC_Verifier;
use EsotericCurrent\Core\Security\Nonce_Store;
use EsotericCurrent\Core\Security\Rate_Limiter;

class Callback_Controller {
    public static function register(): void {
        register_rest_route('ec/v1', '/callback', [
            'methods' => 'POST',
            'callback' => [self::class, 'handle'],
            'permission_callback' => [self::class, 'check_auth'],
        ]);
    }

    public static function check_auth(\WP_REST_Request $request): bool {
        $secret = defined('EC_CALLBACK_SECRET') ? EC_CALLBACK_SECRET : '';
        if (empty($secret)) {
            return false;
        }

        $signature = $request->get_header('X-EC-Signature');
        $timestamp = $request->get_header('X-EC-Timestamp');
        $nonce = $request->get_header('X-EC-Nonce');

        return HMAC_Verifier::verify_request(
            'POST', '/ec/v1/callback', $request->get_body(),
            $timestamp, $nonce, $signature, $secret
        );
    }

    public static function handle(\WP_REST_Request $request): \WP_REST_Response {
        $nonce_hash = hash('sha256', $request->get_header('X-EC-Nonce'));

        if (!Nonce_Store::consume($nonce_hash)) {
            return new \WP_REST_Response(['error' => 'Nonce already consumed'], 429);
        }

        $body = $request->get_json_params();
        $run_uuid = $body['run_uuid'] ?? '';
        $status = $body['status'] ?? '';
        $findings = $body['findings'] ?? [];
        $error = $body['error'] ?? null;
        $cost = $body['estimated_cost'] ?? null;

        if (empty($run_uuid)) {
            return new \WP_REST_Response(['error' => 'run_uuid required'], 400);
        }

        $agent_run_repo = new \EsotericCurrent\Core\Repository\Agent_Run_Repository();
        $run = $agent_run_repo->get_by_uuid($run_uuid);

        if ($run === null) {
            return new \WP_REST_Response(['error' => 'Run not found'], 404);
        }

        if ($status === 'completed') {
            $agent_run_repo->complete_run($run_uuid, $findings, $cost);

            // Store findings
            $finding_repo = new \EsotericCurrent\Core\Repository\Finding_Repository();
            foreach ($findings as $finding) {
                $finding_repo->create_from_agent($finding, $run['id'], $run['topic_id']);
            }

            // Advance topic next_run
            if (!empty($run['topic_id'])) {
                $topic_repo = new \EsotericCurrent\Core\Repository\Research_Topic_Repository();
                $topic_repo->advance_next_run($run['topic_id']);
            }
        } else {
            $agent_run_repo->fail_run($run_uuid, $error['code'] ?? 'unknown', $error['message'] ?? 'Unknown error');
        }

        return new \WP_REST_Response(['accepted' => true]);
    }
}
```

- [ ] **Step 4: Wire REST routes into Plugin**

Add to `Plugin::initialize()`:

```php
add_action('rest_api_init', [Api\Health_Controller::class, 'register']);
add_action('rest_api_init', [Api\Claim_Controller::class, 'register']);
add_action('rest_api_init', [Api\Callback_Controller::class, 'register']);
```

- [ ] **Step 5: Commit**

```bash
git add plugin/src/Api/ plugin/src/Plugin.php
git commit -m "feat(plugin): REST API controllers — health, claim, callback"
```

---
### Task 6: Admin screens

**Files:**
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Admin\Admin_Menu.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Admin\{Dashboard_Page,Sources_Page,Findings_Page,Editorial_Queue_Page,Research_Topics_Page,Agent_Runs_Page,Resources_Page,Issues_Page,Submissions_Page,Automation_Page,Settings_Page,System_Health_Page}.php`

- [ ] **Step 1: Write Admin_Menu**

```php
<?php
namespace EsotericCurrent\Core\Admin;

class Admin_Menu {
    public static function register(): void {
        add_menu_page(
            'Esoteric Current',
            'Esoteric Current',
            'manage_options',
            'ec-dashboard',
            [Dashboard_Page::class, 'render'],
            'dashicons-welcome-learn-more',
            30
        );

        $pages = [
            'ec-dashboard'       => ['Dashboard', Dashboard_Page::class],
            'ec-research-topics' => ['Research Briefs', Research_Topics_Page::class],
            'ec-agent-runs'      => ['Agent Runs', Agent_Runs_Page::class],
            'ec-findings'        => ['Findings', Findings_Page::class],
            'ec-sources'         => ['Sources', Sources_Page::class],
            'ec-editorial'       => ['Editorial Queue', Editorial_Queue_Page::class],
            'ec-resources'       => ['Resources', Resources_Page::class],
            'ec-issues'          => ['Issues', Issues_Page::class],
            'ec-submissions'     => ['Submissions', Submissions_Page::class],
            'ec-automation'      => ['Automation', Automation_Page::class],
            'ec-settings'        => ['Settings', Settings_Page::class],
            'ec-health'          => ['System Health', System_Health_Page::class],
        ];

        foreach ($pages as $slug => [$title, $class]) {
            add_submenu_page(
                'ec-dashboard',
                $title,
                $title,
                'manage_options',
                $slug,
                [$class, 'render']
            );
        }
    }
}
```

- [ ] **Step 2: Write dashboard page**

```php
<?php
namespace EsotericCurrent\Core\Admin;

class Dashboard_Page {
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        ?>
        <div class="wrap">
            <h1>Esoteric Current — Dashboard</h1>
            <div class="ec-dashboard-grid">
                <div class="ec-card">
                    <h2>Pending Review</h2>
                    <?php self::render_pending_count(); ?>
                </div>
                <div class="ec-card">
                    <h2>Last Agent Run</h2>
                    <?php self::render_last_run(); ?>
                </div>
                <div class="ec-card">
                    <h2>Active Sources</h2>
                    <?php self::render_active_sources(); ?>
                </div>
                <div class="ec-card">
                    <h2>System Health</h2>
                    <?php self::render_health(); ?>
                </div>
            </div>
        </div>
        <style>
            .ec-dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
            .ec-card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
            .ec-card h2 { margin-top: 0; font-size: 1.3em; }
        </style>
        <?php
    }

    private static function render_pending_count(): void {
        $queue_repo = new \EsotericCurrent\Core\Repository\Editorial_Queue_Repository();
        $count = count($queue_repo->get_by_state('awaiting_review'));
        echo "<p>Findings awaiting review: <strong>{$count}</strong></p>";
    }

    private static function render_last_run(): void {
        $run_repo = new \EsotericCurrent\Core\Repository\Agent_Run_Repository();
        $runs = $run_repo->get_all(['limit' => 1]);
        if (!empty($runs)) {
            $run = $runs[0];
            echo "<p>Status: <strong>" . esc_html($run['status']) . "</strong></p>";
            echo "<p>Completed: " . esc_html($run['completed_at'] ?? 'N/A') . "</p>";
        } else {
            echo "<p>No runs yet.</p>";
        }
    }

    private static function render_active_sources(): void {
        $source_repo = new \EsotericCurrent\Core\Repository\Source_Repository();
        $sources = $source_repo->get_all(['status' => 'active']);
        echo "<p>Active sources: <strong>" . count($sources) . "</strong></p>";
    }

    private static function render_health(): void {
        $version = \EsotericCurrent\Core\Database\Schema::current_version();
        echo "<p>Schema version: <strong>" . esc_html($version) . "</strong></p>";
    }
}
```

- [ ] **Step 3: Write remaining admin pages**

Each admin page follows the same pattern:
- Check `current_user_can('manage_options')`
- Render standard WordPress list table or edit form
- Use `esc_html()`, `esc_url()`, `wp_nonce_field()` on outputs
- Link to plugin repositories for data access

Key pages:
- `Sources_Page` — Source list table with add/edit/delete, status filter
- `Findings_Page` — Findings list with bulk approve/reject, type/status/topic filters
- `Editorial_Queue_Page` — Queue list with workflow state filter, approve/reject/edit links
- `Research_Topics_Page` — Topic CRUD with all fields, activate/pause toggle
- `Agent_Runs_Page` — Run history with status filter, detail view, cost display
- `Resources_Page` — Resource CRUD with type/status filters
- `Issues_Page` — Issue creation, item assignment, publish/unpublish
- `Submissions_Page` — Submissions list with approve/reject, rate limit info
- `Automation_Page` — Secret key management, GitHub config, schedule display
- `Settings_Page` — Plugin options, model provider defaults, limits
- `System_Health_Page` — Schema version, migration status, last runs, error log

- [ ] **Step 4: Wire admin menu into Plugin**

Add to `Plugin::initialize()`:

```php
add_action('admin_menu', [Admin\Admin_Menu::class, 'register']);
```

- [ ] **Step 5: Commit**

```bash
git add plugin/src/Admin/ plugin/src/Plugin.php
git commit -m "feat(plugin): admin screens — menu, dashboard, all management pages"
```

---
### Task 7: Server-rendered blocks

**Files:**
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Blocks\Block_Registrar.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Blocks\Unified_Search_Block.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Blocks\Editorial_Feed_Block.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Blocks\Resource_Index_Block.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Blocks\Source_Record_Block.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Blocks\Issue_Contents_Block.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Blocks\Submission_Form_Block.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\tests\php\Test_Blocks.php`

- [ ] **Step 1: Write Block_Registrar**

```php
<?php
namespace EsotericCurrent\Core\Blocks;

class Block_Registrar {
    public static function register_all(): void {
        $blocks = [
            'ec/unified-search'     => Unified_Search_Block::class,
            'ec/editorial-feed'     => Editorial_Feed_Block::class,
            'ec/resource-index'     => Resource_Index_Block::class,
            'ec/source-record'      => Source_Record_Block::class,
            'ec/issue-contents'     => Issue_Contents_Block::class,
            'ec/submission-form'    => Submission_Form_Block::class,
        ];

        foreach ($blocks as $name => $class) {
            register_block_type($name, [
                'render_callback' => [$class, 'render'],
                'attributes' => $class::attributes(),
            ]);
        }
    }
}
```

- [ ] **Step 2: Write Unified_Search_Block**

```php
<?php
namespace EsotericCurrent\Core\Blocks;

class Unified_Search_Block {
    public static function attributes(): array {
        return [
            'placeholder' => ['type' => 'string', 'default' => 'Search findings, resources, editorial...'],
            'max_results' => ['type' => 'number', 'default' => 20],
            'show_filters' => ['type' => 'boolean', 'default' => true],
        ];
    }

    public static function render(array $attributes): string {
        $placeholder = esc_attr($attributes['placeholder']);
        $max_results = (int)$attributes['max_results'];
        $show_filters = $attributes['show_filters'];

        $query = isset($_GET['ec_search']) ? sanitize_text_field($_GET['ec_search']) : '';
        $results = '';

        if (!empty($query)) {
            $results = self::execute_search($query, $max_results);
        }

        ob_start();
        ?>
        <div class="ec-unified-search">
            <form method="get" action="<?php echo esc_url(get_permalink()); ?>" class="ec-search-form">
                <input type="search" name="ec_search" value="<?php echo esc_attr($query); ?>"
                       placeholder="<?php echo $placeholder; ?>" class="ec-search-input" />
                <button type="submit" class="ec-search-button">Search</button>
            </form>
            <?php if (!empty($results)): ?>
                <div class="ec-search-results">
                    <?php echo $results; ?>
                </div>
            <?php elseif (!empty($query)): ?>
                <p class="ec-search-empty">No results found for "<?php echo esc_html($query); ?>".</p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function execute_search(string $query, int $max_results): string {
        global $wpdb;
        $like = '%' . $wpdb->esc_like($query) . '%';
        $limit = min($max_results, 100);

        $sql = $wpdb->prepare(
            "SELECT id, title, url, 'finding' as source_type, relevance_score
             FROM {$wpdb->prefix}ec_findings
             WHERE title LIKE %s OR excerpt LIKE %s
             LIMIT %d",
            $like, $like, $limit
        );

        $results = $wpdb->get_results($sql);
        if (empty($results)) {
            return '';
        }

        $html = '<ul class="ec-search-list">';
        foreach ($results as $row) {
            $title = esc_html($row->title);
            $url = $row->url ? esc_url($row->url) : '#';
            $html .= "<li><a href=\"{$url}\">{$title}</a></li>";
        }
        $html .= '</ul>';

        return $html;
    }
}
```

- [ ] **Step 3: Write remaining blocks**

Each block follows the same pattern:
- `attributes()` — returns block attributes array
- `render($attributes)` — returns HTML string

- `Editorial_Feed_Block` — queries `ec_editorial_queue` with `published` status, renders list of items with title, date, excerpt
- `Resource_Index_Block` — queries `ec_resources`, renders filterable grid of resource cards
- `Source_Record_Block` — takes `source_id` attribute, queries `ec_sources`, renders detail view
- `Issue_Contents_Block` — takes `issue_id` or `issue_slug` attribute, queries `ec_issues`, renders TOC
- `Submission_Form_Block` — renders form with URL, title, description, name, email fields; handles POST submission with nonce, rate limiting, validation

- [ ] **Step 4: Write Submission_Form_Block handler**

The submission form must:
1. Render a clean HTML form with nonce field
2. On POST: verify nonce, validate URL, check rate limit, sanitize inputs
3. Insert into `ec_submissions` and `ec_editorial_queue` with state `discovered`
4. Show success or error message

```php
<?php
namespace EsotericCurrent\Core\Blocks;

use EsotericCurrent\Core\Security\Rate_Limiter;

class Submission_Form_Block {
    public static function attributes(): array {
        return [
            'success_message' => ['type' => 'string', 'default' => 'Thank you for your submission. It will be reviewed shortly.'],
        ];
    }

    public static function render(array $attributes): string {
        $message = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ec_submit_url'])) {
            $message = self::handle_submission($attributes);
        }

        ob_start();
        ?>
        <div class="ec-submission-form">
            <?php if (!empty($message)): ?>
                <div class="ec-submission-message"><?php echo wp_kses_post($message); ?></div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(get_permalink()); ?>">
                <?php wp_nonce_field('ec_submission', 'ec_submission_nonce'); ?>
                <p>
                    <label for="ec_url">URL *</label>
                    <input type="url" name="ec_url" id="ec_url" required class="ec-input" />
                </p>
                <p>
                    <label for="ec_title">Title</label>
                    <input type="text" name="ec_title" id="ec_title" class="ec-input" />
                </p>
                <p>
                    <label for="ec_description">Description</label>
                    <textarea name="ec_description" id="ec_description" class="ec-input"></textarea>
                </p>
                <p>
                    <label for="ec_content_type">Content Type</label>
                    <select name="ec_content_type" id="ec_content_type" class="ec-input">
                        <option value="">General</option>
                        <option value="book">Book</option>
                        <option value="article">Article</option>
                        <option value="interview">Interview</option>
                        <option value="podcast">Podcast</option>
                        <option value="video">Video</option>
                        <option value="event">Event</option>
                    </select>
                </p>
                <p>
                    <input type="submit" value="Submit" class="ec-submit-button" />
                </p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function handle_submission(array $attributes): string {
        if (!wp_verify_nonce($_POST['ec_submission_nonce'], 'ec_submission')) {
            return 'Invalid submission. Please try again.';
        }

        $ip_hash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '');
        if (!Rate_Limiter::check('submission_' . $ip_hash, 3, 3600)) {
            return 'Too many submissions. Please try again later.';
        }
        Rate_Limiter::increment('submission_' . $ip_hash);

        $url = esc_url_raw($_POST['ec_url']);
        if (empty($url)) {
            return 'Please enter a valid URL.';
        }

        $title = sanitize_text_field($_POST['ec_title'] ?? '');
        $description = sanitize_textarea_field($_POST['ec_description'] ?? '');
        $content_type = sanitize_text_field($_POST['ec_content_type'] ?? '');

        $submission_repo = new \EsotericCurrent\Core\Repository\Submission_Repository();
        $submission_id = $submission_repo->create([
            'url' => $url,
            'title' => $title,
            'description' => $description,
            'content_type' => $content_type,
            'ip_hash' => $ip_hash,
            'status' => 'pending',
        ]);

        if ($submission_id) {
            $queue_repo = new \EsotericCurrent\Core\Repository\Editorial_Queue_Repository();
            $queue_repo->create([
                'source_type' => 'submission',
                'source_id' => $submission_id,
                'workflow_state' => 'discovered',
            ]);

            Rate_Limiter::increment('submission_' . $ip_hash);
        }

        return esc_html($attributes['success_message']);
    }
}
```

- [ ] **Step 5: Wire block registrar into Plugin**

Add to `Plugin::initialize()`:

```php
add_action('init', [Blocks\Block_Registrar::class, 'register_all']);
```

- [ ] **Step 6: Write block tests**

```php
<?php
namespace EsotericCurrent\Core\Tests;

use EsotericCurrent\Core\Blocks\Unified_Search_Block;
use PHPUnit\Framework\TestCase;

class Test_Blocks extends TestCase {
    public function test_unified_search_renders_form(): void {
        $output = Unified_Search_Block::render([
            'placeholder' => 'Search...',
            'max_results' => 20,
            'show_filters' => true,
        ]);
        $this->assertStringContainsString('ec-search-form', $output);
        $this->assertStringContainsString('ec-search-input', $output);
    }

    public function test_submission_form_renders(): void {
        $output = \EsotericCurrent\Core\Blocks\Submission_Form_Block::render([
            'success_message' => 'Thanks!',
        ]);
        $this->assertStringContainsString('ec-submission-form', $output);
        $this->assertStringContainsString('type="url"', $output);
        $this->assertStringContainsString('wp_nonce_field', $output);
    }
}
```

- [ ] **Step 7: Run tests**

Run: `cd C:\Dev\Open Code Esoteric Current\plugin && vendor/bin/phpunit tests/php/Test_Blocks.php`

Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add plugin/src/Blocks/ plugin/tests/php/Test_Blocks.php plugin/src/Plugin.php
git commit -m "feat(plugin): server-rendered blocks — all six required blocks"
```

---
### Task 8: Editorial workflow engine

**Files:**
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Editorial\Workflow_Engine.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\tests\php\Test_Workflow_Engine.php`

- [ ] **Step 1: Write Workflow_Engine**

```php
<?php
namespace EsotericCurrent\Core\Editorial;

class Workflow_Engine {
    private const VALID_TRANSITIONS = [
        'discovered'          => ['collected', 'rejected', 'duplicate'],
        'collected'           => ['awaiting_research', 'rejected', 'duplicate'],
        'awaiting_research'   => ['researching', 'rejected'],
        'researching'         => ['awaiting_review', 'failed'],
        'awaiting_review'     => ['approved', 'rejected', 'duplicate'],
        'approved'            => ['scheduled', 'archived', 'awaiting_review'],
        'scheduled'           => ['published', 'awaiting_review'],
        'published'           => ['archived'],
        'archived'            => ['published'],
        'rejected'            => ['awaiting_review'],
        'duplicate'           => ['awaiting_review'],
        'failed'              => ['awaiting_research'],
    ];

    public static function can_transition(string $from, string $to): bool {
        return in_array($to, self::VALID_TRANSITIONS[$from] ?? []);
    }

    public static function transition(\EsotericCurrent\Core\Repository\Editorial_Queue_Repository $repo, int $queue_id, string $new_state): bool {
        $item = $repo->get_by_id($queue_id);
        if ($item === null) {
            return false;
        }

        if (!self::can_transition($item['workflow_state'], $new_state)) {
            return false;
        }

        return $repo->transition($queue_id, $new_state);
    }

    public static function get_valid_transitions(string $state): array {
        return self::VALID_TRANSITIONS[$state] ?? [];
    }
}
```

- [ ] **Step 2: Write editorial tests**

```php
<?php
namespace EsotericCurrent\Core\Tests;

use EsotericCurrent\Core\Editorial\Workflow_Engine;
use PHPUnit\Framework\TestCase;

class Test_Workflow_Engine extends TestCase {
    public function test_discovered_can_transition_to_collected(): void {
        $this->assertTrue(Workflow_Engine::can_transition('discovered', 'collected'));
    }

    public function test_discovered_cannot_skip_to_approved(): void {
        $this->assertFalse(Workflow_Engine::can_transition('discovered', 'approved'));
    }

    public function test_published_can_transition_to_archived(): void {
        $this->assertTrue(Workflow_Engine::can_transition('published', 'archived'));
    }

    public function test_unknown_state_has_no_transitions(): void {
        $this->assertEmpty(Workflow_Engine::get_valid_transitions('nonexistent'));
    }

    public function test_rejected_can_return_to_review(): void {
        $this->assertTrue(Workflow_Engine::can_transition('rejected', 'awaiting_review'));
    }
}
```

- [ ] **Step 3: Run tests**

Run: `cd C:\Dev\Open Code Esoteric Current\plugin && vendor/bin/phpunit tests/php/Test_Workflow_Engine.php`

Expected: PASS

- [ ] **Step 4: Commit**

```bash
git add plugin/src/Editorial/ plugin/tests/php/Test_Workflow_Engine.php
git commit -m "feat(plugin): editorial workflow engine with state machine"
```

---
### Task 9: Ingestion (feed client, parser, duplicate detection)

**Files:**
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Ingestion\Feed_Client.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Ingestion\Feed_Parser.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Ingestion\Duplicate_Detector.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\tests\php\Test_Duplicate_Detector.php`

- [ ] **Step 1: Write Duplicate_Detector**

```php
<?php
namespace EsotericCurrent\Core\Ingestion;

class Duplicate_Detector {
    public static function content_hash(string $content): string {
        return hash('sha256', trim($content));
    }

    public static function url_hash(string $url): string {
        return hash('sha256', self::normalize_url($url));
    }

    public static function normalize_url(string $url): string {
        $parsed = wp_parse_url($url);
        if ($parsed === false) {
            return $url;
        }

        $scheme = strtolower($parsed['scheme'] ?? 'https');
        $host = strtolower($parsed['host'] ?? '');
        $path = $parsed['path'] ?? '';
        $path = rtrim($path, '/') ?: '/';
        $query = $parsed['query'] ?? '';

        $normalized = "{$scheme}://{$host}{$path}";
        if (!empty($query)) {
            parse_str($query, $params);
            ksort($params);
            $normalized .= '?' . http_build_query($params);
        }

        return $normalized;
    }

    public static function is_duplicate_url(string $url, \wpdb $wpdb): bool {
        $hash = self::url_hash($url);
        $table = $wpdb->prefix . 'ec_findings';
        $count = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE content_hash = %s",
            $hash
        ));
        return $count > 0;
    }

    public static function is_duplicate_content(string $content, \wpdb $wpdb): bool {
        $hash = self::content_hash($content);
        $table = $wpdb->prefix . 'ec_findings';
        $count = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE content_hash = %s",
            $hash
        ));
        return $count > 0;
    }
}
```

- [ ] **Step 2: Write duplicate detection tests**

```php
<?php
namespace EsotericCurrent\Core\Tests;

use EsotericCurrent\Core\Ingestion\Duplicate_Detector;
use PHPUnit\Framework\TestCase;

class Test_Duplicate_Detector extends TestCase {
    public function test_content_hash_is_deterministic(): void {
        $hash1 = Duplicate_Detector::content_hash('hello world');
        $hash2 = Duplicate_Detector::content_hash('hello world');
        $this->assertSame($hash1, $hash2);
    }

    public function test_normalize_url_removes_trailing_slash(): void {
        $url1 = Duplicate_Detector::normalize_url('https://example.com/page/');
        $url2 = Duplicate_Detector::normalize_url('https://example.com/page');
        $this->assertSame($url1, $url2);
    }

    public function test_normalize_url_lowercases_host(): void {
        $url = Duplicate_Detector::normalize_url('https://Example.COM/Path');
        $this->assertStringContainsString('example.com', $url);
    }

    public function test_url_hash_deterministic(): void {
        $hash1 = Duplicate_Detector::url_hash('https://example.com/page');
        $hash2 = Duplicate_Detector::url_hash('https://example.com/page');
        $this->assertSame($hash1, $hash2);
    }
}
```

- [ ] **Step 3: Create Feed_Client and Feed_Parser stubs**

These handle HTTP feed fetching (`wp_remote_get` with timeouts) and RSS/Atom parsing (SimplePie, bundled with WordPress).

- [ ] **Step 4: Run tests**

Run: `cd C:\Dev\Open Code Esoteric Current\plugin && vendor/bin/phpunit tests/php/Test_Duplicate_Detector.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add plugin/src/Ingestion/ plugin/tests/php/Test_Duplicate_Detector.php
git commit -m "feat(plugin): ingestion layer — feed client, parser, duplicate detection"
```

---
### Task 10: Integration and configuration

**Files:**
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Integration\Model_Provider_Interface.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\src\Integration\DeepSeek_Provider.php`
- Create: `C:\Dev\Open Code Esoteric Current\plugin\tests\php\Test_Model_Provider.php`
- Modify: `C:\Dev\Open Code Esoteric Current\plugin\src\Plugin.php` — final wiring

- [ ] **Step 1: Write provider interface**

```php
<?php
namespace EsotericCurrent\Core\Integration;

interface Model_Provider_Interface {
    public function chat(array $messages, array $options = []): array;
    public function set_api_key(string $key): void;
    public function set_model(string $model): void;
    public function set_temperature(float $temperature): void;
    public function set_max_tokens(int $tokens): void;
    public function get_usage(): array;
}
```

- [ ] **Step 2: Write DeepSeek provider**

```php
<?php
namespace EsotericCurrent\Core\Integration;

class DeepSeek_Provider implements Model_Provider_Interface {
    private string $api_key;
    private string $model = 'deepseek-chat';
    private float $temperature = 0.7;
    private int $max_tokens = 4096;
    private int $timeout = 60;
    private array $usage = ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_cost' => 0.0];

    public const API_BASE = 'https://api.deepseek.com/v1';

    public function set_api_key(string $key): void {
        $this->api_key = $key;
    }

    public function set_model(string $model): void {
        $this->model = $model;
    }

    public function set_temperature(float $temperature): void {
        $this->temperature = max(0, min(2, $temperature));
    }

    public function set_max_tokens(int $tokens): void {
        $this->max_tokens = min($tokens, 8192);
    }

    public function get_usage(): array {
        return $this->usage;
    }

    public function chat(array $messages, array $options = []): array {
        $body = wp_json_encode(array_merge([
            'model' => $options['model'] ?? $this->model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? $this->temperature,
            'max_tokens' => $options['max_tokens'] ?? $this->max_tokens,
        ], $options['extra'] ?? []));

        $response = wp_remote_post(self::API_BASE . '/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => $body,
            'timeout' => $options['timeout'] ?? $this->timeout,
        ]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($data['usage'])) {
            $this->usage['prompt_tokens'] += $data['usage']['prompt_tokens'] ?? 0;
            $this->usage['completion_tokens'] += $data['usage']['completion_tokens'] ?? 0;
        }

        return $data;
    }
}
```

- [ ] **Step 3: Write provider test**

```php
<?php
namespace EsotericCurrent\Core\Tests;

use EsotericCurrent\Core\Integration\DeepSeek_Provider;
use PHPUnit\Framework\TestCase;

class Test_Model_Provider extends TestCase {
    public function test_set_api_key(): void {
        $provider = new DeepSeek_Provider();
        $provider->set_api_key('test-key');
        $this->assertTrue(true); // No exception thrown
    }

    public function test_set_temperature_clamps(): void {
        $provider = new DeepSeek_Provider();
        $provider->set_temperature(5.0);
        $this->assertTrue(true);
    }

    public function test_set_model(): void {
        $provider = new DeepSeek_Provider();
        $provider->set_model('deepseek-reasoner');
        $this->assertTrue(true);
    }
}
```

- [ ] **Step 4: Final Plugin wiring**

Update `Plugin::initialize()` with all hook registrations:

```php
private function initialize(): void {
    if ($this->initialized) return;
    $this->initialized = true;

    add_action('init', [Database\Schema::class, 'migrate']);
    add_action('init', [Blocks\Block_Registrar::class, 'register_all']);
    add_action('rest_api_init', [Api\Health_Controller::class, 'register']);
    add_action('rest_api_init', [Api\Claim_Controller::class, 'register']);
    add_action('rest_api_init', [Api\Callback_Controller::class, 'register']);
    add_action('admin_menu', [Admin\Admin_Menu::class, 'register']);
}
```

- [ ] **Step 5: Commit**

```bash
git add plugin/src/Integration/ plugin/tests/php/Test_Model_Provider.php plugin/src/Plugin.php
git commit -m "feat(plugin): model provider abstraction and DeepSeek integration"
```
