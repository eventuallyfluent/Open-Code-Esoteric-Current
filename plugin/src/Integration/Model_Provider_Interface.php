<?php
namespace EsotericCurrent\Core\Integration;

interface Model_Provider_Interface {
    public function chat(array $messages, array $options = []): array;
    public function set_api_key(string $key): void;
    public function set_model(string $model): void;
    public function set_temperature(float $temperature): void;
    public function set_max_tokens(int $tokens): void;
    public function get_usage(): array;
}
