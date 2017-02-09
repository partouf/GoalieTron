<?php
/**
 * @package goalietron
 */
/*
Plugin Name: GoalieTron
Plugin URI: http://wordpress.org/plugins/goalietron/
Description: A Patreon plugin that displays your current goal and other information.
Author: Partouf
Version: 1.1
Author URI: https://github.com/partouf
*/

class GoalieTron
{
    private static $instance;
    private $options;
    private $cachetimeout = 60;
    private $fetchtimeout = 3;

    const OptionPrefix = "goalietron_";
    const MainJSFile = "goalietron.js";
    const PatreonWebsiteURL = "https://www.patreon.com/";
    const PatreonUserAPIURL = "https://api.patreon.com/user/";

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
            "showbutton" => "false"
        );

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

        $configView = str_replace("{goalietron_json}", $this->GetPatreonRawJSONData(), $configView);

        echo "<div>";
        echo $configView;
        echo "</div>";

        echo $args['after_widget'];
    }

    private function StringIsJson($data)
    {
        $json = @json_decode($data);
        return !is_null($json);
    }

    private function GetPatreonRawJSONData()
    {
        $data_raw = false;

        if ($this->options['cache_only'] == "yes") {
        } else if (empty($this->options['cache']) || (time() - $this->options['cache_age'] > $this->cachetimeout)) {
            if (!empty($this->options['patreon_userid'])) {
                $url = self::PatreonUserAPIURL . $this->options['patreon_userid'];

                $context = stream_context_create(array('https' => array('header' => array('Connection: close'), 'timeout' => $this->fetchtimeout, 'ignore_errors' => true)));

                $data_raw = file_get_contents($url, false, $context);
                if (!empty($data_raw) && $this->StringIsJson($data_raw)) {
                    $this->options['cache'] = $data_raw;
                    $this->SaveOptions("cache");

                    $this->options['cache_age'] = time();
                    $this->SaveOptions("cache_age");
                }
            }
        }

        if (!$data_raw) {
            if (!empty($this->options['cache'])) {
                $data_raw = $this->options['cache'];
            } else {
                $data_raw = "{}";
            }
        }

        return $data_raw;
    }

    private function GetUserIDFromUserName($username)
    {
        $url = self::PatreonWebsiteURL . $username;

        $pagedata = file_get_contents($url);

        $createridpos = strpos($pagedata, "\"creator_id\": ");
        if ($createridpos !== false) {
            $pagedata = substr($pagedata, $createridpos + 14);
            $endidpos = strpos($pagedata, "\n");
            if ($endidpos === false) {
                $endidpos = strpos($pagedata, "}");
            }

            return trim(substr($pagedata, 0, $endidpos));
        }

        return -1;
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
        }

        $configView = file_get_contents(__DIR__ . "/views/config.html");
        foreach ($this->options as $option_name => $option_value) {
            $configView = str_replace("{" . $option_name . "}", $option_value, $configView);
        }
        echo $configView;
    }
}


function goalietron_widget_display($args)
{
    GoalieTron::Instance()->DisplayWidget($args);
}

function goalietron_control_display()
{
    GoalieTron::Instance()->DisplaySettings();
}

wp_register_sidebar_widget('goalietron_widget_1', 'GoalieTron Widget', 'goalietron_widget_display', array('description' => 'A Patreon plugin that displays your current goal and other information.'));
wp_register_widget_control('goalietron_widget_1', 'GoalieTron Control', 'goalietron_control_display', array());
