<?php
/**
 * GoalieTron Block Render Callback
 * 
 * This file handles the server-side rendering of the GoalieTron block.
 * It reuses the existing widget functionality to maintain compatibility.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render callback for the GoalieTron block
 *
 * @param array $attributes The block attributes
 * @param string $content The block content (unused for server-side render)
 * @return string The rendered block HTML
 */
function goalietron_block_render_callback($attributes, $content) {
    // Get the GoalieTron instance
    $goalietron = GoalieTron::Instance();
    
    // Save original options to restore later
    $original_options = $goalietron->options;
    
    // Apply block attributes to the GoalieTron options
    $block_options = wp_parse_args($attributes, $goalietron->options);
    
    // Override the options temporarily
    foreach ($block_options as $key => $value) {
        if (array_key_exists($key, $goalietron->options)) {
            $goalietron->options[$key] = $value;
        }
    }
    
    // Prepare widget args to simulate widget environment
    $widget_args = array(
        'before_widget' => '<div class="wp-block-goalietron-goalietron-block widget goalietron_widget">',
        'after_widget' => '</div>',
        'before_title' => '<h2 class="widget-title">',
        'after_title' => '</h2>'
    );
    
    // Capture the widget output
    ob_start();
    $goalietron->DisplayWidget($widget_args);
    $output = ob_get_clean();
    
    // Restore original options
    $goalietron->options = $original_options;
    
    return $output;
}