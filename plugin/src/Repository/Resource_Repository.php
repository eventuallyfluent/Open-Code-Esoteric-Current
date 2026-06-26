<?php
namespace EsotericCurrent\Core\Repository;

class Resource_Repository {
    private \wpdb $db;
    private string $table;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . 'ec_resources';
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
        if (!empty($args['resource_type'])) {
            $where .= ' AND resource_type = %s';
            $params[] = $args['resource_type'];
        }

        $limit = !empty($args['limit']) ? min((int)$args['limit'], 100) : 50;
        $offset = !empty($args['offset']) ? (int)$args['offset'] : 0;

        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            array_merge($params, [$limit, $offset])
        );

        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function create(array $data): ?int {
        $allowed = ['title', 'resource_type', 'url', 'description', 'topics', 'author', 'publisher', 'published_at', 'status'];
        $insert = array_intersect_key($data, array_flip($allowed));

        $this->db->insert($this->table, $insert);

        return $this->db->insert_id ?: null;
    }

    public function update(int $id, array $data): bool {
        $allowed = ['title', 'resource_type', 'url', 'description', 'topics', 'author', 'publisher', 'published_at', 'status'];
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
