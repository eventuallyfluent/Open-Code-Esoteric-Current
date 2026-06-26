<?php
namespace EsotericCurrent\Core\Admin;

class Research_Topics_Page {
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $repo = new \EsotericCurrent\Core\Repository\Research_Topic_Repository();
        $action = $_GET['action'] ?? 'list';
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        // Handle saves
        $saved = false;
        if (isset($_POST['ec_save_topic']) && check_admin_referer('ec_topic_save')) {
            $saved = self::handle_save($repo);
        }
        if (isset($_POST['ec_delete_topic']) && check_admin_referer('ec_topic_delete')) {
            $saved = self::handle_delete($repo, (int)$_POST['topic_id']);
        }

        if ($action === 'new' || ($action === 'edit' && $id > 0)) {
            $topic = $id > 0 ? $repo->get_by_id($id) : null;
            self::render_form($topic);
            return;
        }

        // List view
        $topics = $repo->get_all(['limit' => 100]);
        ?>
        <div class="wrap">
            <h1>Research Briefs</h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ec-research-topics&action=new')); ?>" class="page-title-action">Add New Topic</a>
            <?php if ($saved): ?>
                <div class="notice notice-success is-dismissible"><p>Topic saved.</p></div>
            <?php endif; ?>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Title</th><th>Priority</th><th>Frequency</th><th>Content Types</th><th>Status</th><th>Next Run</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($topics)): ?>
                    <tr><td colspan="7">No topics yet. <a href="<?php echo esc_url(admin_url('admin.php?page=ec-research-topics&action=new')); ?>">Add one</a> to start agent research.</td></tr>
                <?php else: foreach ($topics as $t): ?>
                    <tr>
                        <td><a href="<?php echo esc_url(admin_url('admin.php?page=ec-research-topics&action=edit&id=' . $t['id'])); ?>"><?php echo esc_html($t['title']); ?></a></td>
                        <td><?php echo esc_html($t['priority']); ?></td>
                        <td><?php echo esc_html($t['run_frequency']); ?></td>
                        <td><?php echo esc_html($t['content_types'] ?? 'any'); ?></td>
                        <td><?php echo esc_html($t['status']); ?></td>
                        <td><?php echo esc_html($t['next_run_at'] ?? 'now'); ?></td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=ec-research-topics&action=edit&id=' . $t['id'])); ?>">Edit</a>
                            <form method="post" style="display:inline" onsubmit="return confirm('Delete this topic?')">
                                <?php wp_nonce_field('ec_topic_delete'); ?>
                                <input type="hidden" name="topic_id" value="<?php echo (int)$t['id']; ?>" />
                                <button type="submit" name="ec_delete_topic" class="button-link" style="color:#b32d2e">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function render_form(?array $topic): void {
        $is_new = $topic === null;
        $t = $topic ?: [];
        ?>
        <div class="wrap">
            <h1><?php echo $is_new ? 'Add New Topic' : 'Edit Topic'; ?></h1>
            <form method="post">
                <?php wp_nonce_field('ec_topic_save'); ?>
                <?php if (!$is_new): ?>
                    <input type="hidden" name="topic_id" value="<?php echo (int)$t['id']; ?>" />
                <?php endif; ?>
                <table class="form-table">
                    <tr><th scope="row"><label for="title">Title</label></th>
                        <td><input name="title" id="title" class="regular-text" value="<?php echo esc_attr($t['title'] ?? ''); ?>" required /></td></tr>
                    <tr><th scope="row"><label for="research_goal">Research Goal</label></th>
                        <td><textarea name="research_goal" id="research_goal" class="large-text" rows="3"><?php echo esc_textarea($t['research_goal'] ?? ''); ?></textarea>
                        <p class="description">What should the agent look for? E.g. "Find recent academic papers on Kabbalah"</p></td></tr>
                    <tr><th scope="row"><label for="included_concepts">Included Concepts</label></th>
                        <td><input name="included_concepts" id="included_concepts" class="regular-text" value="<?php echo esc_attr($t['included_concepts'] ?? ''); ?>" />
                        <p class="description">Comma-separated. Agent prioritizes these.</p></td></tr>
                    <tr><th scope="row"><label for="excluded_concepts">Excluded Concepts</label></th>
                        <td><input name="excluded_concepts" id="excluded_concepts" class="regular-text" value="<?php echo esc_attr($t['excluded_concepts'] ?? ''); ?>" />
                        <p class="description">Comma-separated. Agent avoids these.</p></td></tr>
                    <tr><th scope="row"><label for="content_types">Content Types</label></th>
                        <td>
                            <select name="content_types" id="content_types">
                                <?php $current = $t['content_types'] ?? 'any'; ?>
                                <option value="any" <?php selected($current, 'any'); ?>>Any type</option>
                                <option value="book" <?php selected($current, 'book'); ?>>Books</option>
                                <option value="news-article" <?php selected($current, 'news-article'); ?>>News / Articles</option>
                                <option value="research-paper" <?php selected($current, 'research-paper'); ?>>Research Papers</option>
                                <option value="interview" <?php selected($current, 'interview'); ?>>Interviews</option>
                                <option value="event" <?php selected($current, 'event'); ?>>Events</option>
                                <option value="podcast" <?php selected($current, 'podcast'); ?>>Podcasts</option>
                                <option value="video" <?php selected($current, 'video'); ?>>Videos</option>
                            </select>
                        </td></tr>
                    <tr><th scope="row"><label for="run_frequency">Run Frequency</label></th>
                        <td>
                            <select name="run_frequency" id="run_frequency">
                                <?php $freq = $t['run_frequency'] ?? 'daily'; ?>
                                <option value="hourly" <?php selected($freq, 'hourly'); ?>>Hourly</option>
                                <option value="daily" <?php selected($freq, 'daily'); ?>>Daily</option>
                                <option value="weekly" <?php selected($freq, 'weekly'); ?>>Weekly</option>
                                <option value="monthly" <?php selected($freq, 'monthly'); ?>>Monthly</option>
                            </select>
                            <p class="description">How often the agent checks this topic.</p>
                        </td></tr>
                    <tr><th scope="row"><label for="priority">Priority</label></th>
                        <td>
                            <select name="priority" id="priority">
                                <?php $pri = $t['priority'] ?? 'normal'; ?>
                                <option value="high" <?php selected($pri, 'high'); ?>>High</option>
                                <option value="normal" <?php selected($pri, 'normal'); ?>>Normal</option>
                                <option value="low" <?php selected($pri, 'low'); ?>>Low</option>
                            </select>
                        </td></tr>
                    <tr><th scope="row"><label for="status">Status</label></th>
                        <td>
                            <select name="status" id="status">
                                <?php $st = $t['status'] ?? 'active'; ?>
                                <option value="active" <?php selected($st, 'active'); ?>>Active</option>
                                <option value="paused" <?php selected($st, 'paused'); ?>>Paused</option>
                            </select>
                            <p class="description">Paused topics are not picked up by agents.</p>
                        </td></tr>
                    <tr><th scope="row"><label for="languages">Languages</label></th>
                        <td><input name="languages" id="languages" class="regular-text" value="<?php echo esc_attr($t['languages'] ?? ''); ?>" placeholder="en" />
                        <p class="description">Comma-separated language codes (en, fr, de, etc.)</p></td></tr>
                </table>
                <p class="submit">
                    <button type="submit" name="ec_save_topic" class="button button-primary"><?php echo $is_new ? 'Add Topic' : 'Save Topic'; ?></button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ec-research-topics')); ?>" class="button">Cancel</a>
                </p>
            </form>
        </div>
        <?php
    }

    private static function handle_save($repo): bool {
        $id = isset($_POST['topic_id']) ? (int)$_POST['topic_id'] : 0;
        $data = [
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'research_goal' => sanitize_textarea_field($_POST['research_goal'] ?? ''),
            'included_concepts' => sanitize_text_field($_POST['included_concepts'] ?? ''),
            'excluded_concepts' => sanitize_text_field($_POST['excluded_concepts'] ?? ''),
            'content_types' => sanitize_text_field($_POST['content_types'] ?? 'any'),
            'run_frequency' => sanitize_text_field($_POST['run_frequency'] ?? 'daily'),
            'priority' => sanitize_text_field($_POST['priority'] ?? 'normal'),
            'status' => sanitize_text_field($_POST['status'] ?? 'active'),
            'languages' => sanitize_text_field($_POST['languages'] ?? ''),
        ];
        if (empty($data['title'])) return false;
        if ($id > 0) {
            return $repo->update($id, $data);
        }
        return $repo->create($data) !== null;
    }

    private static function handle_delete($repo, int $id): bool {
        return $id > 0 && $repo->delete($id);
    }
}
