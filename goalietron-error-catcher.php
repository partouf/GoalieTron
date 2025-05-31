<?php
/**
 * GoalieTron Error Catcher
 * 
 * This file should be placed in wp-content/mu-plugins/ to catch errors early
 * It will help diagnose 500 errors that occur before normal plugins load
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to users
ini_set('log_errors', 1);

// Custom error handler for GoalieTron-related issues
register_shutdown_function(function() {
    $error = error_get_last();
    
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Check if error is related to GoalieTron
        if (strpos($error['file'], 'goalietron') !== false || 
            strpos($error['message'], 'GoalieTron') !== false ||
            strpos($error['message'], 'PatreonClient') !== false) {
            
            $log_message = sprintf(
                "GoalieTron Fatal Error: %s in %s on line %d\n",
                $error['message'],
                $error['file'],
                $error['line']
            );
            
            // Log to WordPress debug.log if available
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log($log_message);
            }
            
            // Also log to a dedicated file
            $log_file = WP_CONTENT_DIR . '/goalietron-fatal-errors.log';
            error_log(date('[Y-m-d H:i:s] ') . $log_message, 3, $log_file);
            
            // If we're in the admin area and on widgets.php, show a friendly error
            if (is_admin() && strpos($_SERVER['REQUEST_URI'], 'widgets.php') !== false) {
                // Clear any output buffers
                while (ob_get_level()) {
                    ob_end_clean();
                }
                
                // Show a proper error page
                wp_die(
                    '<h1>GoalieTron Plugin Error</h1>' .
                    '<p>The GoalieTron plugin encountered an error that prevents the widgets page from loading.</p>' .
                    '<p><strong>Error:</strong> ' . esc_html($error['message']) . '</p>' .
                    '<p><strong>File:</strong> ' . esc_html($error['file']) . ' (line ' . esc_html($error['line']) . ')</p>' .
                    '<p>Please check the error logs or temporarily deactivate the GoalieTron plugin.</p>' .
                    '<p><a href="' . admin_url('plugins.php') . '">Go to Plugins</a></p>',
                    'GoalieTron Error',
                    ['response' => 500]
                );
            }
        }
    }
});

// Monitor widget registration issues
add_action('widgets_init', function() {
    // Check if we're on the widgets page
    if (is_admin() && strpos($_SERVER['REQUEST_URI'], 'widgets.php') !== false) {
        // Log what widgets are being registered
        ob_start();
        $registered = did_action('widgets_init');
        $output = ob_get_clean();
        
        if ($output) {
            error_log('GoalieTron Debug - Widget registration output: ' . $output);
        }
    }
}, 1);