<?php
register_block_pattern('observatory-index/privacy-page', [
    'title' => __('Privacy Policy Page Content', 'observatory-index'),
    'description' => __('Ready-to-use content for the Privacy Policy page.', 'observatory-index'),
    'content' => '<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"4rem","bottom":"4rem"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="padding-top:4rem;padding-bottom:4rem">
    <!-- wp:heading {"textAlign":"left","level":1} -->
    <h1 class="wp-block-heading has-text-align-left">Privacy Policy</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Last updated: June 2026</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">Information We Collect</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>The Esoteric Current collects minimal information necessary to operate the site. This may include anonymized analytics data, email addresses for newsletter subscribers (if applicable), and submission metadata from contributors.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">Cookies</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>We use essential cookies for site functionality. No third-party tracking cookies are employed unless explicitly disclosed.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">Data Sharing</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>We do not sell, trade, or share personal information with third parties except as required by law.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">Contact</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>For privacy-related inquiries, contact the editorial team via the Contact page.</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
]);
