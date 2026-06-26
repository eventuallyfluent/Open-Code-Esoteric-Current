<?php
namespace EsotericCurrent\Core\Admin;

class Dashboard_Page {
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $seed_message = '';
        if (isset($_POST['ec_seed_test_data']) && check_admin_referer('ec_seed_data')) {
            $seed_message = self::seed_test_data();
        }

        ?>
        <div class="wrap">
            <h1>Esoteric Current — Dashboard</h1>

            <?php if ($seed_message): ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html($seed_message); ?></p></div>
            <?php endif; ?>

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
                <div class="ec-card">
                    <h2>Test Data</h2>
                    <p>Generate sample topics and findings to test the frontend.</p>
                    <form method="post" style="margin-top:10px">
                        <?php wp_nonce_field('ec_seed_data'); ?>
                        <button type="submit" name="ec_seed_test_data" class="button button-secondary" onclick="return confirm('Add sample topics and published findings?')">Seed Test Data</button>
                    </form>
                </div>
            </div>
        </div>
        <style>
            .ec-dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 20px; }
            .ec-card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
            .ec-card h2 { margin-top: 0; font-size: 1.3em; }
        </style>
        <?php
    }

    private static function seed_test_data(): string {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $migration = new \EsotericCurrent\Core\Database\Migration();
        $migration->migrate_1_0_1();

        $topic_repo = new \EsotericCurrent\Core\Repository\Research_Topic_Repository();
        $existing = $topic_repo->get_all(['limit' => 1]);
        if (!empty($existing)) {
            return 'Test data already exists. To re-seed, delete the existing rows from the custom tables first.';
        }

        $topics = [
            ['title' => 'Recent Hermetic Alchemy Developments',      'research_goal' => 'Find new publications and discussions on hermetic alchemy practice',    'priority' => 'high',   'run_frequency' => 'weekly', 'status' => 'active', 'next_run_at' => current_time('mysql')],
            ['title' => 'Kabbalah in Contemporary Academia',         'research_goal' => 'Track recent academic papers and conferences on Kabbalah studies',      'priority' => 'normal', 'run_frequency' => 'weekly', 'status' => 'active', 'next_run_at' => current_time('mysql')],
            ['title' => 'Esoteric Christianity Movements',           'research_goal' => 'Surface books, events, and interviews on esoteric Christianity',        'priority' => 'normal', 'run_frequency' => 'weekly', 'status' => 'active', 'next_run_at' => current_time('mysql')],
            ['title' => 'Dzogchen and Tantra Cross-Pollination',     'research_goal' => 'Identify teachers, retreats, and publications bridging these traditions', 'priority' => 'low',   'run_frequency' => 'monthly','status' => 'active', 'next_run_at' => current_time('mysql')],
            ['title' => 'Ceremonial Magic Grimoire Republications',  'research_goal' => 'Track new editions and translations of historical grimoires',           'priority' => 'normal', 'run_frequency' => 'weekly', 'status' => 'active', 'next_run_at' => current_time('mysql')],
        ];
        foreach ($topics as $t) {
            $topic_repo->create($t);
        }

        $findings = [
            ['finding_type' => 'book',           'title' => 'The Kybalion: Centenary Edition',                              'url' => 'https://example.com/kybalion-centenary',         'source_url' => 'https://publisher.example.com/hermetic',              'excerpt' => 'A new annotated edition of the foundational Hermetic text.',                                                                           'relevance_score' => 92.00, 'confidence_score' => 88.00, 'classification' => 'hermeticism,text'],
            ['finding_type' => 'research-paper', 'title' => 'Alchemical Symbolism in Early Modern Scientific Manuscripts',  'url' => 'https://example.com/alchemical-symbolism',        'source_url' => 'https://journal.example.com/early-modern',             'excerpt' => 'New research examining alchemical diagrams influence on early modern scientific notation.',                                          'relevance_score' => 85.00, 'confidence_score' => 82.00, 'classification' => 'alchemy,history'],
            ['finding_type' => 'interview',      'title' => 'A Conversation with Rabbi David Aaron on Contemporary Kabbalah','url' => 'https://example.com/rabbi-aaron-interview',       'source_url' => 'https://podcast.example.com/esoteric-pod',             'excerpt' => 'An in-depth discussion on how Kabbalistic thought is reinterpreted for the 21st century.',                                            'relevance_score' => 78.00, 'confidence_score' => 75.00, 'classification' => 'kabbalah,contemporary'],
            ['finding_type' => 'event',          'title' => 'International Conference on Gnostic Studies 2026',            'url' => 'https://example.com/gnostic-conference-2026',     'source_url' => 'https://conference.example.com/gnostic',               'excerpt' => 'Annual gathering exploring Gnostic traditions from antiquity to modern revivals.',                                                    'relevance_score' => 88.00, 'confidence_score' => 85.00, 'classification' => 'gnosticism,conference'],
            ['finding_type' => 'podcast',        'title' => 'The Alchemy of Sound with John D. Smith',                    'url' => 'https://example.com/alchemy-of-sound',           'source_url' => 'https://podcast.example.com/occult-pod',               'excerpt' => 'Explores harmonic resonance, alchemical principle, and contemplative practice.',                                                       'relevance_score' => 72.00, 'confidence_score' => 70.00, 'classification' => 'alchemy,music,mysticism'],
            ['finding_type' => 'news-article',   'title' => 'New Translation of Corpus Hermeticum Published by Oxford Press','url' => 'https://example.com/corpus-hermeticum-oxford',    'source_url' => 'https://news.example.com/religious-studies',          'excerpt' => 'Oxford University Press releases a critical edition of the Corpus Hermeticum.',                                                        'relevance_score' => 95.00, 'confidence_score' => 90.00, 'classification' => 'hermeticism,text,translation'],
            ['finding_type' => 'organization',   'title' => 'Theosophical Society Centennial Archive Goes Online',        'url' => 'https://example.com/theosophical-archive',       'source_url' => 'https://archive.example.com/theosophy',                'excerpt' => 'Digitized collection of lectures, correspondence, and rare publications from the Theosophical Society\'s first century.',                'relevance_score' => 80.00, 'confidence_score' => 85.00, 'classification' => 'theosophy,archive'],
            ['finding_type' => 'development',    'title' => 'Sufi Mysticism in Modern Poetry: A Rising Trend',           'url' => 'https://example.com/sufi-poetry-trend',          'source_url' => 'https://arts.example.com/contemporary',                'excerpt' => 'Contemporary poets are drawing on Sufi mystical imagery and Rumi-inspired forms.',                                                       'relevance_score' => 65.00, 'confidence_score' => 72.00, 'classification' => 'sufism,literature'],
            ['finding_type' => 'resource',       'title' => 'Online Archive of Rosicrucian Manifestos',                   'url' => 'https://example.com/rosicrucian-manifestos',     'source_url' => 'https://archive.example.com/rosicrucian',              'excerpt' => 'Digital collection of the Fama Fraternitatis, Confessio Fraternitatis, and related Rosicrucian texts.',                                  'relevance_score' => 90.00, 'confidence_score' => 92.00, 'classification' => 'rosicrucianism,text,archive'],
            ['finding_type' => 'video',          'title' => 'Neoplatonism and the Western Esoteric Tradition (Lecture Series)','url' => 'https://example.com/neoplatonism-lectures',    'source_url' => 'https://video.example.com/academic',                   'excerpt' => 'Twelve-part series tracing Neoplatonic influence from Plotinus through Renaissance magic to modern esotericism.',                          'relevance_score' => 82.00, 'confidence_score' => 80.00, 'classification' => 'neoplatonism,lecture'],
            ['finding_type' => 'person',         'title' => 'Blavatsky Collection at the British Library',               'url' => 'https://example.com/blavatsky-profile',          'source_url' => 'https://library.example.com/collections',              'excerpt' => 'British Library announces newly catalogued collection of Blavatsky\'s personal correspondence and working notes.',                       'relevance_score' => 75.00, 'confidence_score' => 78.00, 'classification' => 'theosophy,person,archive'],
            ['finding_type' => 'book',           'title' => 'Practical Alchemy: A Modern Practitioner\'s Guide',         'url' => 'https://example.com/practical-alchemy-guide',    'source_url' => 'https://publisher.example.com/esoteric',               'excerpt' => 'A hands-on guide to laboratory alchemical practice bridging medieval texts with contemporary chemistry.',                                  'relevance_score' => 70.00, 'confidence_score' => 65.00, 'classification' => 'alchemy,practice'],
        ];

        $topic_ids = $topic_repo->get_all(['limit' => 5]);
        $now = current_time('mysql');

        foreach ($findings as $i => $f) {
            $f['content_hash'] = hash('sha256', $f['url'] . $now);
            $f['status'] = 'approved';
            $f['created_at'] = $now;

            $wpdb->insert($wpdb->prefix . 'ec_findings', $f);
            $finding_id = $wpdb->insert_id;

            if ($finding_id) {
                $topic_id = $topic_ids[$i % count($topic_ids)]['id'] ?? null;
                $wpdb->insert($wpdb->prefix . 'ec_editorial_queue', [
                    'source_type' => 'finding',
                    'source_id' => $finding_id,
                    'workflow_state' => 'published',
                    'topic_id' => $topic_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        return 'Seeded 5 research topics and 12 published findings. Check the homepage card grid!';
    }

    private static function render_pending_count(): void {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $queue_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$prefix}ec_editorial_queue");
        $published_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$prefix}ec_editorial_queue WHERE workflow_state = 'published'");
        $findings_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$prefix}ec_findings");
        $topics_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$prefix}ec_research_topics");
        echo "<p>Total queue: <strong>{$queue_count}</strong> | Published: <strong>{$published_count}</strong></p>";
        echo "<p>Total findings: <strong>{$findings_count}</strong> | Topics: <strong>{$topics_count}</strong></p>";
        echo "<p>Table prefix: <code>{$prefix}</code></p>";
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
