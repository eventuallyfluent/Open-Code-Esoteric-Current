<?php
namespace EsotericCurrent\Core\Repository;

class Flag_Repository {
    private \wpdb $db;
    private string $table;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . 'ec_finding_flags';
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

        if (isset($args['reviewed'])) {
            $where .= ' AND reviewed = %d';
            $params[] = (int)$args['reviewed'];
        }
        if (!empty($args['finding_id'])) {
            $where .= ' AND finding_id = %d';
            $params[] = (int)$args['finding_id'];
        }
        if (!empty($args['reason'])) {
            $where .= ' AND reason = %s';
            $params[] = $args['reason'];
        }

        $limit = !empty($args['limit']) ? min((int)$args['limit'], 200) : 50;
        $offset = !empty($args['offset']) ? (int)$args['offset'] : 0;

        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$where} ORDER BY reviewed ASC, created_at DESC LIMIT %d OFFSET %d",
            array_merge($params, [$limit, $offset])
        );

        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function count(array $args = []): int {
        $where = '1=1';
        $params = [];

        if (isset($args['reviewed'])) {
            $where .= ' AND reviewed = %d';
            $params[] = (int)$args['reviewed'];
        }
        if (!empty($args['finding_id'])) {
            $where .= ' AND finding_id = %d';
            $params[] = (int)$args['finding_id'];
        }

        $sql = !empty($params)
            ? $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE {$where}", $params)
            : "SELECT COUNT(*) FROM {$this->table} WHERE {$where}";

        return (int)$this->db->get_var($sql);
    }

    public function mark_reviewed(int $id): bool {
        return (bool)$this->db->update(
            $this->table,
            ['reviewed' => 1],
            ['id' => $id]
        );
    }

    public function mark_unreviewed(int $id): bool {
        return (bool)$this->db->update(
            $this->table,
            ['reviewed' => 0],
            ['id' => $id]
        );
    }

    public function dismiss_multiple(array $ids): int {
        if (empty($ids)) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $sql = $this->db->prepare(
            "UPDATE {$this->table} SET reviewed = 1 WHERE id IN ({$placeholders})",
            $ids
        );
        return (int)$this->db->query($sql);
    }

    public function delete(int $id): bool {
        return (bool)$this->db->delete($this->table, ['id' => $id]);
    }
}
