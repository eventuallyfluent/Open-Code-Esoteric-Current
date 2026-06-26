<?php
namespace EsotericCurrent\Core\Admin;

class Submissions_Page {
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $repo = new \EsotericCurrent\Core\Repository\Submission_Repository();
        $status = $_GET['status'] ?? 'pending';
        $submissions = $repo->get_all(['status' => $status, 'limit' => 100]);
        ?>
        <div class="wrap">
            <h1>Submissions</h1>
            <form method="get">
                <input type="hidden" name="page" value="ec-submissions" />
                <select name="status">
                    <option value="pending" <?php selected($status, 'pending'); ?>>Pending</option>
                    <option value="approved" <?php selected($status, 'approved'); ?>>Approved</option>
                    <option value="rejected" <?php selected($status, 'rejected'); ?>>Rejected</option>
                </select>
                <?php submit_button('Filter', 'secondary', '', false); ?>
            </form>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>URL</th><th>Title</th><th>Submitter</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($submissions as $s): ?>
                    <tr>
                        <td><a href="<?php echo esc_url($s['url']); ?>" target="_blank"><?php echo esc_html(mb_substr($s['url'], 0, 50)); ?></a></td>
                        <td><?php echo esc_html($s['title'] ?? '—'); ?></td>
                        <td><?php echo esc_html($s['submitter_name'] ?? 'Anonymous'); ?></td>
                        <td><?php echo esc_html($s['status']); ?></td>
                        <td><?php echo esc_html($s['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
