<?php
\register_block_pattern('observatory-index/masthead', [
    'title' => \__('Masthead', 'observatory-index'),
    'description' => \__('Site masthead with title and tagline.', 'observatory-index'),
    'content' => '<!-- wp:group {"style":{"color":{"background":"#000000"},"spacing":{"padding":{"top":"4rem","bottom":"4rem"}}},"textColor":"white","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-white-color has-black-background-color has-text-color has-background" style="padding-top:4rem;padding-bottom:4rem">
    <!-- wp:site-title {"textAlign":"center","style":{"typography":{"fontSize":"3rem"}}} /-->
    <!-- wp:site-tagline {"textAlign":"center","textColor":"muted-gold"} /-->
</div>
<!-- /wp:group -->',
]);
