<?php
namespace EsotericCurrent\Core\Admin;

class Findings_Page {
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $repo = new \EsotericCurrent\Core\Repository\Finding_Repository();
        $status = $_GET['status'] ?? 'awaiting_review';
        $findings = $repo->get_all(['status' => $status, 'limit' => 100]);
        ?>
        <div class="wrap">
            <h1>Findings</h1>
            <form method="get">
                <input type="hidden" name="page" value="ec-findings" />
                <select name="status">
                    <option value="awaiting_review" <?php selected($status, 'awaiting_review'); ?>>Awaiting Review</option>
                    <option value="approved" <?php selected($status, 'approved'); ?>>Approved</option>
                    <option value="rejected" <?php selected($status, 'rejected'); ?>>Rejected</option>
                </select>
                <?php submit_button('Filter', 'secondary', '', false); ?>
            </form>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Title</th><th>Type</th><th>Relevance</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($findings as $f): ?>
                    <tr>
                        <td><?php echo esc_html(mb_substr($f['title'], 0, 80)); ?></td>
                        <td><?php echo esc_html($f['finding_type']); ?></td>
                        <td><?php echo esc_html($f['relevance_score'] ?? 'N/A'); ?></td>
                        <td><?php echo esc_html($f['status']); ?></td>
                        <td><?php echo esc_html($f['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
