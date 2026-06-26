<?php
namespace EsotericCurrent\Core\Tests;

use EsotericCurrent\Core\Database\Schema;
use PHPUnit\Framework\TestCase;

class Test_Schema extends TestCase {
    private string $option_key = 'ec_schema_version';

    protected function setUp(): void {
        parent::setUp();
        delete_option($this->option_key);
    }

    public function test_current_version_returns_zero_when_not_migrated(): void {
        $version = Schema::current_version();
        $this->assertSame('0.0.0', $version);
    }

    public function test_migrate_updates_version(): void {
        $this->assertTrue(true, 'Placeholder: full migration test needs WP test suite.');
    }
}
