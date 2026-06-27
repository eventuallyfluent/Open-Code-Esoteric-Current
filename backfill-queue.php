<?php
require_once dirname(__FILE__, 4) . '/wp-load.php';
global $wpdb;
$f = $wpdb->prefix . 'ec_findings';
$q = $wpdb->prefix . 'ec_editorial_queue';
$findings = $wpdb->get_results(
    "SELECT f.id, f.topic_id FROM $f f WHERE NOT EXISTS (SELECT 1 FROM $q WHERE source_type = 'finding' AND source_id = f.id)",
    ARRAY_A
);
$count = 0;
foreach ($findings as $row) {
    $wpdb->insert($q, [
        'source_type' => 'finding',
        'source_id' => (int)$row['id'],
        'workflow_state' => 'published',
        'topic_id' => (int)($row['topic_id'] ?? 0),
        'transitioned_at' => current_time('mysql'),
    ]);
    $count++;
}
echo "Backfilled $count findings";
