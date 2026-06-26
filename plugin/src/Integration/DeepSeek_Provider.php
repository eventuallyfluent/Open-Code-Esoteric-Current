<?php
namespace EsotericCurrent\Core\Integration;

class DeepSeek_Provider implements Model_Provider_Interface {
    private string $api_key;
    private string $model = 'deepseek-chat';
    private float $temperature = 0.7;
    private int $max_tokens = 4096;
    private int $timeout = 60;
    private array $usage = ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_cost' => 0.0];

    public const API_BASE = 'https://api.deepseek.com/v1';

    public function set_api_key(string $key): void {
        $this->api_key = $key;
    }

    public function set_model(string $model): void {
        $this->model = $model;
    }

    public function set_temperature(float $temperature): void {
        $this->temperature = max(0, min(2, $temperature));
    }

    public function set_max_tokens(int $tokens): void {
        $this->max_tokens = min($tokens, 8192);
    }

    public function get_usage(): array {
        return $this->usage;
    }

    public function chat(array $messages, array $options = []): array {
        $body = wp_json_encode(array_merge([
            'model' => $options['model'] ?? $this->model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? $this->temperature,
            'max_tokens' => $options['max_tokens'] ?? $this->max_tokens,
        ], $options['extra'] ?? []));

        $response = wp_remote_post(self::API_BASE . '/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => $body,
            'timeout' => $options['timeout'] ?? $this->timeout,
        ]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($data['usage'])) {
            $this->usage['prompt_tokens'] += $data['usage']['prompt_tokens'] ?? 0;
            $this->usage['completion_tokens'] += $data['usage']['completion_tokens'] ?? 0;
        }

        return $data;
    }
}
