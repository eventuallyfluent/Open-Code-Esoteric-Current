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
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('observatory-index-google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@400;600;700&display=swap', [], null);
    wp_enqueue_style('observatory-index-theme', get_template_directory_uri() . '/assets/theme.css', ['observatory-index-google-fonts'], wp_get_theme()->get('Version'));
});
