<?php
namespace EsotericCurrent\Core\Tests;

use EsotericCurrent\Core\Ingestion\Duplicate_Detector;
use PHPUnit\Framework\TestCase;

class Test_Duplicate_Detector extends TestCase {
    public function test_content_hash_is_deterministic(): void {
        $hash1 = Duplicate_Detector::content_hash('hello world');
        $hash2 = Duplicate_Detector::content_hash('hello world');
        $this->assertSame($hash1, $hash2);
    }

    public function test_normalize_url_removes_trailing_slash(): void {
        $url1 = Duplicate_Detector::normalize_url('https://example.com/page/');
        $url2 = Duplicate_Detector::normalize_url('https://example.com/page');
        $this->assertSame($url1, $url2);
    }

    public function test_normalize_url_lowercases_host(): void {
        $url = Duplicate_Detector::normalize_url('https://Example.COM/Path');
        $this->assertStringContainsString('example.com', $url);
    }

    public function test_url_hash_deterministic(): void {
        $hash1 = Duplicate_Detector::url_hash('https://example.com/page');
        $hash2 = Duplicate_Detector::url_hash('https://example.com/page');
        $this->assertSame($hash1, $hash2);
    }
}
