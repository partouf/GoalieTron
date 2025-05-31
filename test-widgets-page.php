<?php
/**
 * Plugin Name: Test Widgets Page Access
 * Description: Tests if widgets.php can be accessed without errors
 * Version: 1.0
 */

// Hook into admin_init to test widget page access
add_action('admin_init', function() {
    $current_page = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    
    if (strpos($current_page, 'widgets.php') !== false) {
        // We're on the widgets page
        error_log('=== WIDGETS PAGE DEBUG ===');
        error_log('Memory Limit: ' . ini_get('memory_limit'));
        error_log('PHP Version: ' . PHP_VERSION);
        
        // Check registered widgets
        global $wp_widget_factory;
        if ($wp_widget_factory && isset($wp_widget_factory->widgets)) {
            error_log('Registered widgets: ' . count($wp_widget_factory->widgets));
            foreach ($wp_widget_factory->widgets as $id => $widget) {
                error_log('Widget: ' . $id);
            }
        }
        
        // Check for widget areas
        global $wp_registered_sidebars;
        error_log('Registered sidebars: ' . count($wp_registered_sidebars));
        
        // Check if theme supports widgets
        if (!current_theme_supports('widgets')) {
            // Force widget support
            add_theme_support('widgets');
            error_log('Added widget support to theme');
        }
    }
});

// Ensure at least one widget area exists
add_action('widgets_init', function() {
    global $wp_registered_sidebars;
    
    if (empty($wp_registered_sidebars)) {
        register_sidebar(array(
            'name'          => __('Default Widget Area', 'goalietron'),
            'id'            => 'default-widget-area',
            'description'   => __('Default widget area for themes without widget support', 'goalietron'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ));
        error_log('Registered default widget area');
    }
}, 5); // Run early