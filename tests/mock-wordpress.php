<?php
/**
 * Mock WordPress environment for testing GoalieTron outside of WordPress
 * 
 * Updated to match WordPress core behavior for:
 * - esc_html() - Escapes content for safe HTML output
 * - esc_attr() - Escapes content for HTML attributes
 * - sanitize_text_field() - Sanitizes string from user input or database
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

function wp_add_inline_script($handle, $data, $position = 'after') {
    global $wp_registered_scripts;
    
    // Assert handle is provided and not empty
    if (empty($handle)) {
        throw new Exception("wp_add_inline_script: Handle cannot be empty");
    }
    
    // Assert data is provided and not empty
    if (empty($data)) {
        throw new Exception("wp_add_inline_script: Data cannot be empty for handle '$handle'");
    }
    
    // Check if script was registered before adding inline script
    if (!isset($wp_registered_scripts[$handle])) {
        throw new Exception("wp_add_inline_script: Script '$handle' must be registered before adding inline script");
    }
    
    // Valid positions are 'before' and 'after'
    if (!in_array($position, ['before', 'after'])) {
        throw new Exception("wp_add_inline_script: Position must be 'before' or 'after', got '$position'");
    }
    
    // Store inline script data for potential validation
    if (!isset($wp_registered_scripts[$handle]['inline'])) {
        $wp_registered_scripts[$handle]['inline'] = [];
    }
    $wp_registered_scripts[$handle]['inline'][$position][] = $data;
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

// Mock WordPress actions/filters with proper execution
$wp_actions = array();
$wp_filters = array();
$wp_actions_done = array();

function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
    global $wp_actions;
    
    // Assert tag is provided and not empty
    if (empty($tag)) {
        throw new Exception("add_action: Action tag cannot be empty");
    }
    
    // Assert function is provided and not empty
    if (empty($function_to_add)) {
        throw new Exception("add_action: Function cannot be empty for action '$tag'");
    }
    
    // Validate priority is numeric
    if (!is_numeric($priority)) {
        throw new Exception("add_action: Priority must be numeric for action '$tag', got: " . gettype($priority));
    }
    
    // Validate accepted_args is a positive integer
    if (!is_int($accepted_args) || $accepted_args < 1) {
        throw new Exception("add_action: Accepted args must be a positive integer for action '$tag', got: $accepted_args");
    }
    
    // Check if function exists (for string function names)
    if (is_string($function_to_add) && !function_exists($function_to_add)) {
        // In WordPress, it's common to register actions for functions that don't exist yet
        // So we'll just log this as a debug message rather than throwing an error
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("add_action: Function '$function_to_add' does not exist yet for action '$tag'");
        }
    }
    
    if (!isset($wp_actions[$tag])) {
        $wp_actions[$tag] = array();
    }
    
    if (!isset($wp_actions[$tag][$priority])) {
        $wp_actions[$tag][$priority] = array();
    }
    
    $wp_actions[$tag][$priority][] = array(
        'function' => $function_to_add,
        'accepted_args' => $accepted_args
    );
}

function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
    global $wp_filters;
    
    // Assert tag is provided and not empty
    if (empty($tag)) {
        throw new Exception("add_filter: Filter tag cannot be empty");
    }
    
    // Assert function is provided and not empty
    if (empty($function_to_add)) {
        throw new Exception("add_filter: Function cannot be empty for filter '$tag'");
    }
    
    // Validate priority is numeric
    if (!is_numeric($priority)) {
        throw new Exception("add_filter: Priority must be numeric for filter '$tag', got: " . gettype($priority));
    }
    
    // Validate accepted_args is a positive integer
    if (!is_int($accepted_args) || $accepted_args < 1) {
        throw new Exception("add_filter: Accepted args must be a positive integer for filter '$tag', got: $accepted_args");
    }
    
    // Check if function exists (for string function names)
    if (is_string($function_to_add) && !function_exists($function_to_add)) {
        // In WordPress, it's common to register filters for functions that don't exist yet
        // (like when plugins register filters in __construct but define functions later)
        // So we'll just log this as a debug message rather than throwing an error
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("add_filter: Function '$function_to_add' does not exist yet for filter '$tag'");
        }
    }
    
    if (!isset($wp_filters[$tag])) {
        $wp_filters[$tag] = array();
    }
    
    if (!isset($wp_filters[$tag][$priority])) {
        $wp_filters[$tag][$priority] = array();
    }
    
    $wp_filters[$tag][$priority][] = array(
        'function' => $function_to_add,
        'accepted_args' => $accepted_args
    );
}

function do_action($tag, ...$args) {
    global $wp_actions, $wp_actions_done;
    
    // Track that this action was done
    if (!isset($wp_actions_done[$tag])) {
        $wp_actions_done[$tag] = 0;
    }
    $wp_actions_done[$tag]++;
    
    // Execute all functions for this action, sorted by priority
    if (isset($wp_actions[$tag])) {
        ksort($wp_actions[$tag]); // Sort by priority
        
        foreach ($wp_actions[$tag] as $priority => $functions) {
            foreach ($functions as $function_data) {
                $function = $function_data['function'];
                $accepted_args = $function_data['accepted_args'];
                
                // Limit args to accepted_args count
                $limited_args = array_slice($args, 0, $accepted_args);
                
                // Call the function
                if (is_callable($function)) {
                    call_user_func_array($function, $limited_args);
                } else {
                    // For testing, we might have string function names that don't exist yet
                    // In a real WordPress environment, this would be an error
                    if (function_exists($function)) {
                        call_user_func_array($function, $limited_args);
                    }
                }
            }
        }
    }
}

function apply_filters($tag, $value, ...$args) {
    global $wp_filters;
    
    // Execute all functions for this filter, sorted by priority
    if (isset($wp_filters[$tag])) {
        ksort($wp_filters[$tag]); // Sort by priority
        
        foreach ($wp_filters[$tag] as $priority => $functions) {
            foreach ($functions as $function_data) {
                $function = $function_data['function'];
                $accepted_args = $function_data['accepted_args'];
                
                // Prepare args: $value is always first, then additional args
                $filter_args = array_merge([$value], array_slice($args, 0, $accepted_args - 1));
                
                // Call the function and update value
                if (is_callable($function)) {
                    $value = call_user_func_array($function, $filter_args);
                } else {
                    if (function_exists($function)) {
                        $value = call_user_func_array($function, $filter_args);
                    }
                }
            }
        }
    }
    
    return $value;
}

function did_action($tag) {
    global $wp_actions_done;
    return isset($wp_actions_done[$tag]) ? $wp_actions_done[$tag] : 0;
}

function has_action($tag, $function_to_check = false) {
    global $wp_actions;
    
    if (!isset($wp_actions[$tag])) {
        return false;
    }
    
    if ($function_to_check === false) {
        return true; // Just check if any actions exist for this tag
    }
    
    // Check if specific function is registered
    foreach ($wp_actions[$tag] as $priority => $functions) {
        foreach ($functions as $function_data) {
            if ($function_data['function'] === $function_to_check) {
                return $priority;
            }
        }
    }
    
    return false;
}

// Don't override error_log - it's a built-in PHP function
// We'll just let the normal error_log work

// Mock other functions that might be needed
function esc_attr($text) {
    // Match WordPress core behavior
    $safe_text = wp_check_invalid_utf8($text);
    $safe_text = _wp_specialchars($safe_text, ENT_QUOTES);
    return apply_filters('esc_attr', $safe_text, $text);
}

function esc_html($text) {
    // Match WordPress core behavior
    $safe_text = wp_check_invalid_utf8($text);
    $safe_text = _wp_specialchars($safe_text, ENT_QUOTES);
    return apply_filters('esc_html', $safe_text, $text);
}

function __($text, $domain = 'default') {
    return $text;
}

function register_block_type($block_type, $args = array()) {
    // Assert block_type is provided and not empty
    if (empty($block_type)) {
        throw new Exception("register_block_type: Block type cannot be empty");
    }
    
    // If block_type is a file path (ends with .json), check if the file exists
    if (is_string($block_type) && substr($block_type, -5) === '.json') {
        if (!file_exists($block_type)) {
            throw new Exception("register_block_type: Block JSON file does not exist: $block_type");
        }
        
        // Parse the block.json to check for referenced files
        $blockJson = file_get_contents($block_type);
        $blockData = json_decode($blockJson, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("register_block_type: Invalid JSON in block file: $block_type");
        }
        
        $blockDir = dirname($block_type);
        
        // Check if render file exists (if specified in block.json)
        if (isset($blockData['render']) && strpos($blockData['render'], 'file:') === 0) {
            $renderFile = $blockDir . '/' . substr($blockData['render'], 5); // Remove 'file:' prefix
            if (!file_exists($renderFile)) {
                throw new Exception("register_block_type: Render file does not exist: $renderFile (specified in $block_type)");
            }
        }
        
        // Check editorScript file if specified in block.json
        if (isset($blockData['editorScript']) && strpos($blockData['editorScript'], 'file:') === 0) {
            $scriptFile = $blockDir . '/' . substr($blockData['editorScript'], 5); // Remove 'file:' prefix
            if (!file_exists($scriptFile)) {
                throw new Exception("register_block_type: Editor script file does not exist: $scriptFile (specified in $block_type)");
            }
        }
        
        // Check style file if specified in block.json
        if (isset($blockData['style']) && strpos($blockData['style'], 'file:') === 0) {
            $styleFile = $blockDir . '/' . substr($blockData['style'], 5); // Remove 'file:' prefix
            if (!file_exists($styleFile)) {
                throw new Exception("register_block_type: Style file does not exist: $styleFile (specified in $block_type)");
            }
        }
        
        // Check editorStyle file if specified in block.json
        if (isset($blockData['editorStyle']) && strpos($blockData['editorStyle'], 'file:') === 0) {
            $editorStyleFile = $blockDir . '/' . substr($blockData['editorStyle'], 5); // Remove 'file:' prefix
            if (!file_exists($editorStyleFile)) {
                throw new Exception("register_block_type: Editor style file does not exist: $editorStyleFile (specified in $block_type)");
            }
        }
    }
    
    // Validate args array if provided
    if (!empty($args)) {
        // Check render_callback file if it's a file path
        if (isset($args['render_callback']) && is_string($args['render_callback']) && strpos($args['render_callback'], '.php') !== false) {
            // This would be a file path, but in WordPress it's typically a function name, so we'll skip this check
        }
        
        // Check editor_script was registered before block registration
        if (isset($args['editor_script'])) {
            global $wp_registered_scripts;
            $script_handle = $args['editor_script'];
            
            if (empty($script_handle)) {
                throw new Exception("register_block_type: editor_script handle cannot be empty");
            }
            
            if (!isset($wp_registered_scripts[$script_handle])) {
                throw new Exception("register_block_type: editor_script '$script_handle' must be registered with wp_register_script() before registering block");
            }
        }
        
        // Check editor_style was registered before block registration (if specified)
        if (isset($args['editor_style'])) {
            global $wp_registered_styles;
            $style_handle = $args['editor_style'];
            
            if (empty($style_handle)) {
                throw new Exception("register_block_type: editor_style handle cannot be empty");
            }
            
            if (!isset($wp_registered_styles[$style_handle])) {
                throw new Exception("register_block_type: editor_style '$style_handle' must be registered with wp_register_style() before registering block");
            }
        }
        
        // Check style was registered before block registration (if specified)
        if (isset($args['style'])) {
            global $wp_registered_styles;
            $style_handle = $args['style'];
            
            if (empty($style_handle)) {
                throw new Exception("register_block_type: style handle cannot be empty");
            }
            
            if (!isset($wp_registered_styles[$style_handle])) {
                throw new Exception("register_block_type: style '$style_handle' must be registered with wp_register_style() before registering block");
            }
        }
        
        // Check script was registered before block registration (if specified)
        if (isset($args['script'])) {
            global $wp_registered_scripts;
            $script_handle = $args['script'];
            
            if (empty($script_handle)) {
                throw new Exception("register_block_type: script handle cannot be empty");
            }
            
            if (!isset($wp_registered_scripts[$script_handle])) {
                throw new Exception("register_block_type: script '$script_handle' must be registered with wp_register_script() before registering block");
            }
        }
    }
    
    return true;
}

function sanitize_text_field($str) {
    // Match WordPress core behavior
    $filtered = _sanitize_text_fields($str, false);
    return apply_filters('sanitize_text_field', $filtered, $str);
}

/**
 * Internal helper function used by sanitize_text_field() and sanitize_textarea_field()
 * Matches WordPress core behavior from formatting.php
 */
function _sanitize_text_fields($str, $keep_newlines = false) {
    if (is_object($str) || is_array($str)) {
        return '';
    }

    $str = (string) $str;

    $filtered = wp_check_invalid_utf8($str);

    if (strpos($filtered, '<') !== false) {
        $filtered = wp_pre_kses_less_than($filtered);
        // This will strip extra whitespace for us.
        $filtered = wp_strip_all_tags($filtered, false);

        // Use HTML entities in a special case to make sure that
        // later newline stripping stages cannot lead to a functional tag.
        $filtered = str_replace("<\n", "&lt;\n", $filtered);
    }

    if (!$keep_newlines) {
        $filtered = preg_replace('/[\r\n\t ]+/', ' ', $filtered);
    }
    $filtered = trim($filtered);

    // Remove percent-encoded characters.
    $found = false;
    while (preg_match('/%[a-f0-9]{2}/i', $filtered, $match)) {
        $filtered = str_replace($match[0], '', $filtered);
        $found = true;
    }

    if ($found) {
        // Strip out the whitespace that may now exist after removing percent-encoded characters.
        $filtered = trim(preg_replace('/ +/', ' ', $filtered));
    }

    return $filtered;
}

/**
 * WordPress core _wp_specialchars function
 * Converts special characters to HTML entities
 */
function _wp_specialchars($text, $quote_style = ENT_NOQUOTES, $charset = false, $double_encode = false) {
    $text = (string) $text;

    if (0 === strlen($text)) {
        return '';
    }

    // Don't bother if there are no specialchars - saves some processing.
    if (!preg_match('/[&<>"\']/', $text)) {
        return $text;
    }

    // For testing, default to UTF-8
    if (!$charset) {
        $charset = 'UTF-8';
    }

    if (!$double_encode) {
        // Decode existing entities first to avoid double encoding
        $text = html_entity_decode($text, $quote_style, $charset);
    }

    $text = htmlspecialchars($text, $quote_style, $charset, $double_encode);

    return $text;
}

/**
 * Check for invalid UTF-8 string
 */
function wp_check_invalid_utf8($text, $strip = false) {
    $text = (string) $text;
    
    if (0 === strlen($text)) {
        return '';
    }

    // Check for invalid UTF-8
    if (!mb_check_encoding($text, 'UTF-8')) {
        if ($strip) {
            return '';
        } else {
            return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }
    }

    return $text;
}

/**
 * Convert lone less than signs
 */
function wp_pre_kses_less_than($text) {
    return preg_replace_callback('/<[^>]*?((?=<)|>|$)/', 'wp_pre_kses_less_than_callback', $text);
}

function wp_pre_kses_less_than_callback($matches) {
    if (false === strpos($matches[0], '>')) {
        return esc_html($matches[0]);
    }
    return $matches[0];
}

/**
 * Properly strip all HTML tags including script and style
 */
function wp_strip_all_tags($text, $remove_breaks = false) {
    $text = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $text);
    $text = strip_tags($text);

    if ($remove_breaks) {
        $text = preg_replace('/[\r\n\t ]+/', ' ', $text);
    }

    return trim($text);
}

function get_block_wrapper_attributes($args = array()) {
    // Mock implementation of get_block_wrapper_attributes
    $attributes = array();
    
    // Handle class attribute
    if (isset($args['class'])) {
        $attributes['class'] = $args['class'];
    }
    
    // Mock custom class name support
    global $mock_block_custom_class;
    if (!empty($mock_block_custom_class)) {
        $existing_class = isset($attributes['class']) ? $attributes['class'] : '';
        $attributes['class'] = trim($existing_class . ' ' . $mock_block_custom_class);
    }
    
    // Convert attributes array to string
    $attr_string = '';
    foreach ($attributes as $key => $value) {
        $attr_string .= $key . '="' . esc_attr($value) . '"';
    }
    
    return $attr_string;
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

// Helper function to reset WordPress actions/filters for testing
function reset_wp_actions() {
    global $wp_actions, $wp_filters, $wp_actions_done;
    $wp_actions = array();
    $wp_filters = array();
    $wp_actions_done = array();
}

// Helper function to reset all WordPress state for testing
function reset_wp_state() {
    reset_wp_options();
    reset_wp_assets();
    reset_wp_actions();
    reset_mock_block_custom_class();
}

// Helper function to simulate WordPress initialization for testing
function simulate_wp_init() {
    // Trigger the init action to register blocks and other components
    do_action('init');
}

// Helper function to get registered actions for testing
function get_registered_actions($tag = null) {
    global $wp_actions;
    return $tag ? (isset($wp_actions[$tag]) ? $wp_actions[$tag] : array()) : $wp_actions;
}

// Helper function to get registered filters for testing
function get_registered_filters($tag = null) {
    global $wp_filters;
    return $tag ? (isset($wp_filters[$tag]) ? $wp_filters[$tag] : array()) : $wp_filters;
}

// Helper function to get action execution count for testing
function get_action_done_count($tag) {
    global $wp_actions_done;
    return isset($wp_actions_done[$tag]) ? $wp_actions_done[$tag] : 0;
}

// Helper function to set options for testing
function set_test_option($name, $value) {
    global $wp_options;
    $wp_options[$name] = $value;
}

// Helper function to set custom block CSS class for testing
function set_mock_block_custom_class($class_name) {
    global $mock_block_custom_class;
    $mock_block_custom_class = $class_name;
}

// Helper function to reset custom block CSS class for testing
function reset_mock_block_custom_class() {
    global $mock_block_custom_class;
    $mock_block_custom_class = '';
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