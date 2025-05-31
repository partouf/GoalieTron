<?php
/**
 * Plugin Name: Enable Classic Widgets Menu
 * Description: Enables the classic Widgets menu for block themes
 * Version: 1.0
 * Author: GoalieTron Support
 */

// Disable block-based widgets editor
add_filter('use_widgets_block_editor', '__return_false');

// Force show widgets in admin menu even for block themes
add_action('admin_menu', function() {
    global $submenu;
    if (!isset($submenu['themes.php'])) {
        return;
    }
    
    $widget_menu_exists = false;
    foreach ($submenu['themes.php'] as $item) {
        if ($item[2] == 'widgets.php') {
            $widget_menu_exists = true;
            break;
        }
    }
    
    if (!$widget_menu_exists) {
        add_theme_page(
            'Widgets',
            'Widgets',
            'edit_theme_options',
            'widgets.php'
        );
    }
});

// Register a widget area if none exist
add_action('widgets_init', function() {
    // Check if any sidebars are registered
    global $wp_registered_sidebars;
    if (empty($wp_registered_sidebars)) {
        register_sidebar(array(
            'name'          => 'Primary Widget Area',
            'id'            => 'primary-widget-area',
            'description'   => 'The primary widget area',
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="widget-title">',
            'after_title'   => '</h3>',
        ));
    }
});