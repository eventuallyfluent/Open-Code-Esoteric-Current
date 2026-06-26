<?php
namespace EsotericCurrent\Core\Admin;

class Issues_Page {
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $repo = new \EsotericCurrent\Core\Repository\Issue_Repository();
        $issues = $repo->get_all(['limit' => 100]);
        ?>
        <div class="wrap">
            <h1>Issues</h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ec-issues&action=new')); ?>" class="page-title-action">New Issue</a>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Title</th><th>Number</th><th>Status</th><th>Published</th></tr></thead>
                <tbody>
                <?php foreach ($issues as $i): ?>
                    <tr>
                        <td><a href="<?php echo esc_url(admin_url('admin.php?page=ec-issues&action=edit&id=' . $i['id'])); ?>"><?php echo esc_html($i['title']); ?></a></td>
                        <td><?php echo esc_html($i['issue_number'] ?? '—'); ?></td>
                        <td><?php echo esc_html($i['status']); ?></td>
                        <td><?php echo esc_html($i['published_at'] ?? 'Draft'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
