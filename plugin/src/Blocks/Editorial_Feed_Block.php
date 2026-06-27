<?php
namespace EsotericCurrent\Core\Blocks;

class Editorial_Feed_Block {
    public static function attributes(): array {
        return [
            'count' => ['type' => 'number', 'default' => 12],
            'show_excerpt' => ['type' => 'boolean', 'default' => true],
            'display' => ['type' => 'string', 'default' => 'grid'],
            'columns' => ['type' => 'number', 'default' => 3],
            'ec_topic' => ['type' => 'string', 'default' => ''],
            'ec_resource_type' => ['type' => 'string', 'default' => ''],
        ];
    }

    public static function render(array $attributes): string {
        global $wpdb;
        $count = min((int)$attributes['count'], 50);
        $display = $attributes['display'] ?? 'grid';
        $show_excerpt = !empty($attributes['show_excerpt']);
        $columns = min(max((int)($attributes['columns'] ?? 3), 1), 4);
        $ec_topic = $attributes['ec_topic'] ?? '';
        $ec_resource_type = $attributes['ec_resource_type'] ?? '';

        if (empty($ec_resource_type) && !empty($_GET['ec_tab'])) {
            $tab_map = ['books' => 'book', 'courses' => 'course', 'research' => 'research-paper', 'events' => 'event', 'podcasts' => 'podcast', 'teachers' => 'teacher', 'organizations' => 'organization'];
            $tab = sanitize_key($_GET['ec_tab']);
            if (isset($tab_map[$tab])) {
                $ec_resource_type = $tab_map[$tab];
            }
        }

        $joins = [];
        $where = 'eq.workflow_state = %s';
        $params = ['published'];

        if (!empty($ec_topic)) {
            $rel = $wpdb->prefix . 'ec_term_relationships';
            $tt = $wpdb->prefix . 'ec_term_taxonomy';
            $t = $wpdb->prefix . 'ec_terms';
            $joins[] = "INNER JOIN {$rel} r ON f.id = r.object_id";
            $joins[] = "INNER JOIN {$tt} ON r.term_taxonomy_id = {$tt}.id AND {$tt}.taxonomy = 'ec_topic'";
            $joins[] = "INNER JOIN {$t} ON {$tt}.term_id = {$t}.id";
            $where .= ' AND ' . $t . '.slug = %s';
            $params[] = sanitize_title($ec_topic);
        }

        if (!empty($ec_resource_type)) {
            $rel2 = $wpdb->prefix . 'ec_term_relationships';
            $tt2 = $wpdb->prefix . 'ec_term_taxonomy';
            $t2 = $wpdb->prefix . 'ec_terms';
            $joins[] = "INNER JOIN {$rel2} r2 ON f.id = r2.object_id";
            $joins[] = "INNER JOIN {$tt2} ON r2.term_taxonomy_id = {$tt2}.id AND {$tt2}.taxonomy = 'ec_resource_type'";
            $joins[] = "INNER JOIN {$t2} ON {$tt2}.term_id = {$t2}.id";
            $where .= ' AND ' . $t2 . '.slug = %s';
            $params[] = sanitize_title($ec_resource_type);
        }

        $blocked = [
            'wikipedia.org', 'archive.org', 'encyclopedia.com', 'britannica.com',
            'amazon.com', 'ebay.com', 'etsy.com', 'goodreads.com',
            'jstor.org', 'academia.edu', 'researchgate.net',
            'coursera.org', 'udemy.com', 'edx.org',
            'oup.com', 'cambridge.org', 'springer.com',
            'youtube.com', 'instagram.com', 'facebook.com', 'twitter.com', 'reddit.com',
        ];
        foreach ($blocked as $b) {
            $where .= ' AND COALESCE(f.source_url, f.url) NOT LIKE %s';
            $params[] = '%' . $wpdb->esc_like($b);
        }

        $join_sql = !empty($joins) ? ' ' . implode(' ', $joins) : '';

        $sql = $wpdb->prepare(
            "SELECT eq.*, f.title, f.excerpt, f.url, f.source_url,
                    f.finding_type, f.relevance_score, f.confidence_score,
                    f.classification, f.created_at
             FROM {$wpdb->prefix}ec_editorial_queue eq
             LEFT JOIN {$wpdb->prefix}ec_findings f ON (eq.source_type = 'finding' AND eq.source_id = f.id)
             {$join_sql}
             WHERE {$where}
             ORDER BY eq.updated_at DESC
             LIMIT %d",
            array_merge($params, [$count])
        );

        $items = $wpdb->get_results($sql);
        $items = self::deduplicate_by_source_id($items);
        if (empty($items)) {
            return '<div class="ec-feed-empty"><p>No published items yet.</p></div>';
        }

        ob_start();
        if ($display === 'grid') {
            self::render_grid($items, $show_excerpt, $columns);
        } else {
            self::render_list($items, $show_excerpt);
        }
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
        return ob_get_clean();
    }

    private static function render_grid(array $items, bool $show_excerpt, int $columns): void {
        global $wpdb;
        ?>
        <div class="ec-feed-grid">
            <?php foreach ($items as $i => $item):
                $is_featured = $i === 0;
                $topics = [];
                if ($is_featured && !empty($item->source_id)) {
                    $rel_table = $wpdb->prefix . 'ec_term_relationships';
                    $tax_table = $wpdb->prefix . 'ec_term_taxonomy';
                    $terms_table = $wpdb->prefix . 'ec_terms';
                    $topics = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT t.name, t.slug FROM {$rel_table} r
                             INNER JOIN {$tax_table} tt ON r.term_taxonomy_id = tt.id AND tt.taxonomy = 'ec_topic'
                             INNER JOIN {$terms_table} t ON tt.term_id = t.id
                             WHERE r.object_id = %d
                             ORDER BY t.name ASC
                             LIMIT 5",
                            $item->source_id
                        )
                    );
                }
            ?>
                <article class="ec-feed-card">
                    <div class="ec-feed-card-top">
                        <span class="ec-feed-type ec-feed-type--<?php echo esc_attr($item->finding_type ?: 'default'); ?>">
                            <span class="ec-feed-type-dot" aria-hidden="true"></span>
                            <?php echo esc_html(ucfirst($item->finding_type ?: 'Finding')); ?>
                        </span>
                    </div>
                    <h3 class="ec-feed-card-title">
                        <a href="<?php echo esc_url(home_url('/finding/' . (int)$item->source_id . '/')); ?>">
                            <?php echo esc_html($item->title); ?>
                        </a>
                    </h3>
                    <?php if ($is_featured && !empty($item->excerpt)): ?>
                        <p class="ec-feed-card-excerpt"><?php echo esc_html($item->excerpt); ?></p>
                    <?php endif; ?>
                    <?php if ($is_featured && $topics): ?>
                        <div class="ec-card-topics">
                            <?php foreach ($topics as $t): ?>
                                <a href="<?php echo esc_url(home_url('/topic/' . $t->slug . '/')); ?>" class="ec-card-topic"><?php echo esc_html($t->name); ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="ec-feed-card-footer">
                        <span class="ec-feed-card-source">
                            <?php echo esc_html(self::extract_domain($item->source_url ?: $item->url)); ?>
                        </span>
                        <span class="ec-feed-card-meta-sep" aria-hidden="true">·</span>
                        <?php if (!empty($item->created_at)): ?>
                            <time class="ec-feed-card-date" datetime="<?php echo esc_attr($item->created_at); ?>">
                                Added <?php echo self::relative_time($item->created_at); ?>
                            </time>
                        <?php endif; ?>
                        <button class="ec-save-btn" data-finding-id="<?php echo (int)$item->source_id; ?>" type="button" title="Save" aria-label="Save finding">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
                            </svg>
                        </button>
                        <button class="ec-flag-btn" data-finding-id="<?php echo (int)$item->source_id; ?>" type="button" title="Report issue" aria-label="Report issue with this finding">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z" />
                                <line x1="4" y1="22" x2="4" y2="15" />
                            </svg>
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private static function render_list(array $items, bool $show_excerpt): void {
        ?>
        <ul class="ec-feed-list">
            <?php foreach ($items as $item): ?>
                <li class="ec-feed-item">
                    <span class="ec-feed-type ec-feed-type--<?php echo esc_attr($item->finding_type ?: 'default'); ?>">
                        <span class="ec-feed-type-dot" aria-hidden="true"></span>
                        <?php echo esc_html(ucfirst($item->finding_type ?: 'Finding')); ?>
                    </span>
                    <h3><a href="<?php echo esc_url(home_url('/finding/' . (int)$item->source_id . '/')); ?>"><?php echo esc_html($item->title); ?></a></h3>
                    <?php if ($show_excerpt && !empty($item->excerpt)): ?>
                        <p class="ec-feed-excerpt"><?php echo esc_html($item->excerpt); ?></p>
                    <?php endif; ?>
                    <div class="ec-feed-meta">
                        <span><?php echo esc_html(self::extract_domain($item->source_url ?: $item->url)); ?></span>
                        <time><?php echo self::relative_time($item->created_at); ?></time>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    }

    private static function extract_domain(string $url): string {
        $parts = wp_parse_url($url);
        if (!empty($parts['host'])) {
            return preg_replace('/^www\./', '', $parts['host']);
        }
        return $url;
    }

    private static function relative_time(string $datetime): string {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        return date('M j', $timestamp);
    }

    private static function deduplicate_by_source_id(array $items): array {
        $seen = [];
        $result = [];
        foreach ($items as $item) {
            $key = (int)$item->source_id;
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $item;
            }
        }
        return $result;
    }
}
