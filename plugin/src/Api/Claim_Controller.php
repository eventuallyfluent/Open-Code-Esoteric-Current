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

        $body = $request->get_json_params();
        $submitted_topics = $body['topics'] ?? [];

        $topic_repo = new \EsotericCurrent\Core\Repository\Research_Topic_Repository();
        $agent_run_repo = new \EsotericCurrent\Core\Repository\Agent_Run_Repository();

        $topic_ids = [];

        if (!empty($submitted_topics)) {
            foreach ($submitted_topics as $t) {
                $title = $t['title'] ?? '';
                $category = $t['category'] ?? '';
                if (empty($title)) { continue; }

                $existing = $topic_repo->get_by_title($title);
                if ($existing) {
                    $topic_ids[] = $existing['id'];
                } else {
                    $id = $topic_repo->create([
                        'title' => $title,
                        'research_goal' => $t['reason'] ?? '',
                        'included_concepts' => $category,
                        'priority' => 5,
                        'run_frequency' => 'daily',
                        'status' => 'active',
                        'next_run_at' => current_time('mysql'),
                    ]);
                    if ($id) { $topic_ids[] = $id; }
                }
            }
        } else {
            $due = $topic_repo->get_due_topics();
            $topic_ids = array_column($due, 'id');
        }

        if (empty($topic_ids)) {
            return new \WP_REST_Response(['claimed' => false, 'topics' => [], 'message' => 'No topics to claim']);
        }

        $runs = [];

        foreach ($topic_ids as $tid) {
            $topic = $topic_repo->get_by_id($tid);
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

        return new \WP_REST_Response([
            'claimed' => true,
            'topic_count' => count($runs),
            'runs' => $runs,
        ]);
    }
}
