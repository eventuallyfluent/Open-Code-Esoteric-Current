<?php
namespace ObservatoryIndex;

add_action('after_setup_theme', function () {
    add_editor_style('assets/editor.css');
    add_theme_support('wp-block-styles');
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');

    register_block_style('core/separator', [
        'name' => 'ec-thin-rule',
        'label' => __('Thin Rule', 'observatory-index'),
    ]);

    register_block_style('core/separator', [
        'name' => 'ec-dashed-rule',
        'label' => __('Dashed Rule', 'observatory-index'),
    ]);
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'observatory-index-google-fonts',
        'https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=Lora:ital,wght@0,400;0,500;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap',
        [],
        null
    );
    wp_enqueue_style(
        'observatory-index-theme',
        get_template_directory_uri() . '/assets/theme.css',
        ['observatory-index-google-fonts'],
        wp_get_theme()->get('Version')
    );
});

add_action('init', function () {
    $patterns_dir = get_template_directory() . '/patterns';
    foreach (glob($patterns_dir . '/*.php') as $file) {
        require_once $file;
    }
});

add_action('after_switch_theme', function () {
    $pages = [
        'submissions' => 'Submit a Source',
        'about'       => 'About',
        'privacy'     => 'Privacy Policy',
        'terms'       => 'Terms of Use',
        'contact'     => 'Contact',
        'subscribe'   => 'Subscribe',
    ];
    foreach ($pages as $slug => $title) {
        $existing = get_page_by_path($slug);
        if (!$existing) {
            wp_insert_post([
                'post_title'   => $title,
                'post_name'    => $slug,
                'post_content' => '',
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ]);
        }
    }
});
