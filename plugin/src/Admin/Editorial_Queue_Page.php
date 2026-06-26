<?php
namespace EsotericCurrent\Core\Admin;

class Editorial_Queue_Page {
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $repo = new \EsotericCurrent\Core\Repository\Editorial_Queue_Repository();
        $state = $_GET['state'] ?? 'discovered';
        $items = $repo->get_all(['workflow_state' => $state, 'limit' => 100]);
        ?>
        <div class="wrap">
            <h1>Editorial Queue</h1>
            <form method="get">
                <input type="hidden" name="page" value="ec-editorial" />
                <select name="state">
                    <?php
                    $states = ['discovered', 'collected', 'awaiting_research', 'researching', 'awaiting_review', 'approved', 'scheduled', 'published', 'archived'];
                    foreach ($states as $s) {
                        echo '<option value="' . esc_attr($s) . '" ' . selected($state, $s, false) . '>' . esc_html(ucfirst(str_replace('_', ' ', $s))) . '</option>';
                    }
                    ?>
                </select>
                <?php submit_button('Filter', 'secondary', '', false); ?>
            </form>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>ID</th><th>Source Type</th><th>Source ID</th><th>Workflow State</th><th>Created</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo (int)$item['id']; ?></td>
                        <td><?php echo esc_html($item['source_type']); ?></td>
                        <td><?php echo (int)$item['source_id']; ?></td>
                        <td><?php echo esc_html($item['workflow_state']); ?></td>
                        <td><?php echo esc_html($item['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
