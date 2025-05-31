<?php
/**
 * @package goalietron
 */
/*
Plugin Name: GoalieTron
Plugin URI: http://wordpress.org/plugins/goalietron/
Description: A Patreon plugin that displays your current goal and other information.
Author: Partouf
Version: 1.3
Author URI: https://github.com/partouf
*/

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
            "goal_mode" => "legacy",
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
        $goalsFile = plugin_dir_path(__FILE__) . 'patreon-goals.json';
        if (file_exists($goalsFile)) {
            $this->patreonClient->loadCustomGoalsFromFile($goalsFile);
        }
    }

    private function getAvailableCustomGoals()
    {
        return $this->patreonClient->getCustomGoals();
    }

    public function DisplayWidget($args)
    {
        $cssfilename = self::OptionPrefix . $this->options['design'] . ".css";

        wp_register_style($cssfilename, plugin_dir_url(__FILE__) . "_inc/" . $cssfilename);
        wp_enqueue_style($cssfilename);

        wp_register_script(self::MainJSFile, plugin_dir_url(__FILE__) . "_inc/" . self::MainJSFile);
        wp_enqueue_script(self::MainJSFile);

        echo $args['before_widget'];
        echo $args['before_title'] . $this->options['title'];
        echo $args['after_title'];

        $configView = file_get_contents(__DIR__ . "/views/design_" . $this->options['design'] . ".html");

        $buttonhtml = "";
        if ($this->options['showbutton'] != "false") {
            $buttonhtml = file_get_contents(__DIR__ . "/views/button.html");
        }
        $configView = str_replace("{goalietron_button}", $buttonhtml, $configView);

        foreach ($this->options as $option_name => $option_value) {
            $configView = str_replace("{" . $option_name . "}", $option_value, $configView);
        }

        $configView = str_replace("{goalietron_json}", $this->GetPatreonData(), $configView);

        echo "<div>";
        echo $configView;
        echo "</div>";

        echo $args['after_widget'];
    }

    private function GetPatreonData()
    {
        if ($this->options['goal_mode'] === 'custom' && !empty($this->options['custom_goal_id']) && !empty($this->options['patreon_username'])) {
            return $this->GetCustomGoalData();
        } else {
            return $this->GetPatreonRawJSONData();
        }
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
            // Fallback to cached data or empty
            return !empty($this->options['cache']) ? $this->options['cache'] : "{}";
        }
        
        $goal = $campaignData['custom_goals'][$goalId];
        
        // Transform custom goal data to match the expected format for the frontend
        $currentValue = $goal['current'];
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
        
        $legacyFormat = array(
            'pledge_sum' => $pledgeSum,
            'patron_count' => isset($campaignData['patron_count']) ? $campaignData['patron_count'] : 0,
            'goals' => array(
                array(
                    'amount' => $goalAmount,
                    'title' => $goal['title'],
                    'description' => 'Custom Goal: ' . ucfirst($goal['type']),
                    'completed_percentage' => $goal['progress_percentage']
                )
            ),
            'name' => isset($campaignData['campaign_name']) ? $campaignData['campaign_name'] : 'Campaign',
            'custom_goal_mode' => true,
            'custom_goal_type' => $goal['type'],
            'custom_goal_current' => $currentValue,
            'custom_goal_target' => $targetValue
        );
        
        $jsonData = json_encode($legacyFormat);
        
        // Update cache
        $this->options['cache'] = $jsonData;
        $this->SaveOptions("cache");
        
        $this->options['cache_age'] = time();
        $this->SaveOptions("cache_age");
        
        return $jsonData;
    }

    private function GetPatreonRawJSONData()
    {
        if ($this->options['cache_only'] == "yes") {
            // Use cached data only
            if (!empty($this->options['cache'])) {
                return $this->options['cache'];
            } else {
                return "{}";
            }
        }
        
        if (empty($this->options['patreon_userid'])) {
            return "{}";
        }
        
        // Check if we need to fetch new data
        $useCache = !empty($this->options['cache']) && (time() - $this->options['cache_age'] <= 60);
        
        if ($useCache) {
            return $this->options['cache'];
        }
        
        // Fetch new data using PatreonClient
        $data_raw = $this->patreonClient->getUserDataRaw($this->options['patreon_userid'], false);
        
        if ($data_raw !== "{}") {
            // Update cache
            $this->options['cache'] = $data_raw;
            $this->SaveOptions("cache");
            
            $this->options['cache_age'] = time();
            $this->SaveOptions("cache_age");
        } else {
            // Use cached data if available
            if (!empty($this->options['cache'])) {
                $data_raw = $this->options['cache'];
            }
        }
        
        return $data_raw;
    }

    private function GetUserIDFromUserName($username)
    {
        $userId = $this->patreonClient->getUserIdFromUsername($username);
        return $userId !== false ? $userId : -1;
    }

    private function SavePostedData()
    {
        foreach ($this->options as $option_name => $option_oldvalue) {
            if (isset($_POST[self::OptionPrefix . $option_name])) {
                $option_newvalue = $_POST[self::OptionPrefix . $option_name];
            } else {
                continue;
            }

            if ($option_name == "patreon_userid") {
                if (!is_numeric($option_newvalue)) {
                    $userid = $this->GetUserIDFromUserName($option_newvalue);
                    if ($userid > 0) {
                        $option_newvalue = $userid;
                    } else {
                        $option_newvalue = "";
                    }
                }

                if ($option_newvalue != $option_oldvalue) {
                    $this->options['cache_age'] = 0;
                }
            }

            $this->options[$option_name] = $option_newvalue;
        }

        $this->SaveOptions();
    }

    public function DisplaySettings()
    {
        if (!empty($_POST)) {
            $this->SavePostedData();
            $this->loadCustomGoals(); // Reload goals after saving
        }

        $configView = file_get_contents(__DIR__ . "/views/config.html");
        
        // Generate custom goals options
        $customGoalsOptions = $this->generateCustomGoalsOptions();
        $configView = str_replace("{custom_goals_options}", $customGoalsOptions, $configView);
        
        foreach ($this->options as $option_name => $option_value) {
            $configView = str_replace("{" . $option_name . "}", $option_value, $configView);
        }
        echo $configView;
    }

    private function generateCustomGoalsOptions()
    {
        $goals = $this->getAvailableCustomGoals();
        $options = '';
        
        foreach ($goals as $goalId => $goal) {
            $goalType = ucfirst($goal['type']);
            $goalTarget = number_format($goal['target']);
            $options .= '<option value="' . esc_attr($goalId) . '">' . 
                       esc_html($goal['title']) . ' (' . $goalType . ': ' . $goalTarget . ')</option>';
        }
        
        if (empty($options)) {
            $options = '<option value="" disabled>No custom goals found</option>';
        }
        
        return $options;
    }
}


// Modern WordPress Widget Class
class GoalieTron_Widget extends WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'goalietron_widget',
            'GoalieTron Widget',
            array(
                'description' => 'A Patreon plugin that displays your current goal and other information.',
                'classname' => 'goalietron_widget'
            )
        );
    }

    public function widget($args, $instance)
    {
        // Get widget settings from database using the instance ID
        $goalietron = GoalieTron::Instance();
        
        // Load settings for this widget instance
        if (!empty($instance)) {
            foreach ($instance as $key => $value) {
                if (array_key_exists($key, $goalietron->options)) {
                    $goalietron->options[$key] = $value;
                }
            }
        }
        
        $goalietron->DisplayWidget($args);
    }

    public function form($instance)
    {
        // Set default values
        $goalietron = GoalieTron::Instance();
        $default_values = $goalietron->options;
        
        // Merge with saved instance values
        $values = wp_parse_args($instance, $default_values);
        
        $this->display_widget_form($values);
    }
    
    private function display_widget_form($values)
    {
        $goalietron = GoalieTron::Instance();
        
        // Generate custom goals options
        $customGoalsOptions = $goalietron->generateCustomGoalsOptions();
        
        // Load form template
        $configView = file_get_contents(__DIR__ . "/views/widget-form.html");
        
        // Replace placeholders with actual values and proper field names
        $replacements = array();
        foreach ($values as $key => $value) {
            $replacements['{' . $key . '}'] = esc_attr($value);
            $replacements['{' . $key . '_field_name}'] = $this->get_field_name($key);
            $replacements['{' . $key . '_field_id}'] = $this->get_field_id($key);
        }
        $replacements['{custom_goals_options}'] = $customGoalsOptions;
        
        $configView = str_replace(array_keys($replacements), array_values($replacements), $configView);
        
        echo $configView;
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        
        // Get all the possible GoalieTron options
        $goalietron = GoalieTron::Instance();
        $valid_options = array_keys($goalietron->options);
        
        // Sanitize and save each option
        foreach ($valid_options as $option_name) {
            if (isset($new_instance[$option_name])) {
                $instance[$option_name] = sanitize_text_field($new_instance[$option_name]);
            }
        }
        
        return $instance;
    }
}

// Register the widget
function register_goalietron_widget()
{
    register_widget('GoalieTron_Widget');
}
add_action('widgets_init', 'register_goalietron_widget');
