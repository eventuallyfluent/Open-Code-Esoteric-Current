<?php
namespace EsotericCurrent\Core\Repository;

class Agent_Run_Repository {
    private \wpdb $db;
    private string $table;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . 'ec_agent_runs';
    }

    public function get_by_id(int $id): ?array {
        $row = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id),
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
        if (!empty($args['topic_id'])) {
            $where .= ' AND topic_id = %d';
            $params[] = (int)$args['topic_id'];
        }

        $limit = !empty($args['limit']) ? min((int)$args['limit'], 100) : 50;
        $offset = !empty($args['offset']) ? (int)$args['offset'] : 0;

        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            array_merge($params, [$limit, $offset])
        );

        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function get_by_uuid(string $uuid): ?array {
        $row = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE run_uuid = %s", $uuid),
            ARRAY_A
        );
        return $row ?: null;
    }

    public function create_run(int $topic_id, string $trigger_type): array {
        $uuid = wp_generate_uuid4();

        $this->db->insert($this->table, [
            'run_uuid' => $uuid,
            'topic_id' => $topic_id,
            'trigger_type' => $trigger_type,
            'status' => 'pending',
            'created_at' => current_time('mysql'),
        ]);

        return [
            'id' => $this->db->insert_id,
            'run_uuid' => $uuid,
            'topic_id' => $topic_id,
            'trigger_type' => $trigger_type,
            'status' => 'pending',
        ];
    }

    public function set_lease(int $id, string $lease_token_hash, string $expires_at): bool {
        return (bool)$this->db->update(
            $this->table,
            [
                'lease_token_hash' => $lease_token_hash,
                'lease_expires_at' => $expires_at,
                'claimed_at' => current_time('mysql'),
                'status' => 'claimed',
            ],
            ['id' => $id]
        );
    }

    public function complete_run(string $run_uuid, array $findings = [], ?float $cost = null): bool {
        $data = [
            'status' => 'completed',
            'findings_json' => wp_json_encode($findings),
            'completed_at' => current_time('mysql'),
        ];
        if ($cost !== null) {
            $data['estimated_cost'] = $cost;
        }

        return (bool)$this->db->update($this->table, $data, ['run_uuid' => $run_uuid]);
    }

    public function fail_run(string $run_uuid, string $error_code, string $error_message): bool {
        return (bool)$this->db->update(
            $this->table,
            [
                'status' => 'failed',
                'error_code' => $error_code,
                'error_message' => $error_message,
                'completed_at' => current_time('mysql'),
            ],
            ['run_uuid' => $run_uuid]
        );
    }

    public function delete(int $id): bool {
        return (bool)$this->db->delete($this->table, ['id' => $id]);
    }
}
