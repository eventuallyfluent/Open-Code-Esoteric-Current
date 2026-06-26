<?php
namespace EsotericCurrent\Core\Repository;

class Run_Log_Repository {
    private \wpdb $db;
    private string $table;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . 'ec_run_log';
    }

    public function get_by_id(int $id): ?array {
        $row = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id),
            ARRAY_A
        );
        return $row ?: null;
    }

    public function add_entry(int $run_id, string $level, string $message, ?array $context = null): ?int {
        $this->db->insert($this->table, [
            'run_id' => $run_id,
            'level' => $level,
            'message' => $message,
            'context_json' => $context !== null ? wp_json_encode($context) : null,
        ]);

        return $this->db->insert_id ?: null;
    }

    public function get_by_run_id(int $run_id): array {
        return $this->db->get_results(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE run_id = %d ORDER BY created_at ASC", $run_id),
            ARRAY_A
        ) ?: [];
    }

    public function get_all(array $args = []): array {
        $where = '1=1';
        $params = [];

        if (!empty($args['level'])) {
            $where .= ' AND level = %s';
            $params[] = $args['level'];
        }
        if (!empty($args['run_id'])) {
            $where .= ' AND run_id = %d';
            $params[] = (int)$args['run_id'];
        }

        $limit = !empty($args['limit']) ? min((int)$args['limit'], 100) : 50;
        $offset = !empty($args['offset']) ? (int)$args['offset'] : 0;

        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            array_merge($params, [$limit, $offset])
        );

        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function delete_old(int $days = 90): int {
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($days * 86400));
        $this->db->query(
            $this->db->prepare("DELETE FROM {$this->table} WHERE created_at < %s", $cutoff)
        );
        return $this->db->rows_affected;
    }
}
