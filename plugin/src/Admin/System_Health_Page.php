<?php
namespace EsotericCurrent\Core\Admin;

class System_Health_Page {
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $schema_version = \EsotericCurrent\Core\Database\Schema::current_version();
        $run_repo = new \EsotericCurrent\Core\Repository\Run_Log_Repository();
        $recent_logs = $run_repo->get_all(['limit' => 20]);
        ?>
        <div class="wrap">
            <h1>System Health</h1>
            <table class="form-table" role="presentation">
                <tr><th>Plugin Version</th><td><?php echo esc_html(EC_CORE_VERSION); ?></td></tr>
                <tr><th>Schema Version</th><td><?php echo esc_html($schema_version); ?></td></tr>
                <tr><th>PHP Version</th><td><?php echo esc_html(PHP_VERSION); ?></td></tr>
                <tr><th>Database</th><td><?php global $wpdb; echo esc_html($wpdb->db_server_info()); ?></td></tr>
            </table>
            <h2>Recent Run Log</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Level</th><th>Message</th><th>Run ID</th><th>Time</th></tr></thead>
                <tbody>
                <?php foreach ($recent_logs as $log): ?>
                    <tr>
                        <td><?php echo esc_html($log['level']); ?></td>
                        <td><?php echo esc_html(mb_substr($log['message'], 0, 100)); ?></td>
                        <td><?php echo (int)$log['run_id']; ?></td>
                        <td><?php echo esc_html($log['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
