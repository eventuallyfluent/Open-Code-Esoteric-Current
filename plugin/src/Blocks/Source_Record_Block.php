<?php
namespace EsotericCurrent\Core\Blocks;

class Source_Record_Block {
    public static function attributes(): array {
        return [
            'source_id' => ['type' => 'number', 'default' => 0],
        ];
    }

    public static function render(array $attributes): string {
        $source_id = (int)$attributes['source_id'];
        if ($source_id <= 0) {
            return '<p>Please select a source.</p>';
        }

        $repo = new \EsotericCurrent\Core\Repository\Source_Repository();
        $source = $repo->get_by_id($source_id);

        if ($source === null) {
            return '<p>Source not found.</p>';
        }

        ob_start();
        ?>
        <div class="ec-source-record">
            <h2><?php echo esc_html($source['name']); ?></h2>
            <dl class="ec-source-details">
                <dt>Type</dt><dd><?php echo esc_html($source['type']); ?></dd>
                <dt>URL</dt><dd><a href="<?php echo esc_url($source['url']); ?>"><?php echo esc_html($source['url']); ?></a></dd>
                <?php if (!empty($source['feed_url'])): ?>
                <dt>Feed URL</dt><dd><a href="<?php echo esc_url($source['feed_url']); ?>"><?php echo esc_html($source['feed_url']); ?></a></dd>
                <?php endif; ?>
                <dt>Status</dt><dd><?php echo esc_html($source['status']); ?></dd>
                <dt>Trust Level</dt><dd><?php echo esc_html($source['trust_level']); ?></dd>
                <dt>Last Fetched</dt><dd><?php echo esc_html($source['last_fetched_at'] ?? 'Never'); ?></dd>
            </dl>
        </div>
        <?php
        return ob_get_clean();
    }
}
