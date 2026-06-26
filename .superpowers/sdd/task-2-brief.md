### Task 2: All template files

**Files:**
- Create: `C:\Dev\Open Code Esoteric Current\theme\templates\home.html`
- Create: `C:\Dev\Open Code Esoteric Current\theme\templates\single.html`
- Create: `C:\Dev\Open Code Esoteric Current\theme\templates\page.html`
- Create: `C:\Dev\Open Code Esoteric Current\theme\templates\archive.html`
- Create: `C:\Dev\Open Code Esoteric Current\theme\templates\search.html`
- Create: `C:\Dev\Open Code Esoteric Current\theme\templates\404.html`
- Create: `C:\Dev\Open Code Esoteric Current\theme\templates\submission.html`

**Interfaces:**
- Consumes: template parts header/footer from Task 1
- Produces: All 7 WordPress templates as HTML block markup

- [ ] **Step 1: Create home.html**

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"},"style":{"spacing":{"padding":{"top":"2rem","bottom":"3rem"}}}} -->
<main class="wp-block-group" style="padding-top:2rem;padding-bottom:3rem">
    <!-- wp:pattern {"slug":"observatory-index/masthead"} /-->
    <!-- wp:heading {"level":2,"style":{"typography":{"fontStyle":"normal","fontWeight":"600"}},"fontSize":"large"} -->
    <h2 class="wp-block-heading has-large-font-size" style="font-style:normal;font-weight:600">Latest from the Current</h2>
    <!-- /wp:heading -->
    <!-- wp:query {"queryId":0,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","sticky":"","inherit":false},"layout":{"type":"constrained"}} -->
    <div class="wp-block-query">
        <!-- wp:post-template -->
        <!-- wp:group {"style":{"border":{"bottom":{"color":"#e8e4dc","width":"1px"}},"spacing":{"padding":{"bottom":"1.5rem","top":"1.5rem"}}}} -->
        <div class="wp-block-group" style="border-bottom:1px solid #e8e4dc;padding-top:1.5rem;padding-bottom:1.5rem">
            <!-- wp:post-title {"isLink":true,"fontSize":"large"} /-->
            <!-- wp:post-excerpt /-->
            <!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"},"style":{"typography":{"fontSize":"0.875rem"}},"textColor":"deep-burgundy"} -->
            <div class="wp-block-group has-deep-burgundy-color has-text-color" style="font-size:0.875rem">
                <!-- wp:post-date /-->
                <!-- wp:post-terms {"term":"category"} /-->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:group -->
        <!-- /wp:post-template -->
        <!-- wp:query-pagination -->
        <!-- wp:query-pagination-previous /-->
        <!-- wp:query-pagination-numbers /-->
        <!-- wp:query-pagination-next /-->
        <!-- /wp:query-pagination -->
    </div>
    <!-- /wp:query -->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

- [ ] **Step 2: Create single.html**

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"},"style":{"spacing":{"padding":{"top":"3rem","bottom":"3rem"}}}} -->
<main class="wp-block-group" style="padding-top:3rem;padding-bottom:3rem">
    <!-- wp:post-featured-image /-->
    <!-- wp:post-title {"level":1} /-->
    <!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"},"style":{"typography":{"fontSize":"0.875rem"}},"textColor":"deep-burgundy"} -->
    <div class="wp-block-group has-deep-burgundy-color has-text-color" style="font-size:0.875rem">
        <!-- wp:post-date /-->
        <!-- wp:post-terms {"term":"category","prefix":"in "} /-->
        <!-- wp:post-terms {"term":"post_tag","prefix":"Tags: "} /-->
    </div>
    <!-- /wp:group -->
    <!-- wp:separator {"className":"is-style-ec-thin-rule"} -->
    <hr class="wp-block-separator has-alpha-channel-opacity is-style-ec-thin-rule"/>
    <!-- /wp:separator -->
    <!-- wp:post-content /-->
    <!-- wp:separator {"className":"is-style-ec-thin-rule"} -->
    <hr class="wp-block-separator has-alpha-channel-opacity is-style-ec-thin-rule"/>
    <!-- /wp:separator -->
    <!-- wp:post-navigation-link {"type":"previous","label":"â† Previous"} /-->
    <!-- wp:post-navigation-link {"type":"next","label":"Next â†’"} /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

- [ ] **Step 3: Create page.html**

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"},"style":{"spacing":{"padding":{"top":"3rem","bottom":"3rem"}}}} -->
<main class="wp-block-group" style="padding-top:3rem;padding-bottom:3rem">
    <!-- wp:post-title {"level":1} /-->
    <!-- wp:post-content /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

- [ ] **Step 4: Create archive.html**

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"},"style":{"spacing":{"padding":{"top":"2rem","bottom":"3rem"}}}} -->
<main class="wp-block-group" style="padding-top:2rem;padding-bottom:3rem">
    <!-- wp:query-title {"type":"archive"} /-->
    <!-- wp:term-description /-->
    <!-- wp:separator {"className":"is-style-ec-thin-rule"} -->
    <hr class="wp-block-separator has-alpha-channel-opacity is-style-ec-thin-rule"/>
    <!-- /wp:separator -->
    <!-- wp:query {"queryId":1,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","sticky":"","inherit":true},"layout":{"type":"constrained"}} -->
    <div class="wp-block-query">
        <!-- wp:post-template -->
        <!-- wp:group {"style":{"border":{"bottom":{"color":"#e8e4dc","width":"1px"}},"spacing":{"padding":{"bottom":"1.5rem","top":"1.5rem"}}}} -->
        <div class="wp-block-group" style="border-bottom:1px solid #e8e4dc;padding-top:1.5rem;padding-bottom:1.5rem">
            <!-- wp:post-title {"isLink":true,"fontSize":"large"} /-->
            <!-- wp:post-excerpt /-->
            <!-- wp:post-date {"fontSize":"small","textColor":"deep-burgundy"} /-->
        </div>
        <!-- /wp:group -->
        <!-- /wp:post-template -->
        <!-- wp:query-pagination -->
        <!-- wp:query-pagination-previous /-->
        <!-- wp:query-pagination-numbers /-->
        <!-- wp:query-pagination-next /-->
        <!-- /wp:query-pagination -->
    </div>
    <!-- /wp:query -->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

- [ ] **Step 5: Create search.html**

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"},"style":{"spacing":{"padding":{"top":"2rem","bottom":"3rem"}}}} -->
<main class="wp-block-group" style="padding-top:2rem;padding-bottom:3rem">
    <!-- wp:search {"label":"Search","showLabel":false,"placeholder":"Search the esoteric currents...","width":100,"widthUnit":"%","buttonText":"Search","buttonUseIcon":true} /-->
    <!-- wp:query-title {"type":"search"} /-->
    <!-- wp:separator {"className":"is-style-ec-thin-rule"} -->
    <hr class="wp-block-separator has-alpha-channel-opacity is-style-ec-thin-rule"/>
    <!-- /wp:separator -->
    <!-- wp:query {"queryId":2,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","sticky":"","inherit":true},"layout":{"type":"constrained"}} -->
    <div class="wp-block-query">
        <!-- wp:post-template -->
        <!-- wp:group {"style":{"border":{"bottom":{"color":"#e8e4dc","width":"1px"}},"spacing":{"padding":{"bottom":"1.5rem","top":"1.5rem"}}}} -->
        <div class="wp-block-group" style="border-bottom:1px solid #e8e4dc;padding-top:1.5rem;padding-bottom:1.5rem">
            <!-- wp:post-title {"isLink":true,"fontSize":"large"} /-->
            <!-- wp:post-excerpt /-->
        </div>
        <!-- /wp:group -->
        <!-- /wp:post-template -->
    </div>
    <!-- /wp:query -->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

- [ ] **Step 6: Create 404.html**

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"},"style":{"spacing":{"padding":{"top":"6rem","bottom":"6rem"}}}} -->
<main class="wp-block-group" style="padding-top:6rem;padding-bottom:6rem">
    <!-- wp:heading {"textAlign":"center","level":1} -->
    <h1 class="wp-block-heading has-text-align-center">Not Found</h1>
    <!-- /wp:heading -->
    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">The page you're looking for has been lost to the currents. Try searching instead.</p>
    <!-- /wp:paragraph -->
    <!-- wp:search {"label":"Search","showLabel":false,"width":50,"widthUnit":"%","buttonText":"Search"} /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

- [ ] **Step 7: Create submission.html (custom template)**

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"},"style":{"spacing":{"padding":{"top":"3rem","bottom":"3rem"}}}} -->
<main class="wp-block-group" style="padding-top:3rem;padding-bottom:3rem">
    <!-- wp:post-title {"level":1} /-->
    <!-- wp:post-content /-->
    <!-- wp:ec/submission-form {} /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

- [ ] **Step 8: Commit**

```bash
git add theme/templates/
git commit -m "feat(theme): all template files â€” home, single, page, archive, search, 404, submission"
```

---
