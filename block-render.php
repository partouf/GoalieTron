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
    // Create a new isolated GoalieTron instance with custom options
    // This avoids loading cache from database that might conflict
    $default_options = array(
        'patreon_userid' => '',
        'design' => 'default',
        'cache' => '',
        'cache_only' => 'no', 
        'cache_age' => 0,
        'title' => '',
        'metercolor' => 'green',
        'toptext' => '',
        'bottomtext' => '',
        'showgoaltext' => 'true',
        'showbutton' => 'false',
        'goal_mode' => 'custom',
        'custom_goal_id' => '',
        'patreon_username' => ''
    );
    
    // Merge attributes with defaults
    $block_options = array_merge($default_options, $attributes);
    
    // Create isolated instance
    $block_goalietron = GoalieTron::CreateInstance($block_options);
    
    
    // Get block wrapper attributes including custom CSS classes
    $wrapper_attributes = get_block_wrapper_attributes(array(
        'class' => 'widget goalietron_widget'
    ));
    
    // Prepare widget args to simulate widget environment
    $widget_args = array(
        'before_widget' => '<div ' . $wrapper_attributes . '>',
        'after_widget' => '</div>',
        'before_title' => '<h2 class="widget-title">',
        'after_title' => '</h2>'
    );
    
    // Capture the widget output using the isolated instance
    ob_start();
    $block_goalietron->DisplayWidget($widget_args);
    $output = ob_get_clean();
    
    return $output;
}