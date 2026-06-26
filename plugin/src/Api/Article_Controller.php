<?php
namespace EsotericCurrent\Core\Api;

use EsotericCurrent\Core\Security\HMAC_Verifier;
use EsotericCurrent\Core\Security\Nonce_Store;
use EsotericCurrent\Core\Security\Rate_Limiter;

class Article_Controller {
    public static function register(): void {
        register_rest_route('ec/v1', '/article', [
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

        $rate_key = 'article_' . $request->get_remote_addr();
        if (!Rate_Limiter::check($rate_key, 10, 60)) {
            return false;
        }

        return HMAC_Verifier::verify_request(
            'POST', '/ec/v1/article', $request->get_body(),
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
        $title = sanitize_text_field($body['title'] ?? '');
        $content = wp_kses_post($body['content'] ?? '');
        $excerpt = sanitize_textarea_field($body['excerpt'] ?? '');
        $tags = $body['tags'] ?? [];
        $source_category = sanitize_text_field($body['source_category'] ?? '');
        $research_notes = $body['research_notes'] ?? '';

        if (empty($title) || empty($content)) {
            return new \WP_REST_Response(['error' => 'title and content are required'], 400);
        }

        $post_id = wp_insert_post([
            'post_title' => $title,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
            'post_status' => 'draft',
            'post_type' => 'post',
        ], true);

        if (is_wp_error($post_id)) {
            return new \WP_REST_Response(['error' => $post_id->get_error_message()], 500);
        }

        if (!empty($tags)) {
            $tag_names = array_map('sanitize_text_field', (array) $tags);
            wp_set_post_terms($post_id, $tag_names, 'post_tag');
        }

        if (!empty($source_category)) {
            update_post_meta($post_id, '_ec_source_category', $source_category);
        }

        if (!empty($research_notes)) {
            update_post_meta($post_id, '_ec_research_notes', $research_notes);
        }

        return new \WP_REST_Response([
            'success' => true,
            'post_id' => $post_id,
            'edit_url' => admin_url('post.php?post=' . $post_id . '&action=edit'),
        ]);
    }
}
