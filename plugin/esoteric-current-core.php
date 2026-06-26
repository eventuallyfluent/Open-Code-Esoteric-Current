<?php
/**
 * Plugin Name:         Esoteric Current Core
 * Plugin URI:          https://theesotericcurrent.com
 * Description:         Core plugin for The Esoteric Current — automated esoteric research publication.
 * Version:             1.0.0
 * Requires at least:   6.4
 * Requires PHP:        8.1
 * Author:              The Esoteric Current
 * Text Domain:         esoteric-current-core
 * Domain Path:         /languages
 */

defined('ABSPATH') || exit;

define('EC_CORE_VERSION', '1.0.0');
define('EC_CORE_FILE', __FILE__);
define('EC_CORE_DIR', plugin_dir_path(__FILE__));
define('EC_CORE_URL', plugin_dir_url(__FILE__));

if (file_exists(EC_CORE_DIR . 'vendor/autoload.php')) {
    require EC_CORE_DIR . 'vendor/autoload.php';
}

register_activation_hook(__FILE__, ['EsotericCurrent\Core\Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['EsotericCurrent\Core\Plugin', 'deactivate']);

add_action('plugins_loaded', ['EsotericCurrent\Core\Plugin', 'init']);
