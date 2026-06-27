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

    public function migrate_1_2_0(): void {
        // Placeholder — no structural changes.
    }

    public function migrate_1_3_0(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $this->create_finding_flags_table($wpdb);
    }

    public function migrate_1_4_0(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $this->create_terms_table($wpdb);
        $this->create_term_taxonomy_table($wpdb);
        $this->create_term_relationships_table($wpdb);
        $this->seed_default_terms($wpdb);
        $this->backfill_existing_classifications($wpdb);
        set_transient('ec_flush_rewrite_rules', true, HOUR_IN_SECONDS);
    }

    public static function maybe_flush_rewrite_rules(): void {
        if (get_transient('ec_flush_rewrite_rules')) {
            delete_transient('ec_flush_rewrite_rules');
            flush_rewrite_rules(false);
        }
    }

    private function backfill_existing_classifications($wpdb): void {
        $findings = $wpdb->get_results(
            "SELECT id, classification FROM {$wpdb->prefix}ec_findings WHERE classification IS NOT NULL AND classification != ''",
            ARRAY_A
        );
        if (empty($findings)) {
            return;
        }
        $terms_table = $wpdb->prefix . 'ec_terms';
        $tax_table = $wpdb->prefix . 'ec_term_taxonomy';
        $rel_table = $wpdb->prefix . 'ec_term_relationships';

        foreach ($findings as $finding) {
            $tags = array_map('trim', explode(',', $finding['classification']));
            $finding_id = (int)$finding['id'];
            foreach ($tags as $tag) {
                if (empty($tag)) continue;
                $slug = sanitize_title($tag);
                $ttid = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT tt.id FROM {$tax_table} tt INNER JOIN {$terms_table} t ON tt.term_id = t.id WHERE t.slug = %s AND tt.taxonomy = 'ec_topic'",
                        $slug
                    )
                );
                if (!$ttid) continue;

                $exists = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$rel_table} WHERE object_id = %d AND term_taxonomy_id = %d",
                        $finding_id, (int)$ttid
                    )
                );
                if (!$exists) {
                    $wpdb->insert($rel_table, [
                        'object_id' => $finding_id,
                        'term_taxonomy_id' => (int)$ttid,
                        'term_order' => 0,
                    ]);
                }
            }
        }
    }

    private function create_terms_table($wpdb): void {
        $table = $wpdb->prefix . 'ec_terms';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL DEFAULT '',
            slug VARCHAR(200) NOT NULL DEFAULT '',
            term_group BIGINT UNSIGNED NOT NULL DEFAULT 0,
            UNIQUE KEY uk_slug (slug)
        ) " . self::CHARSET;
        dbDelta($sql);
    }

    private function create_term_taxonomy_table($wpdb): void {
        $table = $wpdb->prefix . 'ec_term_taxonomy';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            term_id BIGINT UNSIGNED NOT NULL,
            taxonomy VARCHAR(32) NOT NULL DEFAULT '',
            description LONGTEXT DEFAULT NULL,
            parent BIGINT UNSIGNED NOT NULL DEFAULT 0,
            count BIGINT UNSIGNED NOT NULL DEFAULT 0,
            UNIQUE KEY uk_term_taxonomy (term_id, taxonomy),
            KEY idx_taxonomy (taxonomy),
            KEY idx_parent (parent)
        ) " . self::CHARSET;
        dbDelta($sql);
    }

    private function create_term_relationships_table($wpdb): void {
        $table = $wpdb->prefix . 'ec_term_relationships';
        $sql = "CREATE TABLE {$table} (
            object_id BIGINT UNSIGNED NOT NULL,
            term_taxonomy_id BIGINT UNSIGNED NOT NULL,
            term_order INT NOT NULL DEFAULT 0,
            UNIQUE KEY uk_object_term (object_id, term_taxonomy_id),
            KEY idx_term_taxonomy_id (term_taxonomy_id),
            KEY idx_object_id (object_id)
        ) " . self::CHARSET;
        dbDelta($sql);
    }

    private function seed_default_terms($wpdb): void {
        $terms_table = $wpdb->prefix . 'ec_terms';
        $tax_table = $wpdb->prefix . 'ec_term_taxonomy';

        $topic_groups = [
            'western-esoteric-tradition' => 'Western Esoteric Tradition',
            'eastern-esoteric-traditions' => 'Eastern Esoteric Traditions',
            'indigenous-earth-traditions' => 'Indigenous & Earth Traditions',
            'contemporary-esoteric' => 'Contemporary Esoteric',
            'academic-interdisciplinary' => 'Academic & Interdisciplinary',
        ];

        $topic_children = [
            'western-esoteric-tradition' => [
                'hermeticism' => 'Hermeticism',
                'alchemy' => 'Alchemy',
                'astrology' => 'Astrology',
                'ceremonial-magic' => 'Ceremonial Magic',
                'kabbalah' => 'Kabbalah',
                'gnosticism' => 'Gnosticism',
                'esoteric-christianity' => 'Esoteric Christianity',
                'mysticism' => 'Mysticism',
                'rosicrucianism' => 'Rosicrucianism',
                'theosophy' => 'Theosophy',
                'neoplatonism' => 'Neoplatonism',
            ],
            'eastern-esoteric-traditions' => [
                'dzogchen' => 'Dzogchen',
                'esoteric-buddhism' => 'Esoteric Buddhism',
                'tantra' => 'Tantra',
                'taoist-alchemy' => 'Taoist Alchemy',
                'sufism' => 'Sufism',
            ],
            'indigenous-earth-traditions' => [
                'shamanism' => 'Shamanism',
                'paganism' => 'Paganism',
            ],
            'contemporary-esoteric' => [
                'occultism' => 'Occultism',
                'chaos-magic' => 'Chaos Magic',
                'spiritual-practice' => 'Spiritual Practice',
                'psychedelics' => 'Psychedelics',
                'enochian' => 'Enochian',
            ],
            'academic-interdisciplinary' => [
                'consciousness-studies' => 'Consciousness Studies',
                'alternative-history' => 'Alternative History',
            ],
        ];

        $resource_types = [
            'books' => 'Books',
            'courses' => 'Courses',
            'teachers' => 'Teachers',
            'schools' => 'Schools',
            'events' => 'Events',
            'podcasts' => 'Podcasts',
            'videos' => 'Videos',
            'articles' => 'Articles',
            'research-papers' => 'Research Papers',
            'communities' => 'Communities',
            'apps' => 'Apps',
            'archives' => 'Archives',
            'manuscripts' => 'Manuscripts',
            'museums' => 'Museums',
            'publishers' => 'Publishers',
            'news' => 'News',
            'interviews' => 'Interviews',
            'reviews' => 'Reviews',
            'organizations' => 'Organizations',
            'people' => 'People',
        ];

        foreach ($topic_groups as $slug => $name) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$terms_table} WHERE slug = %s", $slug));
            if (!$exists) {
                $wpdb->insert($terms_table, ['name' => $name, 'slug' => $slug, 'term_group' => 0]);
                $term_id = $wpdb->insert_id;
                $wpdb->insert($tax_table, ['term_id' => $term_id, 'taxonomy' => 'ec_topic', 'description' => '', 'parent' => 0, 'count' => 0]);
            }
        }

        foreach ($topic_children as $parent_slug => $children) {
            $parent = $wpdb->get_row($wpdb->prepare("SELECT t.id, tt.id as ttid FROM {$terms_table} t LEFT JOIN {$tax_table} tt ON t.id = tt.term_id AND tt.taxonomy = 'ec_topic' WHERE t.slug = %s", $parent_slug));
            $parent_ttid = $parent ? (int)$parent->ttid : 0;
            foreach ($children as $slug => $name) {
                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$terms_table} WHERE slug = %s", $slug));
                if (!$exists) {
                    $wpdb->insert($terms_table, ['name' => $name, 'slug' => $slug, 'term_group' => 0]);
                    $term_id = $wpdb->insert_id;
                    $wpdb->insert($tax_table, ['term_id' => $term_id, 'taxonomy' => 'ec_topic', 'description' => '', 'parent' => $parent_ttid, 'count' => 0]);
                }
            }
        }

        foreach ($resource_types as $slug => $name) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$terms_table} WHERE slug = %s", $slug));
            if (!$exists) {
                $wpdb->insert($terms_table, ['name' => $name, 'slug' => $slug, 'term_group' => 0]);
                $term_id = $wpdb->insert_id;
                $wpdb->insert($tax_table, ['term_id' => $term_id, 'taxonomy' => 'ec_resource_type', 'description' => '', 'parent' => 0, 'count' => 0]);
            }
        }
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
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            finding_id BIGINT UNSIGNED NOT NULL,
            reason VARCHAR(50) NOT NULL DEFAULT 'other',
            ip_address VARCHAR(45) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reviewed TINYINT(1) NOT NULL DEFAULT 0,
            KEY idx_finding_id (finding_id),
            KEY idx_reviewed (reviewed),
            KEY idx_created_at (created_at)
        ) " . self::CHARSET;
        dbDelta($sql);
    }
}
