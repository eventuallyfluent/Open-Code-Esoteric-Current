<?php
namespace EsotericCurrent\Core\Api;

use EsotericCurrent\Core\Security\HMAC_Verifier;
use EsotericCurrent\Core\Security\Nonce_Store;
use EsotericCurrent\Core\Security\Rate_Limiter;

class Claim_Controller {
    public static function register(): void {
        register_rest_route('ec/v1', '/claim', [
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

        if (empty($signature) || empty($timestamp) || empty($nonce)) {
            return false;
        }

        $rate_key = 'claim_' . $request->get_remote_addr();
        if (!Rate_Limiter::check($rate_key, 30, 60)) {
            return false;
        }

        return HMAC_Verifier::verify_request(
            'POST', '/ec/v1/claim', $request->get_body(),
            $timestamp, $nonce, $signature, $secret
        );
    }

    public static function handle(\WP_REST_Request $request): \WP_REST_Response {
        $nonce = $request->get_header('X-EC-Nonce');
        $nonce_hash = hash('sha256', $nonce);

        if (!Nonce_Store::consume($nonce_hash)) {
            return new \WP_REST_Response(['error' => 'Nonce already consumed'], 429);
        }

        $topic_repo = new \EsotericCurrent\Core\Repository\Research_Topic_Repository();
        $topic = $topic_repo->claim_due_topic();

        if ($topic === null) {
            return new \WP_REST_Response(['claimed' => false, 'message' => 'No due topics']);
        }

        $lease_token = bin2hex(random_bytes(32));
        $lease_expires_at = gmdate('Y-m-d H:i:s', time() + 600);

        $agent_run_repo = new \EsotericCurrent\Core\Repository\Agent_Run_Repository();
        $run = $agent_run_repo->create_run($topic['id'], 'claim');
        $agent_run_repo->set_lease($run['id'], hash('sha256', $lease_token), $lease_expires_at);

        return new \WP_REST_Response([
            'claimed' => true,
            'run_uuid' => $run['run_uuid'],
            'run_id' => $run['id'],
            'lease_token' => $lease_token,
            'lease_expires_at' => $lease_expires_at,
            'topic' => $topic,
        ]);
    }
}
