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
    // Mock function - no-op for testing
}

function wp_enqueue_style($handle) {
    // Mock function - no-op for testing
}

function wp_register_script($handle, $src, $deps = array(), $ver = false, $in_footer = false) {
    // Mock function - no-op for testing
}

function wp_enqueue_script($handle) {
    // Mock function - no-op for testing
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

// Helper function to set options for testing
function set_test_option($name, $value) {
    global $wp_options;
    $wp_options[$name] = $value;
}