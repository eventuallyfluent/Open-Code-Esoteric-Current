<?php
namespace EsotericCurrent\Core\Tests;

use EsotericCurrent\Core\Repository\Source_Repository;
use PHPUnit\Framework\TestCase;

class Test_Source_Repository extends TestCase {
    private Source_Repository $repo;

    protected function setUp(): void {
        $this->repo = new Source_Repository();
    }

    public function test_get_by_id_returns_null_for_missing(): void {
        $result = $this->repo->get_by_id(99999);
        $this->assertNull($result);
    }

    public function test_get_all_returns_empty_array_when_none(): void {
        $results = $this->repo->get_all();
        $this->assertIsArray($results);
    }

    public function test_delete_nonexistent_returns_false(): void {
        $result = $this->repo->delete(99999);
        $this->assertFalse($result);
    }
}
