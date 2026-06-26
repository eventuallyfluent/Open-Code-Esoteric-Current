<?php
namespace EsotericCurrent\Core\Blocks;

class Resource_Index_Block {
    public static function attributes(): array {
        return [
            'type_filter' => ['type' => 'string', 'default' => ''],
            'count' => ['type' => 'number', 'default' => 20],
        ];
    }

    public static function render(array $attributes): string {
        $repo = new \EsotericCurrent\Core\Repository\Resource_Repository();
        $args = ['status' => 'published', 'limit' => min((int)$attributes['count'], 50)];
        if (!empty($attributes['type_filter'])) {
            $args['resource_type'] = $attributes['type_filter'];
        }
        $resources = $repo->get_all($args);

        ob_start();
        ?>
        <div class="ec-resource-index">
            <?php if (empty($resources)): ?>
                <p>No resources found.</p>
            <?php else: ?>
                <div class="ec-resource-grid">
                <?php foreach ($resources as $r): ?>
                    <div class="ec-resource-card">
                        <h3><a href="<?php echo esc_url($r['url']); ?>"><?php echo esc_html($r['title']); ?></a></h3>
                        <?php if (!empty($r['description'])): ?>
                            <p><?php echo esc_html(mb_substr($r['description'], 0, 150)); ?></p>
                        <?php endif; ?>
                        <span class="ec-resource-type"><?php echo esc_html($r['resource_type']); ?></span>
                        <?php if (!empty($r['author'])): ?>
                            <span class="ec-resource-author"> — <?php echo esc_html($r['author']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
