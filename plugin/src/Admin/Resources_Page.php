<?php
namespace EsotericCurrent\Core\Admin;

class Resources_Page {
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $repo = new \EsotericCurrent\Core\Repository\Resource_Repository();
        $type = $_GET['resource_type'] ?? '';
        $status = $_GET['status'] ?? '';
        $resources = $repo->get_all(['resource_type' => $type, 'status' => $status, 'limit' => 100]);
        ?>
        <div class="wrap">
            <h1>Resources</h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ec-resources&action=new')); ?>" class="page-title-action">Add New Resource</a>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Title</th><th>Type</th><th>Status</th><th>Author</th></tr></thead>
                <tbody>
                <?php foreach ($resources as $r): ?>
                    <tr>
                        <td><a href="<?php echo esc_url(admin_url('admin.php?page=ec-resources&action=edit&id=' . $r['id'])); ?>"><?php echo esc_html(mb_substr($r['title'], 0, 60)); ?></a></td>
                        <td><?php echo esc_html($r['resource_type']); ?></td>
                        <td><?php echo esc_html($r['status']); ?></td>
                        <td><?php echo esc_html($r['author'] ?? '—'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
