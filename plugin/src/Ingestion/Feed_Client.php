<?php
namespace EsotericCurrent\Core\Ingestion;

class Feed_Client {
    private int $timeout = 30;
    private int $max_redirects = 5;

    public function set_timeout(int $seconds): void {
        $this->timeout = $seconds;
    }

    public function fetch(string $feed_url): array {
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
