<?php
/**
 * Test script to verify GoalieTron widget registration
 * 
 * This can be run as a standalone WordPress plugin or included in your theme's functions.php
 */

// Test if GoalieTron is active and widget is registered
add_action('admin_init', function() {
    // Only run on widgets.php page
    if (!isset($_GET['page']) && strpos($_SERVER['REQUEST_URI'], 'widgets.php') !== false) {
        
        // Check if GoalieTron plugin is active
        if (!is_plugin_active('goalietron/goalietron.php')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning"><p>GoalieTron plugin is not active!</p></div>';
            });
            return;
        }
        
        // Check if widget is registered
        global $wp_widget_factory;
        
        if (isset($wp_widget_factory->widgets['GoalieTron_Widget'])) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>GoalieTron widget is properly registered!</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                global $wp_widget_factory;
                echo '<div class="notice notice-error"><p>GoalieTron widget is NOT registered!</p>';
                echo '<p>Registered widgets: ' . implode(', ', array_keys($wp_widget_factory->widgets)) . '</p></div>';
            });
        }
        
        // Check for PHP errors
        $error = error_get_last();
        if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
            add_action('admin_notices', function() use ($error) {
                echo '<div class="notice notice-error"><p>PHP Error detected: ' . 
                     esc_html($error['message']) . ' in ' . esc_html($error['file']) . 
                     ' on line ' . esc_html($error['line']) . '</p></div>';
            });
        }
    }
});

// Alternative hook to ensure widget registration
add_action('widgets_init', function() {
    // Force registration if not already done
    if (!class_exists('GoalieTron_Widget') && defined('WP_PLUGIN_DIR')) {
        $plugin_file = WP_PLUGIN_DIR . '/goalietron/goalietron.php';
        if (file_exists($plugin_file)) {
            require_once $plugin_file;
        }
    }
    
    // Try to register the widget if class exists but not registered
    if (class_exists('GoalieTron_Widget') && !is_registered_widget('GoalieTron_Widget')) {
        register_widget('GoalieTron_Widget');
        error_log('GoalieTron: Manually registered widget');
    }
}, 999); // Run late to ensure everything is loaded

// Function to check if widget is registered
function is_registered_widget($widget_class) {
    global $wp_widget_factory;
    return isset($wp_widget_factory->widgets[$widget_class]);
}