<?php
namespace EsotericCurrent\Core\Security;

class Rate_Limiter {
    public static function check(string $key, int $max_attempts = 10, int $window_seconds = 60): bool {
        $option_key = 'ec_rate_limit_' . md5($key);
        $data = get_option($option_key, ['count' => 0, 'reset_at' => time() + $window_seconds]);

        if (time() > $data['reset_at']) {
            $data = ['count' => 0, 'reset_at' => time() + $window_seconds];
            update_option($option_key, $data);
        }

        return $data['count'] < $max_attempts;
    }

    public static function increment(string $key): int {
        $option_key = 'ec_rate_limit_' . md5($key);
        $data = get_option($option_key, ['count' => 0, 'reset_at' => time() + 60]);

        if (time() > $data['reset_at']) {
            $data = ['count' => 0, 'reset_at' => time() + 60];
        }

        $data['count']++;
        update_option($option_key, $data);

        return $data['count'];
    }
}
