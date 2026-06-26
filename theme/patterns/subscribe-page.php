<?php
register_block_pattern('observatory-index/subscribe-page', [
    'title' => __('Subscribe Page Content', 'observatory-index'),
    'description' => __('Ready-to-use content for the Subscribe page.', 'observatory-index'),
    'content' => '<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"4rem","bottom":"4rem"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="padding-top:4rem;padding-bottom:4rem">
    <!-- wp:heading {"textAlign":"left","level":1} -->
    <h1 class="wp-block-heading has-text-align-left">Subscribe</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Stay informed of new additions to the index. Subscription options will be announced as they become available.</p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph {"textColor":"muted"} -->
    <p class="has-muted-color">RSS feed and newsletter integration coming soon.</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
]);
