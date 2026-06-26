<?php
namespace EsotericCurrent\Core\Repository;

class Finding_Repository {
    private \wpdb $db;
    private string $table;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . 'ec_findings';
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
        if (!empty($args['finding_type'])) {
            $where .= ' AND finding_type = %s';
            $params[] = $args['finding_type'];
        }

        $limit = !empty($args['limit']) ? min((int)$args['limit'], 100) : 50;
        $offset = !empty($args['offset']) ? (int)$args['offset'] : 0;

        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            array_merge($params, [$limit, $offset])
        );

        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function get_by_hash(string $hash): ?array {
        $row = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE content_hash = %s", $hash),
            ARRAY_A
        );
        return $row ?: null;
    }

    public function get_by_topic_id(int $topic_id): array {
        return $this->db->get_results(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE topic_id = %d ORDER BY relevance_score DESC", $topic_id),
            ARRAY_A
        ) ?: [];
    }

    public function create(array $data): ?int {
        $allowed = [
            'run_id', 'topic_id', 'finding_type', 'title', 'url', 'source_url',
            'content_hash', 'content', 'excerpt', 'evidence',
            'relevance_score', 'confidence_score', 'classification', 'status'
        ];
        $insert = array_intersect_key($data, array_flip($allowed));

        $this->db->insert($this->table, $insert);

        return $this->db->insert_id ?: null;
    }

    public function create_from_agent(array $finding, int $run_id, ?int $topic_id): ?int {
        return $this->create([
            'run_id' => $run_id,
            'topic_id' => $topic_id,
            'finding_type' => $finding['finding_type'] ?? 'article',
            'title' => $finding['title'] ?? '',
            'url' => $finding['url'] ?? '',
            'source_url' => $finding['source_url'] ?? null,
            'content_hash' => $finding['content_hash'] ?? hash('sha256', $finding['url'] ?? ''),
            'content' => $finding['content'] ?? null,
            'excerpt' => $finding['excerpt'] ?? null,
            'evidence' => $finding['evidence'] ?? null,
            'relevance_score' => $finding['relevance_score'] ?? null,
            'confidence_score' => $finding['confidence_score'] ?? null,
            'classification' => $finding['classification'] ?? null,
            'status' => 'awaiting_review',
        ]);
    }

    public function update(int $id, array $data): bool {
        $allowed = [
            'finding_type', 'title', 'url', 'source_url', 'content',
            'excerpt', 'evidence', 'relevance_score', 'confidence_score',
            'classification', 'status'
        ];
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
