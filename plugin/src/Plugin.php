<?php
namespace EsotericCurrent\Core;

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
    }

    public static function activate(): void {
        Schema::migrate();
    }

    public static function deactivate(): void {
    }
}
