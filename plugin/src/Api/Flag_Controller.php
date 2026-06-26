<?php
namespace EsotericCurrent\Core\Api;

use EsotericCurrent\Core\Security\Rate_Limiter;

class Flag_Controller {
    public static function register(): void {
        register_rest_route('ec/v1', '/finding/(?P<id>\d+)/flag', [
            'methods' => 'POST',
            'callback' => [self::class, 'handle'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_numeric($param) && (int) $param > 0;
                    },
                ],
            ],
        ]);
    }

    public static function handle(\WP_REST_Request $request): \WP_REST_Response {
        $finding_id = (int) $request->get_param('id');
        $reason = sanitize_text_field($request->get_json_params()['reason'] ?? '');
        $ip_address = $request->get_remote_addr();

        if (empty($reason)) {
            return new \WP_REST_Response(['error' => 'reason is required'], 400);
        }

        $allowed_reasons = ['low-quality', 'wrong-category', 'broken-link', 'other'];
        if (!in_array($reason, $allowed_reasons, true)) {
            return new \WP_REST_Response(['error' => 'Invalid reason. Allowed: ' . implode(', ', $allowed_reasons)], 400);
        }

        $rate_key = 'flag_' . $ip_address;
        if (!Rate_Limiter::check($rate_key, 5, 60)) {
            return new \WP_REST_Response(['error' => 'Rate limit exceeded. Try again later.'], 429);
        }

        $finding_repo = new \EsotericCurrent\Core\Repository\Finding_Repository();
        $finding = $finding_repo->get_by_id($finding_id);

        if ($finding === null) {
            return new \WP_REST_Response(['error' => 'Finding not found'], 404);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ec_finding_flags';
        $result = $wpdb->insert($table, [
            'finding_id' => $finding_id,
            'reason' => $reason,
            'ip_address' => $ip_address,
            'reviewed' => 0,
        ]);

        if ($result === false) {
            return new \WP_REST_Response(['error' => 'Failed to record flag'], 500);
        }

        return new \WP_REST_Response([
            'success' => true,
            'flag_id' => (int) $wpdb->insert_id,
        ], 201);
    }
}
