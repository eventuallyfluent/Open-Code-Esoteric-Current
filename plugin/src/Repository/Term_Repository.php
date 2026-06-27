<?php
namespace EsotericCurrent\Core\Repository;

class Term_Repository {
    private \wpdb $db;
    private string $terms_table;
    private string $tax_table;
    private string $rel_table;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $prefix = $wpdb->prefix;
        $this->terms_table = $prefix . 'ec_terms';
        $this->tax_table = $prefix . 'ec_term_taxonomy';
        $this->rel_table = $prefix . 'ec_term_relationships';
    }

    public function get_terms(string $taxonomy, array $args = []): array {
        $where = 'tt.taxonomy = %s';
        $params = [$taxonomy];

        if (!empty($args['parent'])) {
            $where .= ' AND tt.parent = %d';
            $params[] = (int)$args['parent'];
        }
        if (!empty($args['search'])) {
            $where .= ' AND t.name LIKE %s';
            $params[] = '%' . $this->db->esc_like($args['search']) . '%';
        }
        if (!empty($args['hide_empty'])) {
            $where .= ' AND tt.count > 0';
        }

        $sql = "SELECT t.*, tt.id as term_taxonomy_id, tt.taxonomy, tt.description, tt.parent, tt.count
                FROM {$this->terms_table} t
                INNER JOIN {$this->tax_table} tt ON t.id = tt.term_id
                WHERE {$where}
                ORDER BY t.name ASC";

        return $this->db->get_results($this->db->prepare($sql, $params), ARRAY_A) ?: [];
    }

    public function get_term(int $term_id, string $taxonomy): ?array {
        $sql = $this->db->prepare(
            "SELECT t.*, tt.id as term_taxonomy_id, tt.taxonomy, tt.description, tt.parent, tt.count
             FROM {$this->terms_table} t
             INNER JOIN {$this->tax_table} tt ON t.id = tt.term_id
             WHERE t.id = %d AND tt.taxonomy = %s",
            $term_id, $taxonomy
        );
        $row = $this->db->get_row($sql, ARRAY_A);
        return $row ?: null;
    }

    public function get_term_by_slug(string $slug, string $taxonomy): ?array {
        $sql = $this->db->prepare(
            "SELECT t.*, tt.id as term_taxonomy_id, tt.taxonomy, tt.description, tt.parent, tt.count
             FROM {$this->terms_table} t
             INNER JOIN {$this->tax_table} tt ON t.id = tt.term_id
             WHERE t.slug = %s AND tt.taxonomy = %s",
            $slug, $taxonomy
        );
        $row = $this->db->get_row($sql, ARRAY_A);
        return $row ?: null;
    }

    public function get_term_taxonomy_id(int $term_id, string $taxonomy): ?int {
        $ttid = $this->db->get_var(
            $this->db->prepare(
                "SELECT id FROM {$this->tax_table} WHERE term_id = %d AND taxonomy = %s",
                $term_id, $taxonomy
            )
        );
        return $ttid ? (int)$ttid : null;
    }

    public function create_term(string $name, string $taxonomy, array $args = []): ?int {
        $slug = !empty($args['slug']) ? $args['slug'] : sanitize_title($name);

        $existing = $this->db->get_var(
            $this->db->prepare("SELECT id FROM {$this->terms_table} WHERE slug = %s", $slug)
        );
        if ($existing) {
            return null;
        }

        $this->db->insert($this->terms_table, [
            'name' => $name,
            'slug' => $slug,
            'term_group' => !empty($args['term_group']) ? (int)$args['term_group'] : 0,
        ]);
        $term_id = $this->db->insert_id;
        if (!$term_id) {
            return null;
        }

        $parent_ttid = 0;
        if (!empty($args['parent'])) {
            $parent_sql = $this->db->prepare(
                "SELECT id FROM {$this->tax_table} WHERE term_id = %d AND taxonomy = %s",
                (int)$args['parent'], $taxonomy
            );
            $parent_ttid = (int)$this->db->get_var($parent_sql);
        }

        $this->db->insert($this->tax_table, [
            'term_id' => $term_id,
            'taxonomy' => $taxonomy,
            'description' => $args['description'] ?? '',
            'parent' => $parent_ttid,
            'count' => 0,
        ]);

        return $term_id;
    }

    public function update_term(int $term_id, array $data): bool {
        $allowed = ['name', 'slug', 'term_group'];
        $update = array_intersect_key($data, array_flip($allowed));
        if (!empty($update)) {
            $this->db->update($this->terms_table, $update, ['id' => $term_id]);
        }
        $tax_allowed = ['description', 'parent'];
        $tax_update = array_intersect_key($data, array_flip($tax_allowed));
        if (!empty($tax_update)) {
            $this->db->update($this->tax_table, $tax_update, ['term_id' => $term_id]);
        }
        return true;
    }

    public function delete_term(int $term_id): bool {
        $this->db->delete($this->rel_table, ['term_taxonomy_id' => $term_id]);
        $this->db->delete($this->tax_table, ['term_id' => $term_id]);
        $this->db->delete($this->terms_table, ['id' => $term_id]);
        return true;
    }

    public function set_object_terms(int $object_id, array $term_taxonomy_ids): void {
        $this->db->delete($this->rel_table, ['object_id' => $object_id]);
        foreach ($term_taxonomy_ids as $ttid) {
            $this->db->insert($this->rel_table, [
                'object_id' => $object_id,
                'term_taxonomy_id' => (int)$ttid,
                'term_order' => 0,
            ]);
        }
        $this->update_counts($term_taxonomy_ids);
    }

    public function add_object_term(int $object_id, int $term_taxonomy_id): void {
        $exists = $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->rel_table} WHERE object_id = %d AND term_taxonomy_id = %d",
                $object_id, $term_taxonomy_id
            )
        );
        if (!$exists) {
            $this->db->insert($this->rel_table, [
                'object_id' => $object_id,
                'term_taxonomy_id' => $term_taxonomy_id,
                'term_order' => 0,
            ]);
            $this->update_counts([$term_taxonomy_id]);
        }
    }

    public function remove_object_term(int $object_id, int $term_taxonomy_id): void {
        $this->db->delete($this->rel_table, [
            'object_id' => $object_id,
            'term_taxonomy_id' => $term_taxonomy_id,
        ]);
        $this->update_counts([$term_taxonomy_id]);
    }

    public function get_object_terms(int $object_id, ?string $taxonomy = null): array {
        $sql = "SELECT t.*, tt.id as term_taxonomy_id, tt.taxonomy, tt.description, tt.parent, tt.count
                FROM {$this->rel_table} r
                INNER JOIN {$this->tax_table} tt ON r.term_taxonomy_id = tt.id
                INNER JOIN {$this->terms_table} t ON tt.term_id = t.id
                WHERE r.object_id = %d";
        $params = [$object_id];

        if ($taxonomy) {
            $sql .= ' AND tt.taxonomy = %s';
            $params[] = $taxonomy;
        }

        $sql .= ' ORDER BY t.name ASC';

        return $this->db->get_results($this->db->prepare($sql, $params), ARRAY_A) ?: [];
    }

    public function get_objects_by_term(int $term_taxonomy_id, int $limit = 50, int $offset = 0): array {
        $sql = $this->db->prepare(
            "SELECT r.object_id FROM {$this->rel_table} r
             WHERE r.term_taxonomy_id = %d
             ORDER BY r.object_id DESC
             LIMIT %d OFFSET %d",
            $term_taxonomy_id, $limit, $offset
        );
        $rows = $this->db->get_col($sql);
        return array_map('intval', $rows);
    }

    public function get_term_children(int $parent_term_taxonomy_id): array {
        $sql = $this->db->prepare(
            "SELECT t.*, tt.id as term_taxonomy_id, tt.taxonomy, tt.description, tt.parent, tt.count
             FROM {$this->tax_table} tt
             INNER JOIN {$this->terms_table} t ON tt.term_id = t.id
             WHERE tt.parent = %d
             ORDER BY t.name ASC",
            $parent_term_taxonomy_id
        );
        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function get_top_level_terms(string $taxonomy): array {
        return $this->get_terms($taxonomy, ['parent' => 0]);
    }

    private function update_counts(array $term_taxonomy_ids): void {
        foreach (array_unique($term_taxonomy_ids) as $ttid) {
            $count = (int)$this->db->get_var(
                $this->db->prepare(
                    "SELECT COUNT(*) FROM {$this->rel_table} WHERE term_taxonomy_id = %d",
                    $ttid
                )
            );
            $this->db->update($this->tax_table, ['count' => $count], ['id' => $ttid]);
        }
    }

    public function migrate_classification_to_terms(string $classification, int $finding_id): void {
        $tags = array_map('trim', explode(',', $classification));
        $ttids = [];
        foreach ($tags as $tag) {
            if (empty($tag)) continue;
            $slug = sanitize_title($tag);
            $term = $this->get_term_by_slug($slug, 'ec_topic');
            if ($term) {
                $ttids[] = (int)$term['term_taxonomy_id'];
            }
        }
        if (!empty($ttids)) {
            foreach ($ttids as $ttid) {
                $this->add_object_term($finding_id, $ttid);
            }
        }
    }
}
