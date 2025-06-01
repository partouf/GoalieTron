<?php
/**
 * @package goalietron
 */
/*
Plugin Name: GoalieTron
Plugin URI: http://wordpress.org/plugins/goalietron/
Description: A WordPress block plugin that displays your Patreon goals and pledge progress.
Author: Partouf
Version: 2.0
Author URI: https://github.com/partouf
*/

// Include debug script temporarily for troubleshooting
if (file_exists(__DIR__ . '/goalietron-debug.php')) {
    require_once __DIR__ . '/goalietron-debug.php';
}

// Check for required files before including
if (!file_exists(__DIR__ . '/PatreonClient.php')) {
    if (is_admin()) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>GoalieTron Error: PatreonClient.php file is missing. Please ensure all plugin files are properly uploaded.</p></div>';
        });
    }
    error_log('GoalieTron Error: PatreonClient.php not found!');
    return;
}
require_once __DIR__ . '/PatreonClient.php';

class GoalieTron
{
    private static $instance;
    public $options;
    private $patreonClient;

    const OptionPrefix = "goalietron_";
    const MainJSFile = "goalietron.js";

    public static function Instance()
    {
        if (empty(GoalieTron::$instance)) {
            GoalieTron::$instance = new GoalieTron();
        }

        return GoalieTron::$instance;
    }

    public function __construct()
    {
        $this->options = array(
            "patreon_userid" => "",
            "design" => "default",
            "cache" => "",
            "cache_only" => "no",
            "cache_age" => 0,
            "title" => "",
            "metercolor" => "green",
            "toptext" => "",
            "bottomtext" => "",
            "showgoaltext" => "true",
            "showbutton" => "false",
            "goal_mode" => "custom",
            "custom_goal_id" => "",
            "patreon_username" => ""
        );

        $this->patreonClient = new PatreonClient();
        $this->patreonClient->setCacheTimeout(60);
        $this->patreonClient->setFetchTimeout(3);
        $this->loadCustomGoals();
        
        $this->LoadOptions();
    }

    private function LoadOptions()
    {
        foreach ($this->options as $option_name => $option_value) {
            $stored_value = get_option(self::OptionPrefix . $option_name);
            if ($stored_value !== false) {
                $this->options[$option_name] = $stored_value;
            } else {
                add_option(self::OptionPrefix . $option_name, $option_value, null);
            }
        }

        if (empty($this->options['metercolor'])) {
            $this->options['metercolor'] = "green";
            $this->SaveOptions("metercolor");
        }
    }
    
    // Add a method to create instance with custom options (for blocks)
    public static function CreateInstance($custom_options = array())
    {
        $instance = new GoalieTron();
        
        // Override with custom options (but don't load from database)
        foreach ($custom_options as $key => $value) {
            if (array_key_exists($key, $instance->options)) {
                // Sanitize input based on option type
                $instance->options[$key] = $instance->sanitizeOption($key, $value);
            }
        }
        
        // Ensure custom goals are loaded for custom mode blocks
        if ($instance->options['goal_mode'] === 'custom') {
            $instance->loadCustomGoals();
        }
        
        return $instance;
    }

    /**
     * Sanitize option values based on their type and expected format
     * 
     * @param string $key Option name
     * @param mixed $value Option value
     * @return mixed Sanitized value
     */
    private function sanitizeOption($key, $value)
    {
        switch ($key) {
            case 'patreon_userid':
            case 'custom_goal_id':
                // Alphanumeric IDs only
                return preg_replace('/[^a-zA-Z0-9_-]/', '', $value);
                
            case 'patreon_username':
                // Patreon usernames can contain letters, numbers, underscores, hyphens
                return preg_replace('/[^a-zA-Z0-9_-]/', '', $value);
                
            case 'design':
                // Only allow predefined design values
                $valid_designs = array('default', 'fancy', 'minimal', 'streamlined', 'reversed', 'swapped');
                return in_array($value, $valid_designs) ? $value : 'default';
                
            case 'metercolor':
                // Only allow predefined color values
                $valid_colors = array('green', 'orange', 'red', 'blue');
                return in_array($value, $valid_colors) ? $value : 'green';
                
            case 'goal_mode':
                // Only allow predefined goal modes
                $valid_modes = array('legacy', 'custom');
                return in_array($value, $valid_modes) ? $value : 'custom';
                
            case 'showgoaltext':
            case 'showbutton':
            case 'cache_only':
                // Boolean-like values
                return in_array($value, array('true', 'false')) ? $value : 'false';
                
            case 'cache_age':
                // Integer timestamp
                return intval($value);
                
            case 'title':
            case 'toptext':
            case 'bottomtext':
                // Text fields - sanitize for safe storage and display
                return sanitize_text_field($value);
                
            case 'cache':
                // JSON data - validate it's valid JSON
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    return (json_last_error() === JSON_ERROR_NONE) ? $value : '';
                }
                return '';
                
            default:
                // Unknown option - sanitize as text field
                return sanitize_text_field($value);
        }
    }

    private function SaveOptions($specificSetting = null)
    {
        if (!is_null($specificSetting)) {
            update_option(self::OptionPrefix . $specificSetting, $this->options[$specificSetting]);
        } else {
            foreach ($this->options as $option_name => $option_value) {
                update_option(self::OptionPrefix . $option_name, $option_value);
            }
        }
    }

    private function loadCustomGoals()
    {
        try {
            // Validate file path is within plugin directory
            $pluginDir = plugin_dir_path(__FILE__);
            $goalsFile = $pluginDir . 'patreon-goals.json';
            
            // Security check: ensure file is within plugin directory
            $realPluginDir = realpath($pluginDir);
            $realGoalsFile = realpath($goalsFile);
            
            if ($realGoalsFile === false || strpos($realGoalsFile, $realPluginDir) !== 0) {
                error_log('GoalieTron Security Error: patreon-goals.json file path is outside plugin directory');
                return;
            }
            
            if (file_exists($goalsFile) && method_exists($this->patreonClient, 'loadCustomGoalsFromFile')) {
                $this->patreonClient->loadCustomGoalsFromFile($goalsFile);
            }
        } catch (Exception $e) {
            error_log('GoalieTron Error loading custom goals: ' . $e->getMessage());
        }
    }

    public function getAvailableCustomGoals()
    {
        return $this->patreonClient->getCustomGoals();
    }

    public function DisplayWidget($args)
    {
        $cssfilename = self::OptionPrefix . $this->options['design'] . ".css";

        wp_register_style($cssfilename, plugin_dir_url(__FILE__) . "_inc/" . $cssfilename);
        wp_enqueue_style($cssfilename);

        wp_register_script(self::MainJSFile, plugin_dir_url(__FILE__) . "_inc/" . self::MainJSFile, array(), filemtime(plugin_dir_path(__FILE__) . "_inc/" . self::MainJSFile), true);
        wp_enqueue_script(self::MainJSFile);

        echo $args['before_widget'];
        echo $args['before_title'] . esc_html($this->options['title']);
        echo $args['after_title'];

        $configView = file_get_contents(__DIR__ . "/views/design_" . $this->options['design'] . ".html");

        $buttonhtml = "";
        if ($this->options['showbutton'] != "false") {
            $buttonhtml = file_get_contents(__DIR__ . "/views/button.html");
        }
        $configView = str_replace("{goalietron_button}", $buttonhtml, $configView);

        foreach ($this->options as $option_name => $option_value) {
            // Escape output based on context - most template variables are used in HTML context
            // Don't escape certain fields that need raw output
            if (in_array($option_name, array('cache', 'cache_age', 'cache_only'))) {
                $escaped_value = $option_value;
            } else {
                $escaped_value = esc_html($option_value);
            }
            $configView = str_replace("{" . $option_name . "}", $escaped_value, $configView);
        }

        $patreonData = $this->GetPatreonData();
        
        // Calculate server-side preview values for WordPress editor
        $serverSideValues = $this->calculateServerSidePreviewData($patreonData);
        
        // Create unique ID for this widget instance to avoid variable conflicts
        $widgetId = 'gt_' . uniqid();
        
        // Replace the generic PatreonData variable with unique one and add widget ID
        $configView = str_replace('PatreonData', $widgetId . '_PatreonData', $configView);
        $configView = str_replace('GoalieTronShowGoalText', $widgetId . '_ShowGoalText', $configView);
        
        // Add widget ID for JavaScript to use
        $configView = str_replace('<script language="JavaScript">', '<script language="JavaScript" data-widget-id="' . $widgetId . '">', $configView);
        
        $configView = str_replace("{goalietron_json}", $patreonData, $configView);
        
        // Inject server-side calculated values for better editor preview
        $configView = str_replace('<span class="goalietron_goalmoneytext"></span>', 
                                 '<span class="goalietron_goalmoneytext">' . esc_html($serverSideValues['goalText']) . '</span>', 
                                 $configView);
        $configView = str_replace('<span style="width: 0%"></span>', 
                                 '<span style="width: ' . esc_attr($serverSideValues['progressPercent']) . '%"></span>', 
                                 $configView);

        echo "<div>";
        echo $configView;
        echo "</div>";

        echo $args['after_widget'];
    }
    
    /**
     * Calculate server-side preview data for WordPress editor
     * This provides basic goal text and progress percentage so the widget
     * doesn't look empty in the editor before JavaScript runs
     */
    private function calculateServerSidePreviewData($patreonDataJson) {
        $result = array(
            'goalText' => '',
            'progressPercent' => 0
        );
        
        try {
            $patreonData = json_decode($patreonDataJson, true);
            
            if (!$patreonData || !isset($patreonData['included'])) {
                return $result;
            }
            
            // Find campaign and goal data
            $campaignData = null;
            $goalData = null;
            
            foreach ($patreonData['included'] as $item) {
                if ($item['type'] === 'campaign') {
                    $campaignData = $item['attributes'];
                } elseif ($item['type'] === 'goal') {
                    $goalData = $item['attributes'];
                }
            }
            
            if (!$campaignData || !$goalData) {
                return $result;
            }
            
            // Calculate current value based on goal type
            $currentValue = 0;
            $targetValue = $goalData['amount_cents'] / 100;
            $isCountGoal = in_array($goalData['goal_type'], ['patrons', 'members', 'posts']);
            
            if ($goalData['goal_type'] === 'patrons') {
                $currentValue = $campaignData['patron_count'] ?? 0;
            } elseif ($goalData['goal_type'] === 'members') {
                $currentValue = $campaignData['paid_member_count'] ?? 0;
            } elseif ($goalData['goal_type'] === 'posts') {
                $currentValue = $campaignData['creation_count'] ?? 0;
            } elseif ($goalData['goal_type'] === 'income') {
                $currentValue = ($campaignData['pledge_sum'] ?? 0) / 100; // Convert cents to dollars
            }
            
            // Calculate progress percentage
            $progressPercent = $targetValue > 0 ? min(100, floor(($currentValue / $targetValue) * 100)) : 0;
            
            // Generate goal text
            if ($isCountGoal) {
                if ($currentValue >= $targetValue) {
                    $goalText = number_format($targetValue) . ' - reached!';
                } else {
                    $goalText = number_format($currentValue) . ' of ' . number_format($targetValue);
                }
            } else {
                // Income goal
                if ($currentValue >= $targetValue) {
                    $goalText = '$' . number_format($targetValue) . ' - reached!';
                } else {
                    $goalText = '$' . number_format($currentValue) . ' of $' . number_format($targetValue);
                }
            }
            
            $result['goalText'] = $goalText;
            $result['progressPercent'] = $progressPercent;
            
        } catch (Exception $e) {
            // If anything fails, just return empty values
        }
        
        return $result;
    }

    private function GetPatreonData()
    {
        // Always use custom goal mode - legacy API mode is no longer supported
        if (!empty($this->options['custom_goal_id']) && !empty($this->options['patreon_username'])) {
            return $this->GetCustomGoalData();
        } else {
            // If missing settings, return test data
            return $this->GetCustomGoalDataFallback();
        }
    }
    
    private function GetCustomGoalDataFallback()
    {
        // Create test data when username is missing but custom goal is selected
        $goalId = $this->options['custom_goal_id'];
        if (empty($goalId)) {
            $goalId = 'patrons-10'; // Default goal
        }
        
        $goals = $this->patreonClient->getCustomGoals();
        if (!isset($goals[$goalId])) {
            return "{}";
        }
        
        $goal = $goals[$goalId];
        
        // Create test data for unconfigured blocks
        $patreonV1Format = array(
            'data' => array(
                'type' => 'user',
                'id' => 'test-user',
                'attributes' => array(
                    'full_name' => 'Test Campaign'
                )
            ),
            'included' => array(
                array(
                    'type' => 'campaign',
                    'id' => 'test-campaign',
                    'attributes' => array(
                        'patron_count' => 3,  // Some progress for unconfigured blocks
                        'paid_member_count' => 2,
                        'creation_count' => 8,
                        'pledge_sum' => 0,
                        'pay_per_name' => $goal['type'] === 'income' ? 'month' : ''
                    )
                ),
                array(
                    'type' => 'goal',
                    'id' => 'test-goal',
                    'attributes' => array(
                        'amount_cents' => $goal['target'] * 100,
                        'description' => $goal['title'],
                        'title' => $goal['title'],
                        'goal_type' => $goal['type']
                    )
                )
            )
        );
        
        return json_encode($patreonV1Format);
    }

    private function GetCustomGoalData()
    {
        $username = $this->options['patreon_username'];
        $goalId = $this->options['custom_goal_id'];
        
        // Check cache first
        $cacheKey = 'custom_goal_' . $username . '_' . $goalId;
        $useCache = !empty($this->options['cache']) && (time() - $this->options['cache_age'] <= 60);
        
        if ($useCache && !empty($this->options['cache'])) {
            return $this->options['cache'];
        }
        
        // Get campaign data with custom goals
        $campaignData = $this->patreonClient->getCampaignDataWithGoals($username, true);
        
        
        if ($campaignData === false || !isset($campaignData['custom_goals'][$goalId])) {
            // If no campaign data but we have goals, create mock data with real goal
            $goals = $this->patreonClient->getCustomGoals();
            if (isset($goals[$goalId])) {
                $goal = $goals[$goalId];
                
                // Create mock campaign data for testing with ~33% progress
                $mockCampaignData = array(
                    'patron_count' => 8,      // 8/25 = 32% for patrons-25 goal
                    'paid_member_count' => 3, // 3/10 = 30% for members goals  
                    'creation_count' => 17,   // 17/50 = 34% for posts goals
                    'pledge_sum' => 8333,     // $83.33 in cents, $83/$250 = 33% for income goals
                    'campaign_name' => 'Demo Campaign',
                    'custom_goals' => array($goalId => $goal)
                );
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('GoalieTron Debug - Using mock data for goal: ' . $goalId);
                }
                
                $campaignData = $mockCampaignData;
            } else {
                // Fallback to cached data or empty
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('GoalieTron Debug - No goal found and no campaign data, returning empty');
                }
                return !empty($this->options['cache']) ? $this->options['cache'] : "{}";
            }
        }
        
        $goal = $campaignData['custom_goals'][$goalId];
        
        // Transform custom goal data to match the expected format for the frontend
        // Get current value from campaign data based on goal type
        switch ($goal['type']) {
            case 'patrons':
                $currentValue = isset($campaignData['patron_count']) ? $campaignData['patron_count'] : 0;
                break;
            case 'members':
                $currentValue = isset($campaignData['paid_member_count']) ? $campaignData['paid_member_count'] : 0;
                break;
            case 'posts':
                $currentValue = isset($campaignData['creation_count']) ? $campaignData['creation_count'] : 0;
                break;
            case 'income':
                $currentValue = isset($campaignData['pledge_sum']) ? $campaignData['pledge_sum'] / 100 : 0; // Convert cents to dollars
                break;
            default:
                $currentValue = 0;
        }
        $targetValue = $goal['target'];
        
        // For income goals, values are already in dollars, convert to cents
        // For other goals, values are counts, multiply by 100 for frontend compatibility
        if ($goal['type'] === 'income') {
            $pledgeSum = $currentValue * 100; // Convert dollars to cents
            $goalAmount = $targetValue * 100; // Convert dollars to cents
        } else {
            $pledgeSum = $currentValue * 100; // Use count * 100 for compatibility
            $goalAmount = $targetValue * 100; // Use count * 100 for compatibility
        }
        
        // Format data to match Patreon API v1 structure that the JavaScript expects
        $patreonV1Format = array(
            'data' => array(
                'type' => 'user',
                'id' => 'custom-user',
                'attributes' => array(
                    'full_name' => isset($campaignData['campaign_name']) ? $campaignData['campaign_name'] : 'Campaign'
                )
            ),
            'included' => array(
                // Campaign data
                array(
                    'type' => 'campaign',
                    'id' => 'custom-campaign',
                    'attributes' => array(
                        'patron_count' => isset($campaignData['patron_count']) ? $campaignData['patron_count'] : 0,
                        'paid_member_count' => isset($campaignData['paid_member_count']) ? $campaignData['paid_member_count'] : 0,
                        'creation_count' => isset($campaignData['creation_count']) ? $campaignData['creation_count'] : 0,
                        'pledge_sum' => $pledgeSum,
                        'pay_per_name' => $goal['type'] === 'income' ? 'month' : ''
                    )
                ),
                // Goal data
                array(
                    'type' => 'goal',
                    'id' => 'custom-goal',
                    'attributes' => array(
                        'amount_cents' => $goalAmount,
                        'description' => $goal['title'],
                        'title' => $goal['title'],
                        'goal_type' => $goal['type']
                    )
                )
            )
        );
        
        $jsonData = json_encode($patreonV1Format);
        
        
        // Update cache
        $this->options['cache'] = $jsonData;
        $this->SaveOptions("cache");
        
        $this->options['cache_age'] = time();
        $this->SaveOptions("cache_age");
        
        return $jsonData;
    }




    public function generateCustomGoalsOptions()
    {
        try {
            $goals = $this->getAvailableCustomGoals();
            $options = '';
            
            if (is_array($goals)) {
                foreach ($goals as $goalId => $goal) {
                    if (isset($goal['type']) && isset($goal['target']) && isset($goal['title'])) {
                        $goalType = ucfirst($goal['type']);
                        $goalTarget = number_format($goal['target']);
                        $options .= '<option value="' . esc_attr($goalId) . '">' . 
                                   esc_html($goal['title']) . ' (' . $goalType . ': ' . $goalTarget . ')</option>';
                    }
                }
            }
            
            if (empty($options)) {
                $options = '<option value="" disabled>No custom goals found</option>';
            }
            
            return $options;
        } catch (Exception $e) {
            error_log('GoalieTron Error generating custom goals options: ' . $e->getMessage());
            return '<option value="" disabled>Error loading custom goals</option>';
        }
    }
}

// Register the GoalieTron block
function register_goalietron_block() {
    // Check if the register_block_type function exists
    if (!function_exists('register_block_type')) {
        return;
    }

    // Include the render callback file
    require_once plugin_dir_path(__FILE__) . 'block-render.php';

    // Get custom goals for the editor
    $goalietron = GoalieTron::Instance();
    $custom_goals = array();
    
    try {
        $goals = $goalietron->getAvailableCustomGoals();
        if (is_array($goals)) {
            foreach ($goals as $goalId => $goal) {
                if (isset($goal['type']) && isset($goal['target']) && isset($goal['title'])) {
                    $custom_goals[] = array(
                        'id' => $goalId,
                        'title' => $goal['title'],
                        'type' => ucfirst($goal['type']),
                        'target' => number_format($goal['target'])
                    );
                }
            }
        }
    } catch (Exception $e) {
        error_log('GoalieTron Block: Error loading custom goals - ' . $e->getMessage());
    }

    // Register the block editor script with proper dependencies
    wp_register_script(
        'goalietron-block-editor',
        plugin_dir_url(__FILE__) . 'block-editor.js',
        array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-server-side-render'),
        filemtime(plugin_dir_path(__FILE__) . 'block-editor.js'),
        true
    );
    
    // Add custom goals data
    wp_add_inline_script(
        'goalietron-block-editor',
        'window.goalietronCustomGoals = ' . json_encode($custom_goals) . ';',
        'before'
    );
    
    // Register block type from block.json
    register_block_type(__DIR__ . '/block.json', array(
        'editor_script' => 'goalietron-block-editor',
        'render_callback' => 'goalietron_block_render_callback'
    ));
}

// Hook block registration
add_action('init', 'register_goalietron_block');

// Enqueue editor-specific assets
function goalietron_enqueue_block_editor_assets() {
    // Load default CSS in editor for proper visual preview
    wp_enqueue_style(
        'goalietron-editor-style',
        plugin_dir_url(__FILE__) . '_inc/goalietron_default.css',
        array(),
        filemtime(plugin_dir_path(__FILE__) . '_inc/goalietron_default.css')
    );
}
add_action('enqueue_block_editor_assets', 'goalietron_enqueue_block_editor_assets');

// Add block category if needed
function goalietron_block_categories($categories) {
    // Check if our category already exists
    foreach ($categories as $category) {
        if ($category['slug'] === 'widgets') {
            return $categories;
        }
    }
    
    // Add widgets category if it doesn't exist
    return array_merge(
        $categories,
        array(
            array(
                'slug' => 'widgets',
                'title' => __('Widgets', 'goalietron'),
                'icon' => 'admin-generic',
            )
        )
    );
}
add_filter('block_categories_all', 'goalietron_block_categories', 10, 2);
