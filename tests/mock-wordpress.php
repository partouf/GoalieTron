<?php
/**
 * Mock WordPress environment for testing GoalieTron outside of WordPress
 */

// Define WordPress constants
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

// Mock WordPress database options storage
$wp_options = array();

// Track registered and enqueued handles for validation
$wp_registered_styles = array();
$wp_registered_scripts = array();
$wp_enqueued_styles = array();
$wp_enqueued_scripts = array();

// Mock WordPress functions
function get_option($option_name, $default = false) {
    global $wp_options;
    return isset($wp_options[$option_name]) ? $wp_options[$option_name] : $default;
}

function add_option($option_name, $value, $deprecated = '', $autoload = 'yes') {
    global $wp_options;
    if (!isset($wp_options[$option_name])) {
        $wp_options[$option_name] = $value;
        return true;
    }
    return false;
}

function update_option($option_name, $value) {
    global $wp_options;
    $wp_options[$option_name] = $value;
    return true;
}

function plugin_dir_path($file) {
    return dirname($file) . '/';
}

function plugin_dir_url($file) {
    return 'http://localhost/wp-content/plugins/goalietron/';
}

function wp_register_style($handle, $src, $deps = array(), $ver = false, $media = 'all') {
    global $wp_registered_styles;
    
    // Assert handle is provided and not empty
    if (empty($handle)) {
        throw new Exception("wp_register_style: Handle cannot be empty");
    }
    
    // Assert src is provided and not empty
    if (empty($src)) {
        throw new Exception("wp_register_style: Source URL cannot be empty for handle '$handle'");
    }
    
    // Check if file exists (convert URL to file path for local files)
    if (strpos($src, 'http://localhost/wp-content/plugins/goalietron/') === 0) {
        $file_path = str_replace('http://localhost/wp-content/plugins/goalietron/', dirname(__DIR__) . '/', $src);
        if (!file_exists($file_path)) {
            throw new Exception("wp_register_style: CSS file does not exist: $file_path (handle: $handle)");
        }
    }
    
    // WordPress allows re-registering the same handle (overwrites previous registration)
    // So we don't need to check for existing handles here
    
    // Register the style
    $wp_registered_styles[$handle] = array(
        'src' => $src,
        'deps' => $deps,
        'ver' => $ver,
        'media' => $media
    );
}

function wp_enqueue_style($handle) {
    global $wp_registered_styles, $wp_enqueued_styles;
    
    // Assert handle is provided and not empty
    if (empty($handle)) {
        throw new Exception("wp_enqueue_style: Handle cannot be empty");
    }
    
    // Check if style was registered before enqueuing
    if (!isset($wp_registered_styles[$handle])) {
        throw new Exception("wp_enqueue_style: Style '$handle' must be registered before being enqueued");
    }
    
    // WordPress typically ignores duplicate enqueue calls, so we'll just track it
    // but not throw an error (this matches WordPress behavior)
    
    // Enqueue the style
    $wp_enqueued_styles[$handle] = true;
}

function wp_register_script($handle, $src, $deps = array(), $ver = false, $in_footer = false) {
    global $wp_registered_scripts;
    
    // Assert handle is provided and not empty
    if (empty($handle)) {
        throw new Exception("wp_register_script: Handle cannot be empty");
    }
    
    // Assert src is provided and not empty
    if (empty($src)) {
        throw new Exception("wp_register_script: Source URL cannot be empty for handle '$handle'");
    }
    
    // Check if file exists (convert URL to file path for local files)
    if (strpos($src, 'http://localhost/wp-content/plugins/goalietron/') === 0) {
        $file_path = str_replace('http://localhost/wp-content/plugins/goalietron/', dirname(__DIR__) . '/', $src);
        if (!file_exists($file_path)) {
            throw new Exception("wp_register_script: JS file does not exist: $file_path (handle: $handle)");
        }
    }
    
    // WordPress allows re-registering the same handle (overwrites previous registration)
    // So we don't need to check for existing handles here
    
    // Register the script
    $wp_registered_scripts[$handle] = array(
        'src' => $src,
        'deps' => $deps,
        'ver' => $ver,
        'in_footer' => $in_footer
    );
}

function wp_enqueue_script($handle) {
    global $wp_registered_scripts, $wp_enqueued_scripts;
    
    // Assert handle is provided and not empty
    if (empty($handle)) {
        throw new Exception("wp_enqueue_script: Handle cannot be empty");
    }
    
    // Check if script was registered before enqueuing
    if (!isset($wp_registered_scripts[$handle])) {
        throw new Exception("wp_enqueue_script: Script '$handle' must be registered before being enqueued");
    }
    
    // WordPress typically ignores duplicate enqueue calls, so we'll just track it
    // but not throw an error (this matches WordPress behavior)
    
    // Enqueue the script
    $wp_enqueued_scripts[$handle] = true;
}

function wp_parse_args($args, $defaults = '') {
    if (is_object($args)) {
        $r = get_object_vars($args);
    } elseif (is_array($args)) {
        $r =& $args;
    } else {
        parse_str($args, $r);
    }

    if (is_array($defaults)) {
        return array_merge($defaults, $r);
    }
    return $r;
}

// Widget functionality removed - GoalieTron is now block-only

// Mock WordPress actions/filters
$wp_actions = array();
$wp_filters = array();

function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
    global $wp_actions;
    $wp_actions[$tag][] = $function_to_add;
}

function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
    global $wp_filters;
    $wp_filters[$tag][] = $function_to_add;
}

function did_action($tag) {
    return false; // Always return false for testing
}

// Don't override error_log - it's a built-in PHP function
// We'll just let the normal error_log work

// Mock other functions that might be needed
function esc_attr($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function esc_html($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function __($text, $domain = 'default') {
    return $text;
}

function register_block_type($block_type, $args = array()) {
    // Mock function - no-op for testing
    return true;
}

function sanitize_text_field($str) {
    return trim(strip_tags($str));
}

// Helper function to reset options for testing
function reset_wp_options() {
    global $wp_options;
    $wp_options = array();
}

// Helper function to reset WordPress script/style tracking for testing
function reset_wp_assets() {
    global $wp_registered_styles, $wp_registered_scripts, $wp_enqueued_styles, $wp_enqueued_scripts;
    $wp_registered_styles = array();
    $wp_registered_scripts = array();
    $wp_enqueued_styles = array();
    $wp_enqueued_scripts = array();
}

// Helper function to reset all WordPress state for testing
function reset_wp_state() {
    reset_wp_options();
    reset_wp_assets();
}

// Helper function to set options for testing
function set_test_option($name, $value) {
    global $wp_options;
    $wp_options[$name] = $value;
}

// Helper functions to get asset registration state for testing
function get_registered_styles() {
    global $wp_registered_styles;
    return $wp_registered_styles;
}

function get_registered_scripts() {
    global $wp_registered_scripts;
    return $wp_registered_scripts;
}

function get_enqueued_styles() {
    global $wp_enqueued_styles;
    return $wp_enqueued_styles;
}

function get_enqueued_scripts() {
    global $wp_enqueued_scripts;
    return $wp_enqueued_scripts;
}