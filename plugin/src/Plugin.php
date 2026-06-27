<?php
namespace EsotericCurrent\Core;

use EsotericCurrent\Core\Admin\Admin_Menu;
use EsotericCurrent\Core\Admin\Settings_Page;
use EsotericCurrent\Core\Api\Article_Controller;
use EsotericCurrent\Core\Api\Callback_Controller;
use EsotericCurrent\Core\Api\Claim_Controller;
use EsotericCurrent\Core\Api\Flag_Controller;
use EsotericCurrent\Core\Api\Health_Controller;
use EsotericCurrent\Core\Blocks\Block_Registrar;
use EsotericCurrent\Core\Frontend\Finding_Router;
use EsotericCurrent\Core\Database\Schema;

class Plugin {
    private static ?Plugin $instance = null;
    private bool $initialized = false;

    public static function init(): void {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        self::$instance->initialize();
    }

    private function initialize(): void {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;
        add_action('init', [Schema::class, 'migrate']);
        add_action('rest_api_init', [Health_Controller::class, 'register']);
        add_action('rest_api_init', [Claim_Controller::class, 'register']);
        add_action('rest_api_init', [Flag_Controller::class, 'register']);
        add_action('rest_api_init', [Article_Controller::class, 'register']);
        add_action('rest_api_init', [Callback_Controller::class, 'register']);
        add_action('admin_menu', [Admin_Menu::class, 'register']);
        add_action('admin_init', [Settings_Page::class, 'register_settings']);
        add_action('init', [Finding_Router::class, 'init']);
        add_action('init', [Block_Registrar::class, 'register_all']);
        add_action('init', [self::class, 'register_taxonomy_rewrites']);
        add_action('init', [self::class, 'register_shortcodes']);
        add_action('init', [Database\Migration::class, 'maybe_flush_rewrite_rules']);
        add_action('wp_head', [self::class, 'dynamic_type_css']);
    }

    public static function activate(): void {
        Schema::migrate();
    }

    public static function deactivate(): void {
    }

    public static function register_taxonomy_rewrites(): void {
        add_rewrite_tag('%ec_topic%', '([^/]+)');
        add_rewrite_tag('%ec_resource_type%', '([^/]+)');
        add_rewrite_rule('^topic/([^/]+)/?$', 'index.php?pagename=catalogue&ec_topic=$matches[1]', 'top');
        add_rewrite_rule('^type/([^/]+)/?$', 'index.php?pagename=catalogue&ec_resource_type=$matches[1]', 'top');
        add_filter('query_vars', function (array $vars) {
            $vars[] = 'ec_topic';
            $vars[] = 'ec_resource_type';
            return $vars;
        });
    }

    public static function register_shortcodes(): void {
        add_shortcode('ec_topic_bar', [self::class, 'render_topic_bar']);
        add_shortcode('ec_resource_type_bar', [self::class, 'render_resource_type_bar']);
    }

    public static function render_topic_bar(): string {
        $repo = new \EsotericCurrent\Core\Repository\Term_Repository();
        $groups = $repo->get_top_level_terms('ec_topic');
        ob_start();
        ?>
        <div class="ec-topics-bar">
            <div class="ec-container">
                <div class="ec-topics-label">Browse by Topic</div>
                <div class="ec-topics-list">
                    <?php foreach ($groups as $group): ?>
                        <?php $children = $repo->get_term_children((int)$group['term_taxonomy_id']); ?>
                        <?php foreach ($children as $child): ?>
                            <a href="<?php echo esc_url(home_url('/topic/' . $child['slug'] . '/')); ?>" class="ec-topic-chip">
                                <?php echo esc_html($child['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function render_resource_type_bar(): string {
        $repo = new \EsotericCurrent\Core\Repository\Term_Repository();
        $types = $repo->get_terms('ec_resource_type', ['parent' => 0]);
        ob_start();
        ?>
        <div class="ec-topics-bar" style="margin-top:0.5rem">
            <div class="ec-container">
                <div class="ec-topics-label">Browse by Type</div>
                <div class="ec-topics-list">
                    <?php foreach ($types as $type): ?>
                        <a href="<?php echo esc_url(home_url('/type/' . $type['slug'] . '/')); ?>" class="ec-topic-chip">
                            <?php echo esc_html($type['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function dynamic_type_css(): void {
        $types = [
            'news-article' => '#c4a45a', 'book' => '#4a1a2e', 'event' => '#0f1b2d',
            'interview' => '#0f1b2d', 'research-paper' => '#d4cfc4', 'podcast' => '#c4a45a',
            'video' => '#4a1a2e', 'organization' => '#0f1b2d', 'person' => '#d4cfc4',
            'resource' => '#c4a45a', 'development' => '#4a1a2e',
        ];
        $colors = ['#c4a45a', '#4a1a2e', '#0f1b2d', '#d4cfc4', '#6b4c3b', '#2d5a4e', '#5a3d6b', '#8b4513', '#3d5a80', '#6b3a5a'];
        $i = 0;
        echo '<style id="ec-type-colors">';
        foreach ($types as $slug => $color) {
            echo '.ec-feed-type--' . $slug . '{color:' . $color . '}';
        }
        $repo = new \EsotericCurrent\Core\Repository\Term_Repository();
        $type_terms = $repo->get_terms('ec_resource_type', ['parent' => 0]);
        foreach ($type_terms as $t) {
            $slug = sanitize_title($t['slug']);
            if (!isset($types[$slug])) {
                $c = $colors[$i % count($colors)];
                echo '.ec-feed-type--' . $slug . '{color:' . $c . '}';
                $i++;
            }
        }
        echo '</style>';
    }
}
