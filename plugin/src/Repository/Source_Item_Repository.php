<?php
namespace EsotericCurrent\Core\Repository;

class Source_Item_Repository {
    private \wpdb $db;
    private string $table;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . 'ec_source_items';
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
        if (!empty($args['source_id'])) {
            $where .= ' AND source_id = %d';
            $params[] = (int)$args['source_id'];
        }

        $limit = !empty($args['limit']) ? min((int)$args['limit'], 100) : 50;
        $offset = !empty($args['offset']) ? (int)$args['offset'] : 0;

        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$where} ORDER BY published_at DESC LIMIT %d OFFSET %d",
            array_merge($params, [$limit, $offset])
        );

        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function get_by_content_hash(string $hash): ?array {
        $row = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE content_hash = %s", $hash),
            ARRAY_A
        );
        return $row ?: null;
    }

    public function get_by_source_id(int $source_id): array {
        return $this->db->get_results(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE source_id = %d ORDER BY published_at DESC LIMIT 50", $source_id),
            ARRAY_A
        ) ?: [];
    }

    public function create(array $data): ?int {
        $allowed = ['source_id', 'title', 'url', 'content_hash', 'content', 'excerpt', 'author', 'published_at', 'status'];
        $insert = array_intersect_key($data, array_flip($allowed));
        $insert['fetched_at'] = current_time('mysql');

        $this->db->insert($this->table, $insert);

        return $this->db->insert_id ?: null;
    }

    public function update(int $id, array $data): bool {
        $allowed = ['title', 'url', 'content', 'excerpt', 'author', 'published_at', 'status'];
        $update = array_intersect_key($data, array_flip($allowed));

        if (empty($update)) {
            return false;
        }

        return (bool)$this->db->update($this->table, $update, ['id' => $id]);
    }

    public function count_unprocessed(): int {
        return (int)$this->db->get_var(
            $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE status = %s", 'collected')
        );
    }

    public function delete(int $id): bool {
        return (bool)$this->db->delete($this->table, ['id' => $id]);
    }
}
