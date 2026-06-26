<?php
namespace EsotericCurrent\Core\Ingestion;

class Feed_Client {
    private int $timeout = 30;
    private int $max_redirects = 5;

    public function set_timeout(int $seconds): void {
        $this->timeout = $seconds;
    }

    private function validate_url(string $url): bool {
        $parsed = wp_parse_url($url);
        if ($parsed === false || empty($parsed['host'])) {
            return false;
        }

        $host = $parsed['host'];
        $ip = gethostbyname($host);

        if ($ip === $host && !filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return true;
            }
            if ($ip === '0.0.0.0' || $ip === '::1') {
                return false;
            }
            return false;
        }

        return true;
    }

    public function fetch(string $feed_url): array {
        if (!$this->validate_url($feed_url)) {
            return ['success' => false, 'error' => 'URL rejected: invalid or private address'];
        }

        $response = wp_remote_get($feed_url, [
            'timeout' => $this->timeout,
            'redirection' => $this->max_redirects,
            'user_agent' => 'EsotericCurrent/1.0',
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return ['success' => false, 'error' => "HTTP {$code}"];
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return ['success' => false, 'error' => 'Empty response body'];
        }

        return [
            'success' => true,
            'body' => $body,
            'headers' => wp_remote_retrieve_headers($response),
        ];
    }
}
