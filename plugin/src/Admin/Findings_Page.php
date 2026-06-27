<?php
namespace EsotericCurrent\Core\Admin;

use EsotericCurrent\Core\Repository\Finding_Repository;
use EsotericCurrent\Core\Repository\Term_Repository;
use EsotericCurrent\Core\Repository\Editorial_Queue_Repository;

class Findings_Page {
    public static function render(): void {
        global $wpdb;
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $repo = new Finding_Repository();
        $term_repo = new Term_Repository();
        $eq_repo = new Editorial_Queue_Repository();

        if (!empty($_POST['ec_action']) && !empty($_POST['finding_id']) && check_admin_referer('ec_finding_action_' . $_POST['finding_id'])) {
            $finding_id = (int)$_POST['finding_id'];
            $action = $_POST['ec_action'];
            $new_status = '';
            if ($action === 'approve') {
                $new_status = 'approved';
            } elseif ($action === 'publish') {
                $new_status = 'published';
            } elseif ($action === 'reject') {
                $new_status = 'rejected';
            }
            if ($new_status) {
                $repo->update_status($finding_id, $new_status);
                $eq_entry = $eq_repo->get_by_source('finding', $finding_id);
                if ($eq_entry) {
                    $eq_repo->transition((int)$eq_entry['id'], $new_status);
                }
                echo '<div class="notice notice-success"><p>Finding ' . esc_html($new_status) . '.</p></div>';
            }
        }

        if (!empty($_POST['ec_bulk_reject_all']) && check_admin_referer('ec_bulk_reject')) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ec_findings WHERE status = 'approved'");
            $wpdb->query("UPDATE {$wpdb->prefix}ec_findings SET status = 'rejected' WHERE status = 'approved'");
            $wpdb->query("UPDATE {$wpdb->prefix}ec_editorial_queue SET workflow_state = 'rejected', transitioned_at = NOW() WHERE workflow_state IN ('approved', 'published')");
            echo '<div class="notice notice-warning"><p>Rejected ' . (int)$count . ' findings. Worker will now re-discover with stricter quality filters.</p></div>';
        }

        $status = $_GET['status'] ?? 'approved';
        $ec_topic = $_GET['ec_topic'] ?? '';
        $ec_resource_type = $_GET['ec_resource_type'] ?? '';

        $args = ['status' => $status, 'limit' => 100];
        if ($ec_topic) {
            $args['ec_topic'] = sanitize_title($ec_topic);
        }
        if ($ec_resource_type) {
            $args['ec_resource_type'] = sanitize_title($ec_resource_type);
        }

        $findings = $repo->get_all($args);
        $topics = $term_repo->get_terms('ec_topic', ['parent' => 0]);
        $child_topics = $term_repo->get_terms('ec_topic');
        $resource_types = $term_repo->get_terms('ec_resource_type');
        ?>
        <div class="wrap">
            <h1>Findings</h1>
            <form method="get">
                <input type="hidden" name="page" value="ec-findings" />
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="approved" <?php selected($status, 'approved'); ?>>Approved</option>
                    <option value="published" <?php selected($status, 'published'); ?>>Published</option>
                    <option value="rejected" <?php selected($status, 'rejected'); ?>>Rejected</option>
                    <option value="awaiting_review" <?php selected($status, 'awaiting_review'); ?>>Awaiting Review</option>
                </select>
                <select name="ec_topic">
                    <option value="">All Topics</option>
                    <?php foreach ($topics as $group): ?>
                        <optgroup label="<?php echo esc_attr($group['name']); ?>">
                        <?php foreach ($child_topics as $ct): ?>
                            <?php if ($ct['parent'] === $group['term_taxonomy_id']): ?>
                                <option value="<?php echo esc_attr($ct['slug']); ?>" <?php selected($ec_topic, $ct['slug']); ?>><?php echo esc_html($ct['name']); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <select name="ec_resource_type">
                    <option value="">All Types</option>
                    <?php foreach ($resource_types as $rt): ?>
                        <?php if ($rt['parent'] == 0): ?>
                            <option value="<?php echo esc_attr($rt['slug']); ?>" <?php selected($ec_resource_type, $rt['slug']); ?>><?php echo esc_html($rt['name']); ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <?php submit_button('Filter', 'secondary', '', false); ?>
            </form>
            <?php if ($status === 'approved' && $findings): ?>
            <form method="post" style="margin-bottom:1em" onsubmit="return confirm('Reject ALL approved findings? This clears the slate for the worker to re-discover with stricter quality filters.')">
                <?php wp_nonce_field('ec_bulk_reject'); ?>
                <input type="hidden" name="ec_bulk_reject_all" value="1">
                <button type="submit" class="button button-small" style="color:#b32d2e;border-color:#b32d2e">Reject All Approved &amp; Reset</button>
            </form>
            <?php endif; ?>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Title</th><th>Type</th><th>Topics</th><th>Relevance</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($findings as $f): ?>
                    <?php
                    $finding_terms = $term_repo->get_object_terms((int)$f['id'], 'ec_topic');
                    $topic_names = array_map(function ($t) { return $t['name']; }, $finding_terms);
                    ?>
                    <tr>
                        <td><a href="<?php echo esc_url(admin_url('admin.php?page=ec-findings&action=edit&id=' . $f['id'])); ?>"><?php echo esc_html(mb_substr($f['title'], 0, 80)); ?></a></td>
                        <td><?php echo esc_html($f['finding_type']); ?></td>
                        <td><?php echo esc_html(implode(', ', $topic_names)); ?></td>
                        <td><?php echo esc_html($f['relevance_score'] ?? 'N/A'); ?></td>
                        <td><?php echo esc_html($f['status']); ?></td>
                        <td><?php echo esc_html($f['created_at']); ?></td>
                        <td>
                            <?php if ($f['status'] === 'approved'): ?>
                            <form method="post" style="display:inline">
                                <?php wp_nonce_field('ec_finding_action_' . $f['id']); ?>
                                <input type="hidden" name="finding_id" value="<?php echo (int)$f['id']; ?>">
                                <input type="hidden" name="ec_action" value="publish">
                                <button type="submit" class="button button-primary button-small">Publish</button>
                            </form>
                            <?php endif; ?>
                            <?php if ($f['status'] !== 'approved' && $f['status'] !== 'published'): ?>
                            <form method="post" style="display:inline">
                                <?php wp_nonce_field('ec_finding_action_' . $f['id']); ?>
                                <input type="hidden" name="finding_id" value="<?php echo (int)$f['id']; ?>">
                                <input type="hidden" name="ec_action" value="approve">
                                <button type="submit" class="button button-small">Approve</button>
                            </form>
                            <?php endif; ?>
                            <?php if ($f['status'] !== 'rejected' && $f['status'] !== 'published'): ?>
                            <form method="post" style="display:inline">
                                <?php wp_nonce_field('ec_finding_action_' . $f['id']); ?>
                                <input type="hidden" name="finding_id" value="<?php echo (int)$f['id']; ?>">
                                <input type="hidden" name="ec_action" value="reject">
                                <button type="submit" class="button button-small" onclick="return confirm('Reject this finding?')">Reject</button>
                            </form>
                            <?php endif; ?>
                            <?php if ($f['status'] === 'published'): ?>
                            <span class="ec-status-badge" style="color:#46b450;font-weight:600">Published</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
