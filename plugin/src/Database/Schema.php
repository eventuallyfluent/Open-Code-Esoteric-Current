<?php
namespace EsotericCurrent\Core\Database;

class Schema {
    private const OPTION_KEY = 'ec_schema_version';

    public static function migrate(): void {
        $migrations = new Migration();
        $versions = self::get_migration_versions();
        $current = self::current_version();

        foreach ($versions as $version) {
            if (version_compare($version, $current, '>')) {
                $method = 'migrate_' . str_replace('.', '_', $version);
                if (method_exists($migrations, $method)) {
                    $migrations->$method();
                    update_option(self::OPTION_KEY, $version);
                }
            }
        }
    }

    public static function current_version(): string {
        return get_option(self::OPTION_KEY, '0.0.0');
    }

    private static function get_migration_versions(): array {
        return ['1.0.0', '1.0.1', '1.2.0', '1.3.0', '1.4.0', '1.5.0'];
    }
}
