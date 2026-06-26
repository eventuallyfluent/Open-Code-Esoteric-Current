<?php
namespace EsotericCurrent\Core\Api;

use EsotericCurrent\Core\Security\HMAC_Verifier;
use EsotericCurrent\Core\Security\Nonce_Store;
use EsotericCurrent\Core\Security\Rate_Limiter;

class Callback_Controller {
    public static function register(): void {
        register_rest_route('ec/v1', '/callback', [
            'methods' => 'POST',
            'callback' => [self::class, 'handle'],
            'permission_callback' => [self::class, 'check_auth'],
        ]);
    }

    public static function check_auth(\WP_REST_Request $request): bool {
        $secret = defined('EC_API_SECRET') ? EC_API_SECRET : '';
        if (empty($secret)) {
            return false;
        }

        $signature = $request->get_header('X-EC-Signature');
        $timestamp = $request->get_header('X-EC-Timestamp');
        $nonce = $request->get_header('X-EC-Nonce');
        $secret = ec_get_api_secret();

        if (empty($secret) || empty($signature) || empty($timestamp) || empty($nonce)) {
            return false;
        }

        return HMAC_Verifier::verify_request(
            'POST', '/ec/v1/callback', $request->get_body(),
            $timestamp, $nonce, $signature, $secret
        );
    }

    public static function handle(\WP_REST_Request $request): \WP_REST_Response {
        $nonce_hash = hash('sha256', $request->get_header('X-EC-Nonce'));

        if (!Nonce_Store::consume($nonce_hash)) {
            return new \WP_REST_Response(['error' => 'Nonce already consumed'], 429);
        }

        $body = $request->get_json_params();
        $run_uuid = $body['run_uuid'] ?? '';
        $status = $body['status'] ?? '';
        $findings = $body['findings'] ?? [];
        $error = $body['error'] ?? null;
        $cost = $body['estimated_cost'] ?? null;

        if (empty($run_uuid)) {
            return new \WP_REST_Response(['error' => 'run_uuid required'], 400);
        }

        $agent_run_repo = new \EsotericCurrent\Core\Repository\Agent_Run_Repository();
        $run = $agent_run_repo->get_by_uuid($run_uuid);

        if ($run === null) {
            return new \WP_REST_Response(['error' => 'Run not found'], 404);
        }

        if ($status === 'completed') {
            $agent_run_repo->complete_run($run_uuid, $findings, $cost);

            $finding_repo = new \EsotericCurrent\Core\Repository\Finding_Repository();
            foreach ($findings as $finding) {
                $finding_repo->create_from_agent($finding, $run['id'], $run['topic_id']);
            }

            if (!empty($run['topic_id'])) {
                $topic_repo = new \EsotericCurrent\Core\Repository\Research_Topic_Repository();
                $topic_repo->advance_next_run($run['topic_id']);
            }
        } else {
            $agent_run_repo->fail_run($run_uuid, $error['code'] ?? 'unknown', $error['message'] ?? 'Unknown error');
        }

        return new \WP_REST_Response(['accepted' => true]);
    }
}
