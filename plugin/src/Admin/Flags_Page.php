<?php
namespace EsotericCurrent\Core\Admin;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Flags_List_Table extends \WP_List_Table {
    private \EsotericCurrent\Core\Repository\Flag_Repository $repo;
    private \EsotericCurrent\Core\Repository\Finding_Repository $finding_repo;
    private array $finding_cache = [];

    public function __construct() {
        parent::__construct([
            'singular' => 'flag',
            'plural'   => 'flags',
            'ajax'     => false,
        ]);
        $this->repo = new \EsotericCurrent\Core\Repository\Flag_Repository();
        $this->finding_repo = new \EsotericCurrent\Core\Repository\Finding_Repository();
    }

    public function get_columns(): array {
        return [
            'cb'            => '<input type="checkbox" />',
            'id'            => 'ID',
            'finding_title' => 'Finding',
            'finding_id'    => 'Finding ID',
            'reason'        => 'Reason',
            'ip_address'    => 'IP Address',
            'created_at'    => 'Reported',
            'reviewed'      => 'Reviewed',
        ];
    }

    protected function get_sortable_columns(): array {
        return [
            'id'         => ['id', false],
            'created_at' => ['created_at', false],
            'reviewed'   => ['reviewed', false],
        ];
    }

    protected function column_default($item, $column_name): string {
        return esc_html($item[$column_name] ?? '');
    }

    protected function column_cb($item): string {
        return sprintf(
            '<input type="checkbox" name="flag_ids[]" value="%d" />',
            (int)$item['id']
        );
    }

    protected function column_finding_title($item): string {
        $title = $this->get_finding_title((int)$item['finding_id']);
        $label = $title ?: "(finding #{$item['finding_id']})";
        $url = site_url('/finding/' . (int)$item['finding_id'] . '/');
        return '<a href="' . esc_url($url) . '" target="_blank">' . esc_html($label) . '</a>';
    }

    protected function column_reviewed($item): string {
        return $item['reviewed']
            ? '<span class="dashicons dashicons-yes" style="color:#46b450"></span>'
            : '<span class="dashicons dashicons-marker" style="color:#dc3232"></span>';
    }

    protected function column_reason($item): string {
        return esc_html(ucwords(str_replace('-', ' ', $item['reason'])));
    }

    protected function column_id($item): string {
        $dismiss_url = wp_nonce_url(
            add_query_arg([
                'action'   => 'dismiss',
                'flag_id'  => (int)$item['id'],
            ]),
            'ec_flag_dismiss_' . $item['id']
        );
        $delete_url = wp_nonce_url(
            add_query_arg([
                'action'  => 'delete',
                'flag_id' => (int)$item['id'],
            ]),
            'ec_flag_delete_' . $item['id']
        );

        $actions = [
            'dismiss' => '<a href="' . esc_url($dismiss_url) . '">Dismiss</a>',
            'delete'  => '<a href="' . esc_url($delete_url) . '" style="color:#b32d2e">Delete</a>',
        ];

        return sprintf(
            '<strong>%d</strong> %s',
            (int)$item['id'],
            $this->row_actions($actions)
        );
    }

    protected function get_bulk_actions(): array {
        return [
            'dismiss' => 'Dismiss',
            'delete'  => 'Delete',
        ];
    }

    protected function extra_tablenav($which): void {
        if ($which !== 'top') {
            return;
        }
        $current_reviewed = $_GET['reviewed'] ?? '';
        ?>
        <div class="alignleft actions">
            <select name="reviewed">
                <option value="">All flags</option>
                <option value="0" <?php selected($current_reviewed, '0'); ?>>Unreviewed</option>
                <option value="1" <?php selected($current_reviewed, '1'); ?>>Reviewed</option>
            </select>
            <input type="hidden" name="page" value="ec-flags" />
            <?php submit_button('Filter', 'secondary', '', false); ?>
        </div>
        <?php
    }

    public function prepare_items(): void {
        $per_page = 20;
        $current_page = $this->get_pagenum();

        $args = [];
        if (isset($_GET['reviewed']) && $_GET['reviewed'] !== '') {
            $args['reviewed'] = (int)$_GET['reviewed'];
        }

        $total_items = $this->repo->count($args);
        $args['limit'] = $per_page;
        $args['offset'] = ($current_page - 1) * $per_page;

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ]);

        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
        $this->items = $this->repo->get_all($args);
    }

    private function get_finding_title(int $finding_id): string {
        if (!isset($this->finding_cache[$finding_id])) {
            $finding = $this->finding_repo->get_by_id($finding_id);
            $this->finding_cache[$finding_id] = $finding ? $finding['title'] : '';
        }
        return $this->finding_cache[$finding_id];
    }
}

class Flags_Page {
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        self::handle_actions();

        $table = new Flags_List_Table();
        $table->prepare_items();

        ?>
        <div class="wrap">
            <h1>Flagged Findings</h1>
            <?php if (!empty($_GET['ec_flag_msg'])): ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html(rawurldecode($_GET['ec_flag_msg'])); ?></p></div>
            <?php endif; ?>
            <form method="get">
                <input type="hidden" name="page" value="ec-flags" />
                <?php $table->search_box('Search', 'flag-search'); ?>
            </form>
            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=ec-flags')); ?>">
                <?php wp_nonce_field('ec_flag_bulk'); ?>
                <?php $table->display(); ?>
            </form>
        </div>
        <?php
    }

    private static function handle_actions(): void {
        $repo = new \EsotericCurrent\Core\Repository\Flag_Repository();
        $action = $_GET['action'] ?? '';
        $flag_id = isset($_GET['flag_id']) ? (int)$_GET['flag_id'] : 0;

        // Single dismiss
        if ($action === 'dismiss' && $flag_id > 0) {
            if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'ec_flag_dismiss_' . $flag_id)) {
                wp_die('Security check failed');
            }
            $repo->mark_reviewed($flag_id);
            wp_safe_redirect(remove_query_arg(['action', 'flag_id', '_wpnonce']));
            exit;
        }

        // Single delete
        if ($action === 'delete' && $flag_id > 0) {
            if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'ec_flag_delete_' . $flag_id)) {
                wp_die('Security check failed');
            }
            $repo->delete($flag_id);
            wp_safe_redirect(remove_query_arg(['action', 'flag_id', '_wpnonce']));
            exit;
        }

        // Bulk actions (top and bottom dropdown)
        $bulk_action = $_POST['action'] ?? '';
        if ($bulk_action === '-1' && !empty($_POST['action2']) && $_POST['action2'] !== '-1') {
            $bulk_action = $_POST['action2'];
        }

        if (in_array($bulk_action, ['dismiss', 'delete'], true) && !empty($_POST['flag_ids'])) {
            if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'ec_flag_bulk')) {
                wp_die('Security check failed');
            }
            $ids = array_map('intval', $_POST['flag_ids']);
            $count = count($ids);

            if ($bulk_action === 'dismiss') {
                $repo->dismiss_multiple($ids);
                $msg = sprintf('%d flag(s) dismissed.', $count);
            } else {
                foreach ($ids as $id) {
                    $repo->delete($id);
                }
                $msg = sprintf('%d flag(s) deleted.', $count);
            }

            wp_safe_redirect(add_query_arg('ec_flag_msg', rawurlencode($msg), admin_url('admin.php?page=ec-flags')));
            exit;
        }
    }
}
