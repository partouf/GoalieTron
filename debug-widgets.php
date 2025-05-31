<?php
/**
 * Debug helper for GoalieTron widget issues
 * Add this to your theme's functions.php temporarily or activate as a mu-plugin
 */

// Enable error reporting
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}
if (!defined('WP_DEBUG_DISPLAY')) {
    define('WP_DEBUG_DISPLAY', true);
}

// Log widget registration
add_action('widgets_init', function() {
    error_log('GoalieTron Debug: widgets_init hook fired');
    
    global $wp_widget_factory;
    if (isset($wp_widget_factory->widgets['GoalieTron_Widget'])) {
        error_log('GoalieTron Debug: Widget is registered');
    } else {
        error_log('GoalieTron Debug: Widget NOT registered');
    }
}, 20);

// Check if GoalieTron is active
add_action('admin_init', function() {
    if (!is_plugin_active('goalietron/goalietron.php')) {
        error_log('GoalieTron Debug: Plugin not active');
    } else {
        error_log('GoalieTron Debug: Plugin is active');
        
        // Check if classes exist
        if (class_exists('GoalieTron')) {
            error_log('GoalieTron Debug: GoalieTron class exists');
        }
        if (class_exists('GoalieTron_Widget')) {
            error_log('GoalieTron Debug: GoalieTron_Widget class exists');
        }
        if (class_exists('PatreonClient')) {
            error_log('GoalieTron Debug: PatreonClient class exists');
        }
    }
});

// Add admin notice for widget page issues
add_action('admin_notices', function() {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'widgets') {
        // Check for required files
        $plugin_dir = WP_PLUGIN_DIR . '/goalietron/';
        $required_files = [
            'goalietron.php',
            'PatreonClient.php',
            'views/widget-form.html'
        ];
        
        foreach ($required_files as $file) {
            if (!file_exists($plugin_dir . $file)) {
                echo '<div class="notice notice-error"><p>GoalieTron Error: Missing required file: ' . $file . '</p></div>';
            }
        }
        
        // Check if theme has widget areas
        global $wp_registered_sidebars;
        if (empty($wp_registered_sidebars)) {
            echo '<div class="notice notice-warning"><p>No widget areas registered. Your theme may need widget area support.</p></div>';
        }
    }
});