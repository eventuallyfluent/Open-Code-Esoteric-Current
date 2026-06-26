<?php
register_block_pattern('observatory-index/terms-page', [
    'title' => __('Terms of Use Page Content', 'observatory-index'),
    'description' => __('Ready-to-use content for the Terms of Use page.', 'observatory-index'),
    'content' => '<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"4rem","bottom":"4rem"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="padding-top:4rem;padding-bottom:4rem">
    <!-- wp:heading {"textAlign":"left","level":1} -->
    <h1 class="wp-block-heading has-text-align-left">Terms of Use</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Last updated: June 2026</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">Acceptance</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>By accessing The Esoteric Current, you agree to these terms of use. If you do not agree, please discontinue use of the site.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">Content Usage</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>All content on this site is provided for informational and research purposes. Each indexed item links to its original source; The Esoteric Current does not claim ownership of linked external content. Attribution is provided where applicable.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">User Conduct</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Users agree not to misuse the site, including but not limited to scraping, automated access without permission, or submitting unlawful content.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">Changes</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>We reserve the right to update these terms at any time. Continued use constitutes acceptance of changes.</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
]);
