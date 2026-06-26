<?php
namespace EsotericCurrent\Core\Admin;

class Automation_Page {
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        ?>
        <div class="wrap">
            <h1>Automation</h1>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Claim Secret</th>
                    <td><code><?php echo defined('EC_CLAIM_SECRET') && EC_CLAIM_SECRET ? '********' : 'Not configured'; ?></code></td>
                </tr>
                <tr>
                    <th scope="row">Callback Secret</th>
                    <td><code><?php echo defined('EC_CALLBACK_SECRET') && EC_CALLBACK_SECRET ? '********' : 'Not configured'; ?></code></td>
                </tr>
                <tr>
                    <th scope="row">GitHub Repository</th>
                    <td><?php echo esc_html(defined('EC_GITHUB_REPO') ? EC_GITHUB_REPO : 'Not configured'); ?></td>
                </tr>
                <tr>
                    <th scope="row">Schedule</th>
                    <td>Agent runs are triggered via GitHub Actions workflow on schedule or manual dispatch.</td>
                </tr>
            </table>
        </div>
        <?php
    }
}
