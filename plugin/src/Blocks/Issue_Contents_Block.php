<?php
namespace EsotericCurrent\Core\Blocks;

class Issue_Contents_Block {
    public static function attributes(): array {
        return [
            'issue_id' => ['type' => 'number', 'default' => 0],
            'issue_slug' => ['type' => 'string', 'default' => ''],
        ];
    }

    public static function render(array $attributes): string {
        $issue_repo = new \EsotericCurrent\Core\Repository\Issue_Repository();

        if (!empty($attributes['issue_id'])) {
            $issue = $issue_repo->get_by_id((int)$attributes['issue_id']);
        } elseif (!empty($attributes['issue_slug'])) {
            $issue = $issue_repo->get_by_slug($attributes['issue_slug']);
        } else {
            return '<p>Please select an issue.</p>';
        }

        if ($issue === null) {
            return '<p>Issue not found.</p>';
        }

        $queue_repo = new \EsotericCurrent\Core\Repository\Editorial_Queue_Repository();
        $items = $queue_repo->get_all(['issue_id' => $issue['id'], 'limit' => 100]);

        ob_start();
        ?>
        <div class="ec-issue-contents">
            <h2><?php echo esc_html($issue['title']); ?></h2>
            <?php if (!empty($issue['description'])): ?>
                <p class="ec-issue-description"><?php echo esc_html($issue['description']); ?></p>
            <?php endif; ?>
            <?php if (!empty($items)): ?>
                <ol class="ec-issue-toc">
                <?php foreach ($items as $item): ?>
                    <li><?php echo esc_html(ucfirst(str_replace('_', ' ', $item['source_type']))); ?> #<?php echo (int)$item['source_id']; ?></li>
                <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p>No items assigned to this issue yet.</p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
