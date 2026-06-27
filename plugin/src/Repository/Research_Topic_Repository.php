<?php
namespace EsotericCurrent\Core\Repository;

class Research_Topic_Repository {
    private \wpdb $db;
    private string $table;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . 'ec_research_topics';
    }

    public function get_by_id(int $id): ?array {
        $row = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id),
            ARRAY_A
        );
        return $row ?: null;
    }

    public function get_by_title(string $title): ?array {
        $row = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE title = %s LIMIT 1", $title),
            ARRAY_A
        );
        return $row ?: null;
    }

    public function get_all(array $args = []): array {
        $where = '1=1';
        $params = [];

        if (!empty($args['status'])) {
            $where .= ' AND status = %s';
            $params[] = $args['status'];
        }

        $limit = !empty($args['limit']) ? min((int)$args['limit'], 100) : 50;
        $offset = !empty($args['offset']) ? (int)$args['offset'] : 0;

        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$where} ORDER BY priority ASC, created_at DESC LIMIT %d OFFSET %d",
            array_merge($params, [$limit, $offset])
        );

        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function get_due_topics(): array {
        return $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE status = %s AND (next_run_at IS NULL OR next_run_at <= %s) ORDER BY priority ASC",
                'active', current_time('mysql')
            ),
            ARRAY_A
        ) ?: [];
    }

    public function get_by_status(string $status): array {
        return $this->db->get_results(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE status = %s ORDER BY created_at DESC", $status),
            ARRAY_A
        ) ?: [];
    }

    public function create(array $data): ?int {
        $allowed = [
            'title', 'research_goal', 'included_concepts', 'excluded_concepts',
            'priority', 'languages', 'geography', 'lookback_days',
            'date_range_start', 'date_range_end', 'content_types',
            'run_frequency', 'selectivity', 'finding_limit', 'cost_limit',
            'browser_action_limit', 'status', 'next_run_at'
        ];
        $insert = array_intersect_key($data, array_flip($allowed));
        $insert['created_at'] = current_time('mysql');

        $this->db->insert($this->table, $insert);

        return $this->db->insert_id ?: null;
    }

    public function update(int $id, array $data): bool {
        $allowed = [
            'title', 'research_goal', 'included_concepts', 'excluded_concepts',
            'priority', 'languages', 'geography', 'lookback_days',
            'date_range_start', 'date_range_end', 'content_types',
            'run_frequency', 'selectivity', 'finding_limit', 'cost_limit',
            'browser_action_limit', 'status', 'next_run_at'
        ];
        $update = array_intersect_key($data, array_flip($allowed));

        if (empty($update)) {
            return false;
        }

        return (bool)$this->db->update($this->table, $update, ['id' => $id]);
    }

    public function update_next_run(int $id, string $next_run): bool {
        return (bool)$this->db->update(
            $this->table,
            ['next_run_at' => $next_run],
            ['id' => $id]
        );
    }

    public function advance_next_run(int $id): bool {
        $topic = $this->get_by_id($id);
        if ($topic === null) {
            return false;
        }

        $frequencies = [
            'hourly' => '+1 hour',
            'daily' => '+1 day',
            'weekly' => '+1 week',
            'monthly' => '+1 month',
        ];
        $interval = $frequencies[$topic['run_frequency']] ?? '+1 day';
        $next_run = gmdate('Y-m-d H:i:s', strtotime($interval));

        return $this->update_next_run($id, $next_run);
    }

    public function claim_due_topic(): ?array {
        $due = $this->get_due_topics();
        if (empty($due)) {
            return null;
        }

        $topic = $due[0];
        $this->update($topic['id'], ['last_run_at' => current_time('mysql')]);

        return $topic;
    }

    public function delete(int $id): bool {
        return (bool)$this->db->delete($this->table, ['id' => $id]);
    }
}
