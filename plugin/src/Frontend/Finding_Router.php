<?php
namespace EsotericCurrent\Core\Frontend;

class Finding_Router {
    public static function init(): void {
        add_rewrite_rule('^finding/(\d+)/?$', 'index.php?pagename=catalogue&ec_finding_id=$matches[1]', 'top');
        add_filter('query_vars', [self::class, 'add_query_var']);
        add_shortcode('ec_finding_detail', [self::class, 'render_shortcode']);
    }

    public static function add_query_var(array $vars): array {
        $vars[] = 'ec_finding_id';
        return $vars;
    }

    public static function render_shortcode(): string {
        $finding_id = (int)get_query_var('ec_finding_id');
        if ($finding_id < 1) {
            return '<p>No item specified.</p>';
        }

        $repo = new \EsotericCurrent\Core\Repository\Finding_Repository();
        $finding = $repo->get_by_id($finding_id);
        if ($finding === null || $finding['status'] === 'rejected') {
            return '<p>Item not found.</p>';
        }

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

            <?php if (!empty($finding['classification'])): ?>
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
}
