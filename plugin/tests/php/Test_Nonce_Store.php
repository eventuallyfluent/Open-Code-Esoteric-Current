<?php
namespace EsotericCurrent\Core\Tests;

use EsotericCurrent\Core\Security\Nonce_Store;
use PHPUnit\Framework\TestCase;

class Test_Nonce_Store extends TestCase {
    private string $option_key = 'ec_consumed_nonces';

    protected function setUp(): void {
        parent::setUp();
        delete_option($this->option_key);
    }

    public function test_create_nonce_returns_valid_structure(): void {
        $result = Nonce_Store::create_nonce(300);
        $this->assertArrayHasKey('nonce', $result);
        $this->assertArrayHasKey('hash', $result);
        $this->assertArrayHasKey('expires_at', $result);
        $this->assertSame(64, strlen($result['nonce']));
        $this->assertSame(64, strlen($result['hash']));
    }

    public function test_consume_valid_nonce_returns_true(): void {
        $nonce_hash = hash('sha256', 'test-valid-nonce');
        $result = Nonce_Store::consume($nonce_hash);
        $this->assertTrue($result);
    }

    public function test_consume_twice_returns_false(): void {
        $nonce_hash = hash('sha256', 'test-double-consume');
        Nonce_Store::consume($nonce_hash);
        $result = Nonce_Store::consume($nonce_hash);
        $this->assertFalse($result);
    }
}
