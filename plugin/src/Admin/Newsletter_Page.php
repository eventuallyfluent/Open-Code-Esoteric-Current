<?php
namespace EsotericCurrent\Core\Admin;

class Newsletter_Page {
    public static function render(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'ec_newsletter_subscribers';
        $subscribers = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC");
        $active_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'active'");
        ?>
        <div class="wrap">
            <h1>Newsletter Subscribers</h1>
            <p>Total active subscribers: <strong><?php echo (int) $active_count; ?></strong></p>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Subscribed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subscribers)): ?>
                        <tr><td colspan="3">No subscribers yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($subscribers as $s): ?>
                            <tr>
                                <td><?php echo esc_html($s->email); ?></td>
                                <td><?php echo esc_html($s->status); ?></td>
                                <td><?php echo esc_html($s->created_at); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}