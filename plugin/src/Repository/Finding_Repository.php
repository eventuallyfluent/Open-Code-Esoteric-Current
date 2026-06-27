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

    private const BLOCKED_DOMAINS = [
        'wikipedia.org', 'archive.org', 'encyclopedia.com', 'britannica.com',
        'amazon.com', 'ebay.com', 'etsy.com', 'goodreads.com',
        'jstor.org', 'academia.edu', 'researchgate.net',
        'coursera.org', 'udemy.com', 'edx.org',
        'oup.com', 'cambridge.org', 'springer.com',
        'youtube.com', 'instagram.com', 'facebook.com', 'twitter.com', 'reddit.com',
    ];

    public function get_all(array $args = []): array {
        $where = '1=1';
        $params = [];
        $joins = [];

        if (!empty($args['exclude_blocked'])) {
            foreach (self::BLOCKED_DOMAINS as $b) {
                $where .= ' AND COALESCE(f.source_url, f.url) NOT LIKE %s';
                $params[] = '%' . $this->db->esc_like($b);
            }
        }

        if (!empty($args['status'])) {
            if (is_array($args['status'])) {
                $placeholders = implode(',', array_fill(0, count($args['status']), '%s'));
                $where .= " AND f.status IN ({$placeholders})";
                $params = array_merge($params, $args['status']);
            } else {
                $where .= ' AND f.status = %s';
                $params[] = $args['status'];
            }
        }
        if (!empty($args['topic_id'])) {
            $where .= ' AND f.topic_id = %d';
            $params[] = (int)$args['topic_id'];
        }
        if (!empty($args['finding_type'])) {
            $where .= ' AND f.finding_type = %s';
            $params[] = $args['finding_type'];
        }
        if (!empty($args['ec_topic'])) {
            $rel_table = $this->db->prefix . 'ec_term_relationships';
            $tax_table = $this->db->prefix . 'ec_term_taxonomy';
            $terms_table = $this->db->prefix . 'ec_terms';
            $joins[] = "INNER JOIN {$rel_table} r ON f.id = r.object_id";
            $joins[] = "INNER JOIN {$tax_table} tt ON r.term_taxonomy_id = tt.id AND tt.taxonomy = 'ec_topic'";
            $joins[] = "INNER JOIN {$terms_table} t ON tt.term_id = t.id";
            $where .= ' AND t.slug = %s';
            $params[] = $args['ec_topic'];
        }
        if (!empty($args['ec_resource_type'])) {
            $rel_table = $this->db->prefix . 'ec_term_relationships';
            $tax_table = $this->db->prefix . 'ec_term_taxonomy';
            $terms_table = $this->db->prefix . 'ec_terms';
            $joins[] = "INNER JOIN {$rel_table} r2 ON f.id = r2.object_id";
            $joins[] = "INNER JOIN {$tax_table} tt2 ON r2.term_taxonomy_id = tt2.id AND tt2.taxonomy = 'ec_resource_type'";
            $joins[] = "INNER JOIN {$terms_table} t2 ON tt2.term_id = t2.id";
            $where .= ' AND t2.slug = %s';
            $params[] = $args['ec_resource_type'];
        }

        $limit = !empty($args['limit']) ? min((int)$args['limit'], 100) : 50;
        $offset = !empty($args['offset']) ? (int)$args['offset'] : 0;

        $from = $this->table . ' f';
        $join_sql = !empty($joins) ? ' ' . implode(' ', $joins) : '';

        $sql = $this->db->prepare(
            "SELECT f.* FROM {$from}{$join_sql} WHERE {$where} ORDER BY f.created_at DESC LIMIT %d OFFSET %d",
            array_merge($params, [$limit, $offset])
        );

        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function get_published(array $args = []): array {
        $args['status'] = ['approved', 'published'];
        $args['exclude_blocked'] = true;
        return $this->get_all($args);
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
        $content_hash = $finding['content_hash'] ?? hash('sha256', $finding['url'] ?? '');
        $existing = $this->get_by_hash($content_hash);

        $data = [
            'run_id' => $run_id,
            'topic_id' => $topic_id,
            'finding_type' => $finding['finding_type'] ?? 'article',
            'title' => $finding['title'] ?? '',
            'url' => $finding['url'] ?? '',
            'source_url' => $finding['source_url'] ?? null,
            'content_hash' => $content_hash,
            'content' => $finding['content'] ?? null,
            'excerpt' => $finding['excerpt'] ?? null,
            'evidence' => $finding['evidence'] ?? null,
            'relevance_score' => $finding['relevance_score'] ?? null,
            'confidence_score' => $finding['confidence_score'] ?? null,
            'classification' => $finding['classification'] ?? null,
            'status' => 'approved',
        ];

        if ($existing) {
            $data['status'] = $existing['status'];
            $this->update((int)$existing['id'], $data);
            $finding_id = (int)$existing['id'];
        } else {
            $finding_id = $this->create($data);
        }

        if ($finding_id) {
            $term_repo = new \EsotericCurrent\Core\Repository\Term_Repository();
            if (!empty($finding['classification'])) {
                $term_repo->migrate_classification_to_terms($finding['classification'], $finding_id);
            }
            $type_slug = $finding['finding_type'] ?? '';
            $plural_map = [
                'book' => 'books', 'article' => 'articles', 'course' => 'courses',
                'teacher' => 'teachers', 'interview' => 'interviews',
                'research-paper' => 'research-papers', 'event' => 'events',
                'podcast' => 'podcasts', 'organization' => 'organizations',
            ];
            if ($type_slug && isset($plural_map[$type_slug])) {
                $type_term = $term_repo->get_term_by_slug($plural_map[$type_slug], 'ec_resource_type');
                if ($type_term) {
                    $term_repo->add_object_term($finding_id, (int)$type_term['term_taxonomy_id']);
                }
            }
        }

        return $finding_id;
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
