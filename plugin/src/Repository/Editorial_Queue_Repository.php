<?php
namespace EsotericCurrent\Core\Repository;

class Editorial_Queue_Repository {
    private \wpdb $db;
    private string $table;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . 'ec_editorial_queue';
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

        if (!empty($args['workflow_state'])) {
            $where .= ' AND workflow_state = %s';
            $params[] = $args['workflow_state'];
        }
        if (!empty($args['topic_id'])) {
            $where .= ' AND topic_id = %d';
            $params[] = (int)$args['topic_id'];
        }
        if (!empty($args['issue_id'])) {
            $where .= ' AND issue_id = %d';
            $params[] = (int)$args['issue_id'];
        }

        $limit = !empty($args['limit']) ? min((int)$args['limit'], 100) : 50;
        $offset = !empty($args['offset']) ? (int)$args['offset'] : 0;

        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            array_merge($params, [$limit, $offset])
        );

        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function get_by_state(string $state): array {
        return $this->db->get_results(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE workflow_state = %s ORDER BY created_at DESC", $state),
            ARRAY_A
        ) ?: [];
    }

    public function get_by_source(string $source_type, int $source_id): ?array {
        $row = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE source_type = %s AND source_id = %d",
                $source_type, $source_id
            ),
            ARRAY_A
        );
        return $row ?: null;
    }

    public function create(array $data): ?int {
        $allowed = ['source_type', 'source_id', 'workflow_state', 'previous_state', 'assigned_to', 'notes', 'topic_id', 'issue_id'];
        $insert = array_intersect_key($data, array_flip($allowed));

        if (empty($insert['workflow_state'])) {
            $insert['workflow_state'] = 'discovered';
        }

        $this->db->insert($this->table, $insert);

        return $this->db->insert_id ?: null;
    }

    public function transition(int $id, string $new_state): bool {
        $entry = $this->get_by_id($id);
        if ($entry === null) {
            return false;
        }

        return (bool)$this->db->update(
            $this->table,
            [
                'previous_state' => $entry['workflow_state'],
                'workflow_state' => $new_state,
                'transitioned_at' => current_time('mysql'),
            ],
            ['id' => $id]
        );
    }

    public function update(int $id, array $data): bool {
        $allowed = ['assigned_to', 'notes', 'topic_id', 'issue_id'];
        $update = array_intersect_key($data, array_flip($allowed));

        if (empty($update)) {
            return false;
        }

        return (bool)$this->db->update($this->table, $update, ['id' => $id]);
    }

    public function delete(int $id): bool {
        return (bool)$this->db->delete($this->table, ['id' => $id]);
    }
}
