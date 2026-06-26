<?php
namespace EsotericCurrent\Core\Tests;

use EsotericCurrent\Core\Security\HMAC_Verifier;
use PHPUnit\Framework\TestCase;

class Test_HMAC_Verifier extends TestCase {
    private string $secret = 'test-secret-key';

    public function test_sign_and_verify_match(): void {
        $data = ['run_uuid' => 'abc-123', 'action' => 'claim'];
        $sig = HMAC_Verifier::sign($data, $this->secret);
        $this->assertTrue(HMAC_Verifier::verify($data, $sig, $this->secret));
    }

    public function test_wrong_secret_fails(): void {
        $data = ['run_uuid' => 'abc-123'];
        $sig = HMAC_Verifier::sign($data, $this->secret);
        $this->assertFalse(HMAC_Verifier::verify($data, $sig, 'wrong-secret'));
    }

    public function test_tampered_data_fails(): void {
        $data = ['run_uuid' => 'abc-123'];
        $sig = HMAC_Verifier::sign($data, $this->secret);
        $tampered = ['run_uuid' => 'abc-999'];
        $this->assertFalse(HMAC_Verifier::verify($tampered, $sig, $this->secret));
    }

    public function test_expired_request_fails(): void {
        $old_timestamp = (string)(time() - 600);
        $result = HMAC_Verifier::verify_request(
            'POST', '/ec/v1/claim', '{}',
            $old_timestamp, 'test-nonce', 'test-sig', $this->secret
        );
        $this->assertFalse($result);
    }
}
