<?php
namespace EsotericCurrent\Core\Security;

class Nonce_Store {
    private const TABLE_HASH_OPTION = 'ec_consumed_nonces';

    public static function create_nonce(int $ttl_seconds = 300): array {
        $nonce = bin2hex(random_bytes(32));
        $hash = hash('sha256', $nonce);
        $expires_at = gmdate('Y-m-d H:i:s', time() + $ttl_seconds);
        return compact('nonce', 'hash', 'expires_at');
    }

    public static function consume(string $nonce_hash): bool {
        $consumed = get_option(self::TABLE_HASH_OPTION, []);
        $expires_at = gmdate('Y-m-d H:i:s', time());

        $consumed = array_filter($consumed, function($entry) use ($expires_at) {
            return $entry['expires_at'] > $expires_at;
        });

        if (isset($consumed[$nonce_hash])) {
            return false;
        }

        $consumed[$nonce_hash] = ['expires_at' => gmdate('Y-m-d H:i:s', time() + 300)];
        update_option(self::TABLE_HASH_OPTION, $consumed);
        return true;
    }
}
