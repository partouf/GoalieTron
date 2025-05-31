<?php
/**
 * GoalieTron Debug Script
 * 
 * This script helps identify issues that might cause 500 errors in the WordPress widgets.php page
 * 
 * Usage: Add this file to the plugin directory and include it in goalietron.php temporarily
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', plugin_dir_path(__FILE__) . 'goalietron-errors.log');

// Custom error handler
function goalietron_error_handler($errno, $errstr, $errfile, $errline) {
    $error_message = "GoalieTron Error: [$errno] $errstr in $errfile on line $errline\n";
    error_log($error_message);
    
    // Also display errors if WP_DEBUG is true
    if (defined('WP_DEBUG') && WP_DEBUG) {
        echo "<!-- GoalieTron Debug: $error_message -->\n";
    }
    
    // Don't execute PHP internal error handler
    return true;
}

// Custom exception handler
function goalietron_exception_handler($exception) {
    $error_message = "GoalieTron Exception: " . $exception->getMessage() . 
                    " in " . $exception->getFile() . 
                    " on line " . $exception->getLine() . 
                    "\nTrace:\n" . $exception->getTraceAsString() . "\n";
    error_log($error_message);
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        echo "<!-- GoalieTron Debug Exception: " . htmlspecialchars($error_message) . " -->\n";
    }
}

// Set error handlers
set_error_handler("goalietron_error_handler");
set_exception_handler("goalietron_exception_handler");

// Debug function to log GoalieTron-specific messages
function goalietron_debug($message, $data = null) {
    $debug_message = "[" . date('Y-m-d H:i:s') . "] GoalieTron Debug: " . $message;
    if ($data !== null) {
        $debug_message .= " - Data: " . print_r($data, true);
    }
    error_log($debug_message . "\n", 3, plugin_dir_path(__FILE__) . 'goalietron-debug.log');
}

// Check for common issues
function goalietron_check_issues() {
    $issues = [];
    
    // Check if required files exist
    $required_files = [
        'PatreonClient.php',
        'views/widget-form.html',
        'views/config.html'
    ];
    
    foreach ($required_files as $file) {
        $file_path = plugin_dir_path(__FILE__) . $file;
        if (!file_exists($file_path)) {
            $issues[] = "Missing required file: $file";
        }
    }
    
    // Check PHP version
    if (version_compare(PHP_VERSION, '5.6.0', '<')) {
        $issues[] = "PHP version too old. Required: 5.6.0+, Current: " . PHP_VERSION;
    }
    
    // Check if WordPress functions are available
    if (!function_exists('add_action')) {
        $issues[] = "WordPress functions not available - plugin may be loading incorrectly";
    }
    
    // Check memory limit
    $memory_limit = ini_get('memory_limit');
    $memory_limit_bytes = wp_convert_hr_to_bytes($memory_limit);
    if ($memory_limit_bytes < 67108864) { // Less than 64MB
        $issues[] = "Low memory limit: $memory_limit (recommended: 64M or higher)";
    }
    
    return $issues;
}

// Log initialization
goalietron_debug("GoalieTron Debug initialized");
goalietron_debug("PHP Version", PHP_VERSION);
goalietron_debug("WordPress Version", get_bloginfo('version'));
goalietron_debug("Plugin Directory", plugin_dir_path(__FILE__));

// Check for issues on initialization
$startup_issues = goalietron_check_issues();
if (!empty($startup_issues)) {
    goalietron_debug("Startup issues detected", $startup_issues);
}

// Hook to check widget registration issues
add_action('widgets_init', function() {
    goalietron_debug("widgets_init hook called");
    
    // Check if GoalieTron_Widget class exists
    if (!class_exists('GoalieTron_Widget')) {
        goalietron_debug("ERROR: GoalieTron_Widget class not found!");
    } else {
        goalietron_debug("GoalieTron_Widget class exists");
    }
    
    // Check if WP_Widget is available
    if (!class_exists('WP_Widget')) {
        goalietron_debug("ERROR: WP_Widget class not available!");
    }
}, 1); // Run early to catch issues

// Log when the plugin file is included
goalietron_debug("Plugin file included from", debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2));