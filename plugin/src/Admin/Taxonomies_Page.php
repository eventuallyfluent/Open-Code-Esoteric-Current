<?php
namespace EsotericCurrent\Core\Admin;

use EsotericCurrent\Core\Repository\Term_Repository;

class Taxonomies_Page {
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $taxonomy = $_GET['ec_taxonomy'] ?? 'ec_topic';
        $repo = new Term_Repository();

        if (!empty($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'ec_add_term_' . $taxonomy)) {
            $name = sanitize_text_field($_POST['term_name'] ?? '');
            if ($name) {
                $args = ['slug' => sanitize_title($_POST['term_slug'] ?? '')];
                if (!empty($_POST['parent_id'])) {
                    $args['parent'] = (int)$_POST['parent_id'];
                }
                $args['description'] = sanitize_textarea_field($_POST['description'] ?? '');
                $result = $repo->create_term($name, $taxonomy, $args);
                if ($result) {
                    echo '<div class="notice notice-success"><p>Term added.</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>Term already exists or could not be created.</p></div>';
                }
            }
        }

        if (!empty($_GET['delete']) && !empty($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'ec_delete_term_' . (int)$_GET['delete'])) {
            $repo->delete_term((int)$_GET['delete']);
            echo '<div class="notice notice-success"><p>Term deleted.</p></div>';
        }

        $terms = $repo->get_terms($taxonomy);

        ?>
        <div class="wrap">
            <h1>Taxonomies</h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=ec-taxonomies&ec_taxonomy=ec_topic" class="nav-tab <?php echo $taxonomy === 'ec_topic' ? 'nav-tab-active' : ''; ?>">Topic Channels</a>
                <a href="?page=ec-taxonomies&ec_taxonomy=ec_resource_type" class="nav-tab <?php echo $taxonomy === 'ec_resource_type' ? 'nav-tab-active' : ''; ?>">Resource Types</a>
            </h2>

            <form method="post" style="margin-bottom:2em;padding:1em;background:#f0f0f1;max-width:500px">
                <?php wp_nonce_field('ec_add_term_' . $taxonomy); ?>
                <h3>Add New <?php echo $taxonomy === 'ec_topic' ? 'Topic Channel' : 'Resource Type'; ?></h3>
                <p>
                    <label>Name<br>
                    <input type="text" name="term_name" class="regular-text" required></label>
                </p>
                <p>
                    <label>Slug (optional)<br>
                    <input type="text" name="term_slug" class="regular-text" placeholder="auto-generated if empty"></label>
                </p>
                <?php if ($taxonomy === 'ec_topic'): ?>
                    <p>
                        <label>Parent Group<br>
                        <select name="parent_id">
                            <option value="">— No parent —</option>
                            <?php foreach ($terms as $t): ?>
                                <?php if ($t['parent'] == 0 && $t['taxonomy'] === $taxonomy): ?>
                                    <option value="<?php echo (int)$t['term_id']; ?>"><?php echo esc_html($t['name']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select></label>
                    </p>
                <?php endif; ?>
                <p>
                    <label>Description<br>
                    <textarea name="description" rows="3" class="large-text"></textarea></label>
                </p>
                <p><?php submit_button('Add Term', 'primary', '', false); ?></p>
            </form>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Parent</th>
                        <th>Findings</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($terms as $t): ?>
                    <?php
                    $parent_name = '';
                    if ($t['parent'] > 0) {
                        $parent_term = $repo->get_term_by_slug(
                            $this->get_slug_from_ttid($repo, $t['parent']),
                            $taxonomy
                        );
                        $parent_name = $parent_term ? $parent_term['name'] : '';
                    }
                    $delete_url = wp_nonce_url(
                        admin_url('admin.php?page=ec-taxonomies&ec_taxonomy=' . $taxonomy . '&delete=' . $t['term_id']),
                        'ec_delete_term_' . $t['term_id']
                    );
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($t['name']); ?></strong></td>
                        <td><code><?php echo esc_html($t['slug']); ?></code></td>
                        <td><?php echo esc_html($parent_name); ?></td>
                        <td><?php echo (int)$t['count']; ?></td>
                        <td><a href="<?php echo esc_url($delete_url); ?>" class="button-small" onclick="return confirm('Delete this term? This will remove all relationships.')">Delete</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function get_slug_from_ttid(Term_Repository $repo, int $ttid): string {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT t.slug FROM {$wpdb->prefix}ec_term_taxonomy tt INNER JOIN {$wpdb->prefix}ec_terms t ON tt.term_id = t.id WHERE tt.id = %d",
                $ttid
            )
        );
        return $row ? $row->slug : '';
    }
}
