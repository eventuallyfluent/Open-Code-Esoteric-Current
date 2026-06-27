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
        $secret = ec_get_api_secret();
        if (empty($secret)) { return false; }

        $signature = $request->get_header('X-EC-Signature');
        $timestamp = $request->get_header('X-EC-Timestamp');
        $nonce = $request->get_header('X-EC-Nonce');

        if (empty($signature) || empty($timestamp) || empty($nonce)) { return false; }

        $ip = $_SERVER['REMOTE_ADDR'] ?? $request->get_header('X-Forwarded-For') ?? 'unknown';
        $rate_key = 'claim_' . md5($ip);
        if (!Rate_Limiter::check($rate_key, 30, 60)) { return false; }

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

        $body = $request->get_json_params();
        $submitted = $body['topics'] ?? [];

        if (empty($submitted)) {
            return new \WP_REST_Response(['claimed' => false, 'runs' => [], 'message' => 'No topics submitted']);
        }

        global $wpdb;
        $rt = $wpdb->prefix . 'ec_research_topics';
        $found = [];

        foreach ($submitted as $t) {
            $title = trim($t['title'] ?? '');
            if ($title === '') { continue; }

            $existing = $wpdb->get_row(
                $wpdb->prepare("SELECT id FROM $rt WHERE title = %s LIMIT 1", $title),
                ARRAY_A
            );
            if ($existing) {
                $found[] = (int)$existing['id'];
            } else {
                $ok = $wpdb->insert($rt, [
                    'title' => $title,
                    'research_goal' => $t['reason'] ?? '',
                    'included_concepts' => $t['category'] ?? '',
                    'priority' => 'normal',
                    'run_frequency' => 'daily',
                    'status' => 'active',
                    'next_run_at' => current_time('mysql'),
                    'created_at' => current_time('mysql'),
                ]);
                if ($ok && $wpdb->insert_id) {
                    $found[] = $wpdb->insert_id;
                }
            }
        }

        if (empty($found)) {
            return new \WP_REST_Response(['claimed' => false, 'runs' => [], 'message' => 'No topics to claim']);
        }

        $agent_run_repo = new \EsotericCurrent\Core\Repository\Agent_Run_Repository();
        $topic_repo = new \EsotericCurrent\Core\Repository\Research_Topic_Repository();
        $runs = [];

        foreach ($found as $tid) {
            $topic = $topic_repo->get_by_id((int)$tid);
            if ($topic === null) { continue; }

            $lease_token = bin2hex(random_bytes(32));
            $lease_expires_at = gmdate('Y-m-d H:i:s', time() + 600);

            $run = $agent_run_repo->create_run($topic['id'], 'claim');
            $agent_run_repo->set_lease($run['id'], hash('sha256', $lease_token), $lease_expires_at);
            $topic_repo->update($topic['id'], ['last_run_at' => current_time('mysql')]);

            $runs[] = [
                'run_uuid' => $run['run_uuid'],
                'run_id' => $run['id'],
                'lease_token' => $lease_token,
                'lease_expires_at' => $lease_expires_at,
                'topic' => $topic,
            ];
        }

        return new \WP_REST_Response(['claimed' => true, 'topic_count' => count($runs), 'runs' => $runs]);
    }
}
