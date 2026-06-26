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
