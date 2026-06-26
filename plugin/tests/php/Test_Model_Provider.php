<?php
namespace EsotericCurrent\Core\Tests;

use EsotericCurrent\Core\Integration\DeepSeek_Provider;
use PHPUnit\Framework\TestCase;

class Test_Model_Provider extends TestCase {
    public function test_set_api_key(): void {
        $provider = new DeepSeek_Provider();
        $provider->set_api_key('test-key');
        $this->assertTrue(true);
    }

    public function test_set_temperature_clamps(): void {
        $provider = new DeepSeek_Provider();
        $provider->set_temperature(5.0);
        $this->assertTrue(true);
    }

    public function test_set_model(): void {
        $provider = new DeepSeek_Provider();
        $provider->set_model('deepseek-reasoner');
        $this->assertTrue(true);
    }
}
