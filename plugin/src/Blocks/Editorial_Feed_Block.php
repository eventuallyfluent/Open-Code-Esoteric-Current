<?php
namespace EsotericCurrent\Core\Blocks;

class Editorial_Feed_Block {
    public static function attributes(): array {
        return [
            'count' => ['type' => 'number', 'default' => 10],
            'show_excerpt' => ['type' => 'boolean', 'default' => true],
        ];
    }

    public static function render(array $attributes): string {
        global $wpdb;
        $count = min((int)$attributes['count'], 50);

        $sql = $wpdb->prepare(
            "SELECT eq.*, f.title, f.excerpt, f.url, f.finding_type
             FROM {$wpdb->prefix}ec_editorial_queue eq
             LEFT JOIN {$wpdb->prefix}ec_findings f ON (eq.source_type = 'finding' AND eq.source_id = f.id)
             WHERE eq.workflow_state = %s
             ORDER BY eq.updated_at DESC
             LIMIT %d",
            'published', $count
        );

        $items = $wpdb->get_results($sql);

        ob_start();
        ?>
        <div class="ec-editorial-feed">
            <?php if (empty($items)): ?>
                <p>No published items yet.</p>
            <?php else: ?>
                <ul class="ec-feed-list">
                <?php foreach ($items as $item): ?>
                    <li class="ec-feed-item">
                        <h3><a href="<?php echo esc_url($item->url); ?>"><?php echo esc_html($item->title); ?></a></h3>
                        <?php if (!empty($attributes['show_excerpt']) && !empty($item->excerpt)): ?>
                            <p class="ec-feed-excerpt"><?php echo esc_html(mb_substr($item->excerpt, 0, 200)); ?></p>
                        <?php endif; ?>
                        <span class="ec-feed-meta"><?php echo esc_html(ucfirst($item->finding_type)); ?></span>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
