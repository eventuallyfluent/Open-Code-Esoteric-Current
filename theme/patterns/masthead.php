<?php
register_block_pattern('observatory-index/masthead', [
    'title' => __('Masthead', 'observatory-index'),
    'description' => __('Site masthead with title and tagline, dark observatory style.', 'observatory-index'),
    'content' => '<!-- wp:group {"style":{"color":{"background":"#050505"},"spacing":{"padding":{"top":"5rem","bottom":"5rem"}}},"textColor":"text","layout":{"type":"default"}} -->
<div class="wp-block-group has-text-color has-background" style="background-color:#050505;padding-top:5rem;padding-bottom:5rem">
    <!-- wp:site-title {"textAlign":"center","style":{"typography":{"fontSize":"clamp(2rem,4vw,3.5rem)","fontStyle":"normal","fontWeight":"400"}}} /-->
    <!-- wp:site-tagline {"textAlign":"center","textColor":"muted"} /-->
</div>
<!-- /wp:group -->',
]);
