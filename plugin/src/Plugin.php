<?php
namespace EsotericCurrent\Core;

use EsotericCurrent\Core\Admin\Admin_Menu;
use EsotericCurrent\Core\Admin\Settings_Page;
use EsotericCurrent\Core\Api\Article_Controller;
use EsotericCurrent\Core\Api\Callback_Controller;
use EsotericCurrent\Core\Api\Claim_Controller;
use EsotericCurrent\Core\Api\Health_Controller;
use EsotericCurrent\Core\Blocks\Block_Registrar;
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
        add_action('rest_api_init', [Article_Controller::class, 'register']);
        add_action('rest_api_init', [Callback_Controller::class, 'register']);
        add_action('admin_menu', [Admin_Menu::class, 'register']);
        add_action('admin_init', [Settings_Page::class, 'register_settings']);
        add_action('init', [Block_Registrar::class, 'register_all']);
    }

    public static function activate(): void {
        Schema::migrate();
    }

    public static function deactivate(): void {
    }
}
