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

    public function migrate_1_0_1(): void {
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

    public function migrate_1_3_0(): void {
        global $wpdb;
        $this->create_finding_flags_table($wpdb);
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
}
