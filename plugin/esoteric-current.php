<?php
/**
 * Plugin Name:         Esoteric Current Core
 * Plugin URI:          https://theesotericcurrent.com
 * Description:         Core plugin for The Esoteric Current — automated esoteric research publication.
 * Version:             1.0.1
 * Requires at least:   6.4
 * Requires PHP:        8.1
 * Author:              The Esoteric Current
 * Text Domain:         esoteric-current
 * Domain Path:         /languages
 */

defined('ABSPATH') || exit;

define('EC_CORE_VERSION', '1.0.1');
define('EC_CORE_FILE', __FILE__);
define('EC_CORE_DIR', plugin_dir_path(__FILE__));
define('EC_CORE_URL', plugin_dir_url(__FILE__));

if (version_compare(PHP_VERSION, '8.1', '<')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p><strong>Esoteric Current Core</strong> requires PHP 8.1 or higher. You are running ' . esc_html(PHP_VERSION) . '.</p></div>';
    });
    return;
}

if (file_exists(EC_CORE_DIR . 'vendor/autoload.php')) {
    require EC_CORE_DIR . 'vendor/autoload.php';
}

register_deactivation_hook(__FILE__, ['EsotericCurrent\Core\Plugin', 'deactivate']);

add_action('plugins_loaded', ['EsotericCurrent\Core\Plugin', 'init']);

function ec_get_api_secret(): string {
    if (defined('EC_API_SECRET')) {
        return EC_API_SECRET;
    }
    $option = get_option('esoteric_current_core_api_secret', '');
    return is_string($option) ? $option : '';
}

register_activation_hook(__FILE__, function () {
    try {
        if (!get_option('esoteric_current_core_api_secret')) {
            update_option('esoteric_current_core_api_secret', bin2hex(random_bytes(32)));
        }
        \EsotericCurrent\Core\Plugin::activate();
    } catch (\Throwable $e) {
        $msg = $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
        update_option('ec_activation_error', $msg);
        add_action('admin_notices', function () use ($msg) {
            echo '<div class="notice notice-error"><p><strong>Esoteric Current:</strong> ' . esc_html($msg) . '</p></div>';
        });
    }
});
