<?php
namespace EsotericCurrent\Core\Api;

use EsotericCurrent\Core\Repository\Research_Topic_Repository;

class Topics_Controller {
    public static function register(): void {
        register_rest_route('ec/v1', '/topics', [
            'methods' => 'GET',
            'callback' => [self::class, 'handle'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function handle(\WP_REST_Request $request): \WP_REST_Response {
        $repo = new Research_Topic_Repository();
        $topics = $repo->get_all(['status' => 'active']);
        $result = array_map(function ($t) {
            return [
                'id' => (int) $t['id'],
                'title' => $t['title'],
                'research_goal' => $t['research_goal'],
                'priority' => (int) $t['priority'],
            ];
        }, $topics);
        return new \WP_REST_Response($result);
    }
}
