<?php
namespace EsotericCurrent\Core\Blocks;

class Editorial_Feed_Block {
    public static function attributes(): array {
        return [
            'count' => ['type' => 'number', 'default' => 12],
            'show_excerpt' => ['type' => 'boolean', 'default' => true],
            'display' => ['type' => 'string', 'default' => 'grid'],
            'columns' => ['type' => 'number', 'default' => 3],
        ];
    }

    public static function render(array $attributes): string {
        global $wpdb;
        $count = min((int)$attributes['count'], 50);
        $display = $attributes['display'] ?? 'grid';
        $show_excerpt = !empty($attributes['show_excerpt']);
        $columns = min(max((int)($attributes['columns'] ?? 3), 1), 4);

        $sql = $wpdb->prepare(
            "SELECT eq.*, f.title, f.excerpt, f.url, f.source_url,
                    f.finding_type, f.relevance_score, f.confidence_score,
                    f.classification, f.created_at
             FROM {$wpdb->prefix}ec_editorial_queue eq
             LEFT JOIN {$wpdb->prefix}ec_findings f ON (eq.source_type = 'finding' AND eq.source_id = f.id)
             WHERE eq.workflow_state = %s
             ORDER BY eq.updated_at DESC
             LIMIT %d",
            'published', $count
        );

        $items = $wpdb->get_results($sql);
        if (empty($items)) {
            return '<div class="ec-feed-empty"><p>No published items yet.</p></div>';
        }

        ob_start();
        if ($display === 'grid') {
            self::render_grid($items, $show_excerpt, $columns);
        } else {
            self::render_list($items, $show_excerpt);
        }
        return ob_get_clean();
    }

    private static function render_grid(array $items, bool $show_excerpt, int $columns): void {
        ?>
        <div class="ec-feed-grid" style="--ec-feed-cols: <?php echo $columns; ?>">
            <?php foreach ($items as $item): ?>
                <article class="ec-feed-card">
                    <div class="ec-feed-card-top">
                        <span class="ec-feed-type ec-feed-type--<?php echo esc_attr($item->finding_type ?: 'default'); ?>">
                            <span class="ec-feed-type-dot" aria-hidden="true"></span>
                            <?php echo esc_html(ucfirst($item->finding_type ?: 'Finding')); ?>
                        </span>
                        <?php if (!empty($item->confidence_score)): ?>
                            <span class="ec-feed-confidence" title="Confidence: <?php echo esc_attr($item->confidence_score); ?>%">
                                <?php echo round((float)$item->confidence_score); ?>%
                            </span>
                        <?php endif; ?>
                    </div>
                    <h3 class="ec-feed-card-title">
                        <a href="<?php echo esc_url($item->url); ?>" target="_blank" rel="noopener">
                            <?php echo esc_html($item->title); ?>
                        </a>
                    </h3>
                    <?php if ($show_excerpt && !empty($item->excerpt)): ?>
                        <p class="ec-feed-card-excerpt"><?php echo esc_html(mb_substr($item->excerpt, 0, 200)); ?></p>
                    <?php endif; ?>
                    <div class="ec-feed-card-footer">
                        <span class="ec-feed-card-source">
                            <?php echo esc_html(self::extract_domain($item->source_url ?: $item->url)); ?>
                        </span>
                        <?php if (!empty($item->created_at)): ?>
                            <time class="ec-feed-card-date" datetime="<?php echo esc_attr($item->created_at); ?>">
                                <?php echo self::relative_time($item->created_at); ?>
                            </time>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($item->relevance_score)): ?>
                        <div class="ec-feed-card-relevance" aria-label="Relevance: <?php echo esc_attr($item->relevance_score); ?>%">
                            <span class="ec-feed-card-relevance-bar" style="width:<?php echo round((float)$item->relevance_score); ?>%"></span>
                        </div>
                    <?php endif; ?>
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
                    <h3><a href="<?php echo esc_url($item->url); ?>" target="_blank" rel="noopener"><?php echo esc_html($item->title); ?></a></h3>
                    <?php if ($show_excerpt && !empty($item->excerpt)): ?>
                        <p class="ec-feed-excerpt"><?php echo esc_html(mb_substr($item->excerpt, 0, 200)); ?></p>
                    <?php endif; ?>
                    <div class="ec-feed-meta">
                        <span><?php echo esc_html(self::extract_domain($item->source_url ?: $item->url)); ?></span>
                        <?php if (!empty($item->created_at)): ?>
                            <time><?php echo self::relative_time($item->created_at); ?></time>
                        <?php endif; ?>
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
}
