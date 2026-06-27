<?php
namespace EsotericCurrent\Core\Frontend;

use EsotericCurrent\Core\Repository\Finding_Repository;
use EsotericCurrent\Core\Repository\Term_Repository;

class Finding_Router {
    public static function init(): void {
        add_rewrite_rule('^finding/(\d+)/?$', 'index.php?pagename=catalogue&ec_finding_id=$matches[1]', 'top');
        add_filter('query_vars', [self::class, 'add_query_var']);
        add_shortcode('ec_finding_detail', [self::class, 'render_shortcode']);
        add_shortcode('ec_catalogue_page', [self::class, 'render_catalogue']);
        add_filter('the_title', [self::class, 'override_page_title'], 10, 2);
        add_filter('the_content', [self::class, 'override_page_content'], 5);
    }

    public static function add_query_var(array $vars): array {
        $vars[] = 'ec_finding_id';
        return $vars;
    }

    public static function get_context(): array {
        $finding_id = (int)get_query_var('ec_finding_id');
        $topic_slug = get_query_var('ec_topic');
        $type_slug = get_query_var('ec_resource_type');
        $context = ['type' => 'catalogue', 'title' => 'Catalogue'];
        if ($finding_id > 0) {
            $repo = new Finding_Repository();
            $finding = $repo->get_by_id($finding_id);
            if ($finding) {
                $context['type'] = 'finding';
                $context['title'] = $finding['title'];
                $context['id'] = $finding_id;
            }
        } elseif (!empty($topic_slug)) {
            $t_repo = new Term_Repository();
            $term = $t_repo->get_term_by_slug(sanitize_title($topic_slug), 'ec_topic');
            $context['type'] = 'topic';
            $context['title'] = $term ? $term['name'] : ucfirst(str_replace('-', ' ', $topic_slug));
        } elseif (!empty($type_slug)) {
            $t_repo = new Term_Repository();
            $term = $t_repo->get_term_by_slug(sanitize_title($type_slug), 'ec_resource_type');
            $context['type'] = 'type';
            $context['title'] = $term ? $term['name'] : ucfirst(str_replace('-', ' ', $type_slug));
        }
        return $context;
    }

    public static function override_page_title(string $title, int $post_id = 0): string {
        if (!in_the_loop() || !is_main_query()) return $title;
        $context = self::get_context();
        if ($context['type'] !== 'catalogue') return $context['title'];
        return $title;
    }

    public static function override_page_content(string $content): string {
        if (!is_main_query() || !in_the_loop()) return $content;
        $finding_id = (int)get_query_var('ec_finding_id');
        $topic_slug = get_query_var('ec_topic');
        $type_slug = get_query_var('ec_resource_type');
        if ($finding_id > 0 || !empty($topic_slug) || !empty($type_slug)) {
            return do_shortcode('[ec_finding_detail]');
        }
        return $content;
    }

    public static function render_shortcode(): string {
        $finding_id = (int)get_query_var('ec_finding_id');
        $topic_slug = get_query_var('ec_topic');
        $type_slug = get_query_var('ec_resource_type');

        if ($finding_id < 1 && empty($topic_slug) && empty($type_slug)) {
            return self::render_catalogue();
        }

        if ($finding_id < 1) {
            return self::render_catalogue();
        }

        $f_repo = new Finding_Repository();
        $t_repo = new Term_Repository();
        $finding = $f_repo->get_by_id($finding_id);
        if ($finding === null || $finding['status'] === 'rejected') {
            return '<p>Item not found.</p>';
        }

        $topic_terms = $t_repo->get_object_terms($finding_id, 'ec_topic');
        $type_terms = $t_repo->get_object_terms($finding_id, 'ec_resource_type');

        ob_start();
        ?>
        <article class="ec-detail-item">
            <nav class="ec-detail-breadcrumb">
                <a href="<?php echo esc_url(home_url()); ?>">Catalogue</a>
                <span class="ec-detail-sep">/</span>
                <span class="ec-feed-type ec-feed-type--<?php echo esc_attr($finding['finding_type'] ?: 'default'); ?>">
                    <?php echo esc_html(ucfirst($finding['finding_type'] ?: 'Finding')); ?>
                </span>
            </nav>

            <h1 class="ec-detail-title"><?php echo esc_html($finding['title']); ?></h1>

            <?php if (!empty($finding['excerpt'])): ?>
                <p class="ec-detail-desc"><?php echo esc_html($finding['excerpt']); ?></p>
            <?php endif; ?>

            <div class="ec-detail-actions">
                <a href="<?php echo esc_url($finding['url']); ?>" target="_blank" rel="noopener" class="ec-btn-subscribe">
                    Visit Source
                </a>
                <?php if (!empty($finding['source_url']) && $finding['source_url'] !== $finding['url']): ?>
                    <a href="<?php echo esc_url($finding['source_url']); ?>" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:0.5em;background:transparent;border:1px solid var(--ec-gold);color:var(--ec-gold);padding:0.7em 1.5em;text-decoration:none;font-weight:600;font-size:0.85rem">
                        Original Source
                    </a>
                <?php endif; ?>
            </div>

            <?php if (!empty($topic_terms)): ?>
                <div class="ec-topics-bar">
                    <span class="ec-topics-label">Topics</span>
                    <span class="ec-topics-list">
                        <?php foreach ($topic_terms as $term): ?>
                            <a href="<?php echo esc_url(home_url('/topic/' . $term['slug'] . '/')); ?>" class="ec-topic-chip">
                                <?php echo esc_html($term['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </span>
                </div>
            <?php elseif (!empty($finding['classification'])): ?>
                <div class="ec-topics-bar">
                    <span class="ec-topics-label">Topics</span>
                    <span class="ec-topics-list">
                        <?php foreach (explode(',', $finding['classification']) as $topic): ?>
                            <a href="<?php echo esc_url(home_url('/?s=' . trim($topic))); ?>" class="ec-topic-chip">
                                <?php echo esc_html(trim($topic)); ?>
                            </a>
                        <?php endforeach; ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if (!empty($type_terms)): ?>
                <div class="ec-topics-bar" style="margin-top:0.5rem">
                    <span class="ec-topics-label">Type</span>
                    <span class="ec-topics-list">
                        <?php foreach ($type_terms as $term): ?>
                            <a href="<?php echo esc_url(home_url('/type/' . $term['slug'] . '/')); ?>" class="ec-topic-chip">
                                <?php echo esc_html($term['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </span>
                </div>
            <?php endif; ?>

            <div class="ec-meta-row">
                <?php if (!empty($finding['relevance_score'])): ?>
                    <div class="ec-meta-cell">
                        <span class="ec-meta-label">Relevance</span>
                        <span class="ec-meta-value"><?php echo round((float)$finding['relevance_score']); ?>%</span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($finding['confidence_score'])): ?>
                    <div class="ec-meta-cell">
                        <span class="ec-meta-label">Confidence</span>
                        <span class="ec-meta-value"><?php echo round((float)$finding['confidence_score']); ?>%</span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($finding['source_url']) || !empty($finding['url'])): ?>
                    <div class="ec-meta-cell">
                        <span class="ec-meta-label">Source</span>
                        <span class="ec-meta-value"><?php echo esc_html(parse_url($finding['source_url'] ?: $finding['url'], PHP_URL_HOST)); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($finding['created_at'])): ?>
                    <div class="ec-meta-cell">
                        <span class="ec-meta-label">Added</span>
                        <span class="ec-meta-value"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($finding['created_at']))); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </article>
        <?php
        return ob_get_clean();
    }

    public static function render_catalogue(): string {
        $finding_id = (int)get_query_var('ec_finding_id');
        $topic_slug = get_query_var('ec_topic');
        $type_slug = get_query_var('ec_resource_type');

        if ($finding_id > 0) {
            return self::render_shortcode();
        }

        $f_repo = new Finding_Repository();
        $t_repo = new Term_Repository();

        $args = ['status' => 'approved', 'limit' => 50];
        $title = 'Catalogue';

        if (!empty($topic_slug)) {
            $args['ec_topic'] = sanitize_title($topic_slug);
            $term = $t_repo->get_term_by_slug($args['ec_topic'], 'ec_topic');
            $title = $term ? esc_html($term['name']) : ucfirst(str_replace('-', ' ', $topic_slug));
        } elseif (!empty($type_slug)) {
            $args['ec_resource_type'] = sanitize_title($type_slug);
            $term = $t_repo->get_term_by_slug($args['ec_resource_type'], 'ec_resource_type');
            $title = $term ? esc_html($term['name']) : ucfirst(str_replace('-', ' ', $type_slug));
        }

        $findings = $f_repo->get_published($args);

        ob_start();
        ?>
        <div class="ec-archive-header">
            <h1 class="ec-archive-title"><?php echo esc_html($title); ?></h1>
        </div>
        <?php if (empty($findings)): ?>
            <p class="ec-feed-empty">No published items found.</p>
        <?php else: ?>
            <div class="ec-feed-grid" style="--ec-feed-cols: 3">
                <?php foreach ($findings as $finding): ?>
                    <article class="ec-feed-card">
                        <div class="ec-feed-card-top">
                            <span class="ec-feed-type ec-feed-type--<?php echo esc_attr($finding['finding_type'] ?: 'default'); ?>">
                                <span class="ec-feed-type-dot"></span>
                                <?php echo esc_html(ucfirst($finding['finding_type'] ?: 'Finding')); ?>
                            </span>
                            <?php if (!empty($finding['confidence_score'])): ?>
                                <span class="ec-feed-confidence"><?php echo round((float)$finding['confidence_score']); ?>%</span>
                            <?php endif; ?>
                        </div>
                        <h3 class="ec-feed-card-title">
                            <a href="<?php echo esc_url(home_url('/finding/' . (int)$finding['id'] . '/')); ?>">
                                <?php echo esc_html($finding['title']); ?>
                            </a>
                        </h3>
                        <div class="ec-feed-card-footer">
                            <span class="ec-feed-card-source">
                                <?php echo esc_html(parse_url($finding['source_url'] ?: $finding['url'], PHP_URL_HOST)); ?>
                            </span>
                            <?php if (!empty($finding['created_at'])): ?>
                                <time class="ec-feed-card-date"><?php echo self::relative_time($finding['created_at']); ?></time>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($finding['relevance_score'])): ?>
                            <div class="ec-feed-card-relevance">
                                <span class="ec-feed-card-relevance-bar" style="width:<?php echo round((float)$finding['relevance_score']); ?>%"></span>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif;
        return ob_get_clean();
    }

    private static function relative_time(string $datetime): string {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        return date('M j', $timestamp);
    }
}
