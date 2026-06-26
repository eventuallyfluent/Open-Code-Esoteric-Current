<?php
register_block_pattern('observatory-index/about-page', [
    'title' => __('About Page Content', 'observatory-index'),
    'description' => __('Ready-to-use content for the About page.', 'observatory-index'),
    'content' => '<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"4rem","bottom":"4rem"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="padding-top:4rem;padding-bottom:4rem">
    <!-- wp:heading {"textAlign":"left","level":1} -->
    <h1 class="wp-block-heading has-text-align-left">About The Esoteric Current</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>The Esoteric Current is a curated index of esoteric research, news, book releases, interviews, and discoveries across the field. We track what is happening at the intersections of hermeticism, alchemy, astrology, kabbalah, gnosticism, neoplatonism, theosophy, rosicrucianism, ceremonial magic, shamanism, dzogchen, tantra, sufism, mysticism, and esoteric Christianity — and make it discoverable in one place.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">Mission</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Our mission is to surface signal in a noisy world — connecting researchers, practitioners, and the curious to the ideas and discoveries shaping contemporary esoteric thought. Every item in our index is reviewed by editorial staff before publication.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">Method</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Content is surfaced through a combination of automated discovery agents and community submissions. Each finding is assigned a confidence score and relevance rating, then reviewed by human editors before appearing in the index.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">ISSN &amp; Catalogue Reference</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>The Esoteric Current is catalogued under ISSN 3049-XXXX. Catalogue ref: EC-PUB-001.</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
]);
