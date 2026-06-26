<?php
namespace EsotericCurrent\Core\Repository;

class Submission_Repository {
    private \wpdb $db;
    private string $table;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . 'ec_submissions';
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

        $limit = !empty($args['limit']) ? min((int)$args['limit'], 100) : 50;
        $offset = !empty($args['offset']) ? (int)$args['offset'] : 0;

        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            array_merge($params, [$limit, $offset])
        );

        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function count_recent_by_ip(string $ip_hash, int $minutes = 60): int {
        $since = gmdate('Y-m-d H:i:s', time() - ($minutes * 60));
        return (int)$this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE ip_hash = %s AND created_at >= %s",
                $ip_hash, $since
            )
        );
    }

    public function create(array $data): ?int {
        $allowed = ['url', 'title', 'description', 'submitter_name', 'submitter_email', 'content_type', 'ip_hash', 'status'];
        $insert = array_intersect_key($data, array_flip($allowed));

        $this->db->insert($this->table, $insert);

        return $this->db->insert_id ?: null;
    }

    public function update(int $id, array $data): bool {
        $allowed = ['url', 'title', 'description', 'submitter_name', 'submitter_email', 'content_type', 'status'];
        $update = array_intersect_key($data, array_flip($allowed));

        if (empty($update)) {
            return false;
        }

        return (bool)$this->db->update($this->table, $update, ['id' => $id]);
    }

    public function update_status(int $id, string $status): bool {
        return (bool)$this->db->update($this->table, ['status' => $status], ['id' => $id]);
    }

    public function delete(int $id): bool {
        return (bool)$this->db->delete($this->table, ['id' => $id]);
    }
}
