<?php
namespace EsotericCurrent\Core\Api;

use EsotericCurrent\Core\Database\Schema;

class Health_Controller {
    public static function register(): void {
        register_rest_route('ec/v1', '/health', [
            'methods' => ['GET', 'POST'],
            'callback' => [self::class, 'handle'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function handle(\WP_REST_Request $request): \WP_REST_Response {
        return new \WP_REST_Response([
            'status' => 'ok',
            'version' => EC_CORE_VERSION,
            'schema_version' => Schema::current_version(),
        ]);
    }
}
