<?php
namespace EsotericCurrent\Core\Admin;

class Research_Topics_Page {
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $repo = new \EsotericCurrent\Core\Repository\Research_Topic_Repository();
        $topics = $repo->get_all(['limit' => 100]);
        ?>
        <div class="wrap">
            <h1>Research Briefs</h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ec-research-topics&action=new')); ?>" class="page-title-action">Add New Topic</a>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Title</th><th>Priority</th><th>Frequency</th><th>Status</th><th>Next Run</th></tr></thead>
                <tbody>
                <?php foreach ($topics as $t): ?>
                    <tr>
                        <td><a href="<?php echo esc_url(admin_url('admin.php?page=ec-research-topics&action=edit&id=' . $t['id'])); ?>"><?php echo esc_html($t['title']); ?></a></td>
                        <td><?php echo esc_html($t['priority']); ?></td>
                        <td><?php echo esc_html($t['run_frequency']); ?></td>
                        <td><?php echo esc_html($t['status']); ?></td>
                        <td><?php echo esc_html($t['next_run_at'] ?? 'Now'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
