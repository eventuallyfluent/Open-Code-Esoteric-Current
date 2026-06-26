<?php
namespace EsotericCurrent\Core\Database;

class Schema {
    private const OPTION_KEY = 'ec_schema_version';

    public static function migrate(): void {
        $current = self::current_version();
        $target = defined('EC_CORE_VERSION') ? EC_CORE_VERSION : '1.0.0';

        if (version_compare($current, $target, '>=')) {
            return;
        }

        $migrations = new Migration();
        $versions = self::get_migration_versions();

        foreach ($versions as $version) {
            if (version_compare($version, $current, '>')) {
                $migration_method = 'migrate_' . str_replace('.', '_', $version);
                if (method_exists($migrations, $migration_method)) {
                    $migrations->$migration_method();
                    update_option(self::OPTION_KEY, $version);
                }
            }
        }
    }

    public static function current_version(): string {
        return get_option(self::OPTION_KEY, '0.0.0');
    }

    private static function get_migration_versions(): array {
        return ['1.0.0'];
    }
}
