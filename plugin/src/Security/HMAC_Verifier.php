<?php
namespace EsotericCurrent\Core\Security;

class HMAC_Verifier {
    public static function sign(array $data, string $secret): string {
        $payload = wp_json_encode($data);
        return hash_hmac('sha256', $payload, $secret);
    }

    public static function verify(array $data, string $signature, string $secret): bool {
        $expected = self::sign($data, $secret);
        return hash_equals($expected, $signature);
    }

    public static function sign_request(string $method, string $path, string $body, string $timestamp, string $nonce, string $secret): string {
        $data = implode("\n", [$method, $path, $body, $timestamp, $nonce]);
        return hash_hmac('sha256', $data, $secret);
    }

    public static function verify_request(string $method, string $path, string $body, string $timestamp, string $nonce, string $signature, string $secret, int $max_age_seconds = 300): bool {
        if (abs(time() - (int)$timestamp) > $max_age_seconds) {
            return false;
        }
        $expected = self::sign_request($method, $path, $body, $timestamp, $nonce, $secret);
        return hash_equals($expected, $signature);
    }
}
