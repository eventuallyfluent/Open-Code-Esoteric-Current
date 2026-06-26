### Task 3: Template parts and patterns

**Files:**
- Create: `C:\Dev\Open Code Esoteric Current\theme\parts\masthead.html`
- Create: `C:\Dev\Open Code Esoteric Current\theme\parts\signals-rail.html`
- Create: `C:\Dev\Open Code Esoteric Current\theme\parts\policy-strip.html`
- Create: `C:\Dev\Open Code Esoteric Current\theme\patterns\masthead.php`

**Interfaces:**
- Consumes: visual palette from theme.json, plugin blocks
- Produces: masthead with site description, signals rail for latest findings, policy strip for legal/links

- [ ] **Step 1: Create masthead.html part**

```html
<!-- wp:group {"style":{"color":{"background":"#000000"},"spacing":{"padding":{"top":"4rem","bottom":"4rem"}}},"textColor":"white","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-white-color has-black-background-color has-text-color has-background" style="padding-top:4rem;padding-bottom:4rem">
    <!-- wp:site-title {"textAlign":"center","style":{"typography":{"fontSize":"3rem","fontStyle":"normal","fontWeight":"700"}}} /-->
    <!-- wp:site-tagline {"textAlign":"center","style":{"typography":{"fontSize":"1rem"}},"textColor":"muted-gold"} /-->
</div>
<!-- /wp:group -->
```

- [ ] **Step 2: Create signals-rail.html part**

```html
<!-- wp:group {"style":{"color":{"background":"#1A2238"},"spacing":{"padding":{"top":"0.75rem","bottom":"0.75rem"}}},"textColor":"warm-white","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-warm-white-color has-dark-navy-background-color has-text-color has-background" style="padding-top:0.75rem;padding-bottom:0.75rem">
    <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
    <p class="has-text-align-center has-small-font-size">Latest signals will appear here</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
```

- [ ] **Step 3: Create policy-strip.html part**

```html
<!-- wp:group {"style":{"color":{"background":"#2C2C2C"},"spacing":{"padding":{"top":"0.5rem","bottom":"0.5rem"}}},"textColor":"light-gray","fontSize":"small","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"}} -->
<div class="wp-block-group has-light-gray-color has-charcoal-background-color has-text-color has-background has-small-font-size" style="padding-top:0.5rem;padding-bottom:0.5rem">
    <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.75rem"}}} -->
    <p style="font-size:0.75rem">All content is curated. Views expressed are those of the authors.</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
```

- [ ] **Step 4: Create masthead pattern**

```php
<?php
register_block_pattern('observatory-index/masthead', [
    'title' => __('Masthead', 'observatory-index'),
    'description' => __('Site masthead with title and tagline.', 'observatory-index'),
    'content' => '<!-- wp:group {"style":{"color":{"background":"#000000"},"spacing":{"padding":{"top":"4rem","bottom":"4rem"}}},"textColor":"white","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-white-color has-black-background-color has-text-color has-background" style="padding-top:4rem;padding-bottom:4rem">
    <!-- wp:site-title {"textAlign":"center","style":{"typography":{"fontSize":"3rem"}}} /-->
    <!-- wp:site-tagline {"textAlign":"center","textColor":"muted-gold"} /-->
</div>
<!-- /wp:group -->',
]);
```

- [ ] **Step 5: Update functions.php to load pattern**

```php
add_action('init', function () {
    require_once get_template_directory() . '/patterns/masthead.php';
});
```

- [ ] **Step 6: Commit**

```bash
git add theme/parts/ theme/patterns/ theme/functions.php
git commit -m "feat(theme): template parts and masthead pattern"
```

---
