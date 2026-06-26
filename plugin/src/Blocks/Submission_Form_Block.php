<?php
namespace EsotericCurrent\Core\Blocks;

use EsotericCurrent\Core\Security\Rate_Limiter;

class Submission_Form_Block {
    public static function attributes(): array {
        return [
            'success_message' => ['type' => 'string', 'default' => 'Thank you for your submission. It will be reviewed shortly.'],
        ];
    }

    public static function render(array $attributes): string {
        $message = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ec_submit_url'])) {
            $message = self::handle_submission($attributes);
        }

        ob_start();
        ?>
        <div class="ec-submission-form">
            <?php if (!empty($message)): ?>
                <div class="ec-submission-message"><?php echo wp_kses_post($message); ?></div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(get_permalink()); ?>">
                <?php wp_nonce_field('ec_submission', 'ec_submission_nonce'); ?>
                <p>
                    <label for="ec_submit_url">URL *</label>
                    <input type="url" name="ec_submit_url" id="ec_submit_url" required class="widefat" />
                </p>
                <p>
                    <label for="ec_submit_title">Title</label>
                    <input type="text" name="ec_submit_title" id="ec_submit_title" class="widefat" />
                </p>
                <p>
                    <label for="ec_submit_description">Description</label>
                    <textarea name="ec_submit_description" id="ec_submit_description" rows="4" class="widefat"></textarea>
                </p>
                <p>
                    <label for="ec_submit_name">Your Name</label>
                    <input type="text" name="ec_submit_name" id="ec_submit_name" class="widefat" />
                </p>
                <p>
                    <label for="ec_submit_email">Your Email</label>
                    <input type="email" name="ec_submit_email" id="ec_submit_email" class="widefat" />
                </p>
                <p>
                    <button type="submit" class="button button-primary">Submit</button>
                </p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function handle_submission(array $attributes): string {
        if (!wp_verify_nonce($_POST['ec_submission_nonce'] ?? '', 'ec_submission')) {
            return '<p class="ec-error">Security check failed. Please try again.</p>';
        }

        $url = esc_url_raw($_POST['ec_submit_url']);
        if (empty($url) || !wp_http_validate_url($url)) {
            return '<p class="ec-error">Please enter a valid URL.</p>';
        }

        $ip_hash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '');
        if (!Rate_Limiter::check('submission_' . $ip_hash, 5, 3600)) {
            return '<p class="ec-error">Too many submissions. Please try again later.</p>';
        }
        Rate_Limiter::increment('submission_' . $ip_hash);

        $sub_repo = new \EsotericCurrent\Core\Repository\Submission_Repository();
        $sub_id = $sub_repo->create([
            'url' => $url,
            'title' => sanitize_text_field($_POST['ec_submit_title'] ?? ''),
            'description' => sanitize_textarea_field($_POST['ec_submit_description'] ?? ''),
            'submitter_name' => sanitize_text_field($_POST['ec_submit_name'] ?? ''),
            'submitter_email' => sanitize_email($_POST['ec_submit_email'] ?? ''),
            'ip_hash' => $ip_hash,
            'status' => 'pending',
        ]);

        if ($sub_id) {
            $queue_repo = new \EsotericCurrent\Core\Repository\Editorial_Queue_Repository();
            $queue_repo->create([
                'source_type' => 'submission',
                'source_id' => $sub_id,
                'workflow_state' => 'discovered',
            ]);
        }

        return '<p class="ec-success">' . esc_html($attributes['success_message']) . '</p>';
    }
}
