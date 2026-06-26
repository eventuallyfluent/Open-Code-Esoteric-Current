<?php
namespace EsotericCurrent\Core\Admin;

class Agent_Runs_Page {
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $repo = new \EsotericCurrent\Core\Repository\Agent_Run_Repository();
        $status = $_GET['status'] ?? '';
        $runs = $repo->get_all(['status' => $status, 'limit' => 100]);
        ?>
        <div class="wrap">
            <h1>Agent Runs</h1>
            <form method="get">
                <input type="hidden" name="page" value="ec-agent-runs" />
                <select name="status">
                    <option value="">All</option>
                    <option value="pending" <?php selected($status, 'pending'); ?>>Pending</option>
                    <option value="claimed" <?php selected($status, 'claimed'); ?>>Claimed</option>
                    <option value="completed" <?php selected($status, 'completed'); ?>>Completed</option>
                    <option value="failed" <?php selected($status, 'failed'); ?>>Failed</option>
                </select>
                <?php submit_button('Filter', 'secondary', '', false); ?>
            </form>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Run UUID</th><th>Topic ID</th><th>Trigger</th><th>Status</th><th>Cost</th><th>Completed</th></tr></thead>
                <tbody>
                <?php foreach ($runs as $r): ?>
                    <tr>
                        <td><code><?php echo esc_html(mb_substr($r['run_uuid'], 0, 8)); ?>…</code></td>
                        <td><?php echo (int)$r['topic_id']; ?></td>
                        <td><?php echo esc_html($r['trigger_type']); ?></td>
                        <td><?php echo esc_html($r['status']); ?></td>
                        <td><?php echo $r['estimated_cost'] !== null ? '$' . esc_html((string)$r['estimated_cost']) : '—'; ?></td>
                        <td><?php echo esc_html($r['completed_at'] ?? '—'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
