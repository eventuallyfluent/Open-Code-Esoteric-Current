<?php
register_block_pattern('observatory-index/contact-page', [
    'title' => __('Contact Page Content', 'observatory-index'),
    'description' => __('Ready-to-use content for the Contact page.', 'observatory-index'),
    'content' => '<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"4rem","bottom":"4rem"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="padding-top:4rem;padding-bottom:4rem">
    <!-- wp:heading {"textAlign":"left","level":1} -->
    <h1 class="wp-block-heading has-text-align-left">Contact</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>For inquiries, submissions, or corrections, please reach out to the editorial team.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">General Inquiries</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Email: <a href="mailto:editorial@esotericcurrent.com">editorial@esotericcurrent.com</a></p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">Submissions</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Use our <a href="/submissions/">submissions page</a> to submit a source for inclusion in the index.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">Corrections</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>If you notice an error in any listing, please email corrections@esotericcurrent.com with the item URL and details.</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
]);
