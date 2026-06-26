<?php
namespace EsotericCurrent\Core\Admin;

class Settings_Page {
    private const OPTION_GROUP = 'esoteric_current_core_settings';

    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        ?>
        <div class="wrap">
            <h1>Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields(self::OPTION_GROUP);
                do_settings_sections(self::OPTION_GROUP);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public static function register_settings(): void {
        register_setting(self::OPTION_GROUP, 'esoteric_current_core_default_finding_limit', ['type' => 'integer', 'default' => 25]);
        register_setting(self::OPTION_GROUP, 'esoteric_current_core_default_cost_limit', ['type' => 'number', 'default' => 0.50]);
        register_setting(self::OPTION_GROUP, 'esoteric_current_core_model_provider', ['type' => 'string', 'default' => 'deepseek']);

        if (get_option('esoteric_current_core_api_secret', '') === '') {
            update_option('esoteric_current_core_api_secret', bin2hex(random_bytes(32)));
        }
        register_setting(self::OPTION_GROUP, 'esoteric_current_core_api_secret', ['type' => 'string']);

        add_settings_section('ec_api', 'API Access', '__return_empty_string', self::OPTION_GROUP);
        add_settings_field('api_secret', 'API Secret Key', function () {
            $secret = get_option('esoteric_current_core_api_secret', '');
            echo '<input name="esoteric_current_core_api_secret" id="ec-api-secret" type="text" value="' . esc_attr($secret) . '" class="regular-text" style="font-family:monospace" />';
            echo '<button type="button" class="button" onclick="document.getElementById(\'ec-api-secret\').value = Array.from(crypto.getRandomValues(new Uint8Array(32))).map(b => b.toString(16).padStart(2,\'0\')).join(\'\')" style="margin-left:6px">Regenerate</button>';
            echo '<p class="description">Used by the agent worker to authenticate API calls. Copy this to your GitHub Actions secret as <code>WORDPRESS_API_SECRET</code>.</p>';
        }, self::OPTION_GROUP, 'ec_api');

        add_settings_section('ec_defaults', 'Default Limits', '__return_empty_string', self::OPTION_GROUP);
        add_settings_field('default_finding_limit', 'Default Finding Limit', function () {
            echo '<input name="esoteric_current_core_default_finding_limit" type="number" value="' . esc_attr(get_option('esoteric_current_core_default_finding_limit', 25)) . '" class="small-text" />';
        }, self::OPTION_GROUP, 'ec_defaults');
        add_settings_field('default_cost_limit', 'Default Cost Limit ($)', function () {
            echo '<input name="esoteric_current_core_default_cost_limit" type="number" step="0.01" value="' . esc_attr(get_option('esoteric_current_core_default_cost_limit', 0.50)) . '" class="small-text" />';
        }, self::OPTION_GROUP, 'ec_defaults');
        add_settings_field('model_provider', 'Model Provider', function () {
            echo '<select name="esoteric_current_core_model_provider">';
            foreach (['deepseek', 'openai', 'anthropic'] as $p) {
                echo '<option value="' . esc_attr($p) . '" ' . selected(get_option('esoteric_current_core_model_provider', 'deepseek'), $p, false) . '>' . esc_html(ucfirst($p)) . '</option>';
            }
            echo '</select>';
        }, self::OPTION_GROUP, 'ec_defaults');
    }
}
