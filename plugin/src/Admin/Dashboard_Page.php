<?php
namespace EsotericCurrent\Core\Admin;

class Dashboard_Page {
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        ?>
        <div class="wrap">
            <h1>Esoteric Current — Dashboard</h1>
            <div class="ec-dashboard-grid">
                <div class="ec-card">
                    <h2>Pending Review</h2>
                    <?php self::render_pending_count(); ?>
                </div>
                <div class="ec-card">
                    <h2>Last Agent Run</h2>
                    <?php self::render_last_run(); ?>
                </div>
                <div class="ec-card">
                    <h2>Active Sources</h2>
                    <?php self::render_active_sources(); ?>
                </div>
                <div class="ec-card">
                    <h2>System Health</h2>
                    <?php self::render_health(); ?>
                </div>
            </div>
        </div>
        <style>
            .ec-dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
            .ec-card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
            .ec-card h2 { margin-top: 0; font-size: 1.3em; }
        </style>
        <?php
    }

    private static function render_pending_count(): void {
        $queue_repo = new \EsotericCurrent\Core\Repository\Editorial_Queue_Repository();
        $count = count($queue_repo->get_by_state('awaiting_review'));
        echo "<p>Findings awaiting review: <strong>{$count}</strong></p>";
    }

    private static function render_last_run(): void {
        $run_repo = new \EsotericCurrent\Core\Repository\Agent_Run_Repository();
        $runs = $run_repo->get_all(['limit' => 1]);
        if (!empty($runs)) {
            $run = $runs[0];
            echo "<p>Status: <strong>" . esc_html($run['status']) . "</strong></p>";
            echo "<p>Completed: " . esc_html($run['completed_at'] ?? 'N/A') . "</p>";
        } else {
            echo "<p>No runs yet.</p>";
        }
    }

    private static function render_active_sources(): void {
        $source_repo = new \EsotericCurrent\Core\Repository\Source_Repository();
        $sources = $source_repo->get_all(['status' => 'active']);
        echo "<p>Active sources: <strong>" . count($sources) . "</strong></p>";
    }

    private static function render_health(): void {
        $version = \EsotericCurrent\Core\Database\Schema::current_version();
        echo "<p>Schema version: <strong>" . esc_html($version) . "</strong></p>";
    }
}
