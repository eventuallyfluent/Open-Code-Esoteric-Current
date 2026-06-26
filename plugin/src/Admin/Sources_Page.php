<?php
namespace EsotericCurrent\Core\Admin;

class Sources_Page {
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $repo = new \EsotericCurrent\Core\Repository\Source_Repository();
        $sources = $repo->get_all(['limit' => 100]);
        ?>
        <div class="wrap">
            <h1>Sources</h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ec-sources&action=new')); ?>" class="page-title-action">Add New Source</a>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Name</th><th>Type</th><th>Status</th><th>Trust Level</th><th>Last Fetched</th></tr></thead>
                <tbody>
                <?php foreach ($sources as $s): ?>
                    <tr>
                        <td><a href="<?php echo esc_url(admin_url('admin.php?page=ec-sources&action=edit&id=' . $s['id'])); ?>"><?php echo esc_html($s['name']); ?></a></td>
                        <td><?php echo esc_html($s['type']); ?></td>
                        <td><?php echo esc_html($s['status']); ?></td>
                        <td><?php echo esc_html($s['trust_level']); ?></td>
                        <td><?php echo esc_html($s['last_fetched_at'] ?? 'Never'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
