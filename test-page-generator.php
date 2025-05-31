<?php
/**
 * Test Page Generator for GoalieTron Widget
 * 
 * Generates HTML pages showing various widget configurations without WordPress
 */

require_once __DIR__ . '/PatreonClient.php';

class TestPageGenerator
{
    private $client;
    private $outputDir;
    
    public function __construct()
    {
        $this->client = new PatreonClient();
        $this->client->loadCustomGoalsFromFile(__DIR__ . '/patreon-goals.json');
        $this->outputDir = __DIR__ . '/test-pages/';
        
        // Create output directory
        if (!file_exists($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }
    
    public function generateAllPages()
    {
        echo "Generating test pages...\n\n";
        
        // Copy CSS and JS files
        $this->copyAssets();
        
        // Generate pages with different configurations
        $this->generateLegacyGoalPage();
        $this->generateCustomGoalPages();
        $this->generateDesignShowcase();
        $this->generateConfigPage();
        $this->generateIndexPage();
        
        echo "✓ All test pages generated in: " . $this->outputDir . "\n";
        echo "✓ Open index.html to see all variations\n";
    }
    
    private function copyAssets()
    {
        $incDir = $this->outputDir . '_inc/';
        if (!file_exists($incDir)) {
            mkdir($incDir, 0755, true);
        }
        
        // Copy CSS files
        $cssFiles = glob(__DIR__ . '/_inc/*.css');
        foreach ($cssFiles as $cssFile) {
            copy($cssFile, $incDir . basename($cssFile));
        }
        
        // Copy JS file
        copy(__DIR__ . '/_inc/goalietron.js', $incDir . 'goalietron.js');
        
        echo "✓ Assets copied\n";
    }
    
    private function generateLegacyGoalPage()
    {
        $pageContent = $this->generateBasePage(
            'Legacy Goal (Simulated)',
            'default',
            'green',
            $this->generateLegacyMockData(),
            'This shows how the widget would look with the original Patreon API data (simulated since API requires authentication).'
        );
        
        file_put_contents($this->outputDir . 'legacy-goal.html', $pageContent);
        echo "✓ Generated: legacy-goal.html\n";
    }
    
    private function generateCustomGoalPages()
    {
        $goals = $this->client->getCustomGoals();
        $username = 'scishow'; // Use SciShow as test data
        
        foreach ($goals as $goalId => $goal) {
            $campaignData = $this->client->getCampaignDataWithGoals($username);
            if ($campaignData && isset($campaignData['custom_goals'][$goalId])) {
                $goalData = $campaignData['custom_goals'][$goalId];
                $jsonData = $this->formatCustomGoalForFrontend($goalData, $campaignData);
                
                $filename = 'custom-goal-' . $goalId . '.html';
                $pageContent = $this->generateBasePage(
                    $goal['title'],
                    'default',
                    'blue',
                    $jsonData,
                    "Custom goal tracking {$goal['type']} with target of " . number_format($goal['target']) . ". Current progress: {$goalData['progress_percentage']}%"
                );
                
                file_put_contents($this->outputDir . $filename, $pageContent);
                echo "✓ Generated: $filename\n";
            }
        }
    }
    
    private function generateDesignShowcase()
    {
        $designs = ['default', 'fancy', 'minimal', 'streamlined', 'reversed', 'swapped'];
        $colors = ['green', 'blue', 'red', 'orange'];
        
        // Use the first custom goal for design showcase
        $goals = $this->client->getCustomGoals();
        $firstGoal = reset($goals);
        $goalId = key($goals);
        
        if ($firstGoal) {
            $campaignData = $this->client->getCampaignDataWithGoals('scishow');
            $goalData = $campaignData['custom_goals'][$goalId];
            $jsonData = $this->formatCustomGoalForFrontend($goalData, $campaignData);
            
            foreach ($designs as $design) {
                $color = $colors[array_rand($colors)];
                $filename = "design-$design.html";
                $pageContent = $this->generateBasePage(
                    "Design: " . ucfirst($design),
                    $design,
                    $color,
                    $jsonData,
                    "Showcasing the '$design' design theme with '$color' color scheme."
                );
                
                file_put_contents($this->outputDir . $filename, $pageContent);
                echo "✓ Generated: $filename\n";
            }
        }
    }
    
    private function generateConfigPage()
    {
        $configTemplate = file_get_contents(__DIR__ . '/views/config.html');
        
        // Mock configuration values
        $configValues = [
            'title' => 'My Patreon Goal Widget',
            'toptext' => 'Help us reach our goal!',
            'bottomtext' => 'Every contribution matters',
            'design' => 'default',
            'metercolor' => 'blue',
            'showgoaltext' => 'true',
            'showbutton' => 'true',
            'goal_mode' => 'custom',
            'patreon_userid' => '123456',
            'patreon_username' => 'scishow',
            'custom_goal_id' => 'patrons-20000'
        ];
        
        // Generate custom goals options
        $goals = $this->client->getCustomGoals();
        $customGoalsOptions = '';
        foreach ($goals as $goalId => $goal) {
            $goalType = ucfirst($goal['type']);
            $goalTarget = number_format($goal['target']);
            $selected = ($goalId === 'patrons-20000') ? ' selected' : '';
            $customGoalsOptions .= '<option value="' . htmlspecialchars($goalId) . '"' . $selected . '>' . 
                                  htmlspecialchars($goal['title']) . ' (' . $goalType . ': ' . $goalTarget . ')</option>';
        }
        
        // Replace template variables
        $configTemplate = str_replace('{custom_goals_options}', $customGoalsOptions, $configTemplate);
        foreach ($configValues as $key => $value) {
            $configTemplate = str_replace('{' . $key . '}', htmlspecialchars($value), $configTemplate);
        }
        
        $pageContent = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Widget Configuration - GoalieTron Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            margin: 40px; 
            background: #f1f1f1; 
            color: #23282d;
        }
        .test-container { 
            max-width: 600px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.13); 
        }
        .test-header { 
            text-align: center; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #0073aa; 
            padding-bottom: 20px; 
        }
        .test-header h1 { 
            color: #23282d; 
            margin: 0 0 10px 0; 
            font-size: 24px;
        }
        .test-description { 
            color: #666; 
            font-style: italic; 
        }
        .config-container { 
            background: #f9f9f9; 
            padding: 20px; 
            border-radius: 5px; 
            margin: 20px 0; 
            border: 1px solid #ddd;
        }
        .config-container > div {
            margin-bottom: 15px;
        }
        .config-container label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #23282d;
        }
        .widefat {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            background: white;
            box-sizing: border-box;
        }
        .widefat:focus {
            border-color: #0073aa;
            outline: none;
            box-shadow: 0 0 0 1px #0073aa;
        }
        #legacy_options, #custom_options {
            margin-left: 20px;
            margin-top: 10px;
            padding: 15px;
            background: #fff;
            border-radius: 4px;
            border: 1px solid #e5e5e5;
        }
        .description {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
        .back-link { 
            display: inline-block; 
            margin-top: 20px; 
            color: #0073aa; 
            text-decoration: none; 
            font-weight: bold; 
        }
        .back-link:hover { 
            text-decoration: underline; 
        }
        .save-button, .load-button, .reset-button, .preview-button {
            border: none;
            padding: 10px 16px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .save-button {
            background: #0073aa;
            color: white;
        }
        .save-button:hover {
            background: #005a87;
        }
        .load-button {
            background: #00a32a;
            color: white;
        }
        .load-button:hover {
            background: #007a20;
        }
        .reset-button {
            background: #dc3545;
            color: white;
        }
        .reset-button:hover {
            background: #c82333;
        }
        .preview-button {
            background: #6c757d;
            color: white;
        }
        .preview-button:hover {
            background: #545b62;
        }
        .status-message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
            min-height: 20px;
        }
        .status-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .status-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .status-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .note {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 12px;
            border-radius: 4px;
            margin: 15px 0;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="test-header">
            <h1>🔧 Widget Configuration</h1>
            <div class="test-description">WordPress admin interface for configuring the GoalieTron widget</div>
        </div>
        
        <div class="note">
            <strong>Interactive Demo:</strong> This configuration interface includes save/load functionality using browser localStorage. 
            Your settings will persist between browser sessions for testing purposes.
        </div>
        
        <form class="config-container" id="widget-config-form">
            ' . $configTemplate . '
            
            <div style="display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap;">
                <button type="button" class="save-button" onclick="saveConfig()">
                    💾 Save Configuration
                </button>
                <button type="button" class="load-button" onclick="loadConfig()">
                    📁 Load Configuration
                </button>
                <button type="button" class="reset-button" onclick="resetConfig()">
                    🔄 Reset to Defaults
                </button>
                <button type="button" class="preview-button" onclick="previewWidget()">
                    👁️ Preview Widget
                </button>
            </div>
            
            <div id="status-message" class="status-message"></div>
        </form>
        
        <div class="note">
            <strong>💡 Tips:</strong>
            <ul style="margin: 10px 0 0 20px;">
                <li>Switch between "Legacy API" and "Custom Goals" modes</li>
                <li>Custom goals are created using the CLI tool</li>
                <li>All design themes work with both modes</li>
                <li>The "Show button" option toggles the Patreon patron button</li>
            </ul>
        </div>
        
        <a href="index.html" class="back-link">← Back to Test Index</a>
    </div>
    
    <script>
        // Configuration save/load functionality
        const CONFIG_STORAGE_KEY = "goalietron_test_config";
        const DEFAULT_CONFIG = {
            title: "My Patreon Goal Widget",
            toptext: "Help us reach our goal!",
            bottomtext: "Every contribution matters",
            design: "default",
            metercolor: "blue",
            showgoaltext: "true",
            showbutton: "true",
            goal_mode: "custom",
            patreon_userid: "123456",
            patreon_username: "scishow",
            custom_goal_id: "patrons-20000"
        };
        
        function showStatus(message, type = "info") {
            const statusDiv = document.getElementById("status-message");
            statusDiv.textContent = message;
            statusDiv.className = "status-message status-" + type;
            setTimeout(() => {
                statusDiv.textContent = "";
                statusDiv.className = "status-message";
            }, 3000);
        }
        
        function getFormData() {
            const form = document.getElementById("widget-config-form");
            const formData = new FormData(form);
            const config = {};
            
            // Get all form elements
            const inputs = form.querySelectorAll("input, select");
            inputs.forEach(input => {
                if (input.name && input.name.startsWith("goalietron_")) {
                    const key = input.name.replace("goalietron_", "");
                    config[key] = input.value;
                }
            });
            
            return config;
        }
        
        function setFormData(config) {
            const form = document.getElementById("widget-config-form");
            
            Object.keys(config).forEach(key => {
                const element = form.querySelector(`[name="goalietron_${key}"]`);
                if (element) {
                    element.value = config[key];
                    
                    // Trigger change event for dropdowns to update UI
                    if (element.tagName === "SELECT") {
                        element.dispatchEvent(new Event("change"));
                    }
                }
            });
            
            // Update dropdown selections manually for jQuery-based code
            setTimeout(() => {
                jQuery("#goalietron_design option[value=\'" + config.design + "\']").attr("selected", "selected");
                jQuery("#goalietron_metercolor option[value=\'" + config.metercolor + "\']").attr("selected", "selected");
                jQuery("#goalietron_showgoaltext option[value=\'" + config.showgoaltext + "\']").attr("selected", "selected");
                jQuery("#goalietron_showbutton option[value=\'" + config.showbutton + "\']").attr("selected", "selected");
                jQuery("#goalietron_goal_mode option[value=\'" + config.goal_mode + "\']").attr("selected", "selected");
                jQuery("#goalietron_custom_goal_id option[value=\'" + config.custom_goal_id + "\']").attr("selected", "selected");
                
                // Trigger the goal mode toggle
                toggleGoalOptions();
            }, 100);
        }
        
        function saveConfig() {
            try {
                const config = getFormData();
                localStorage.setItem(CONFIG_STORAGE_KEY, JSON.stringify(config));
                showStatus("Configuration saved successfully!", "success");
            } catch (error) {
                showStatus("Error saving configuration: " + error.message, "error");
            }
        }
        
        function loadConfig() {
            try {
                const savedConfig = localStorage.getItem(CONFIG_STORAGE_KEY);
                if (savedConfig) {
                    const config = JSON.parse(savedConfig);
                    setFormData(config);
                    showStatus("Configuration loaded successfully!", "success");
                } else {
                    showStatus("No saved configuration found", "info");
                }
            } catch (error) {
                showStatus("Error loading configuration: " + error.message, "error");
            }
        }
        
        function resetConfig() {
            if (confirm("Are you sure you want to reset to default configuration?")) {
                setFormData(DEFAULT_CONFIG);
                showStatus("Configuration reset to defaults", "info");
            }
        }
        
        function previewWidget() {
            try {
                const config = getFormData();
                
                // Build preview URL
                let previewUrl = "design-" + config.design + ".html";
                
                // If custom goal mode, try to use specific goal page
                if (config.goal_mode === "custom" && config.custom_goal_id) {
                    const customUrl = "custom-goal-" + config.custom_goal_id + ".html";
                    // Check if the file exists by trying to open it
                    previewUrl = customUrl;
                }
                
                // Open preview in new window
                const previewWindow = window.open(previewUrl, "_blank", "width=800,height=600");
                if (previewWindow) {
                    showStatus("Preview opened in new window", "success");
                } else {
                    showStatus("Please allow popups to preview the widget", "error");
                }
            } catch (error) {
                showStatus("Error opening preview: " + error.message, "error");
            }
        }
        
        // Auto-load configuration on page load
        document.addEventListener("DOMContentLoaded", function() {
            // Small delay to ensure jQuery is loaded
            setTimeout(() => {
                const savedConfig = localStorage.getItem(CONFIG_STORAGE_KEY);
                if (savedConfig) {
                    try {
                        const config = JSON.parse(savedConfig);
                        setFormData(config);
                        showStatus("Previous configuration loaded", "info");
                    } catch (error) {
                        console.warn("Error loading saved config:", error);
                    }
                }
            }, 500);
        });
    </script>
</body>
</html>';
        
        file_put_contents($this->outputDir . 'config.html', $pageContent);
        echo "✓ Generated: config.html\n";
    }
    
    private function generateIndexPage()
    {
        $goals = $this->client->getCustomGoals();
        $designs = ['default', 'fancy', 'minimal', 'streamlined', 'reversed', 'swapped'];
        
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoalieTron Widget Test Pages</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #0073aa; padding-bottom: 10px; }
        h2 { color: #0073aa; margin-top: 30px; }
        .page-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 20px 0; }
        .page-item { background: #f9f9f9; padding: 15px; border-radius: 5px; border-left: 4px solid #0073aa; }
        .page-item a { text-decoration: none; color: #0073aa; font-weight: bold; }
        .page-item a:hover { text-decoration: underline; }
        .description { color: #666; font-size: 14px; margin-top: 5px; }
        .note { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 GoalieTron Widget Test Pages</h1>
        
        <div class="note success">
            <strong>✓ Test Environment Ready!</strong><br>
            These pages demonstrate the GoalieTron widget functionality without WordPress.
            All data is live from Patreon\'s public API.
        </div>
        
        <div style="text-align: center; margin: 20px 0;">
            <a href="config.html" style="display: inline-block; background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;">
                ⚙️ Configure Widget Settings
            </a>
            <p style="margin: 10px 0 0 0; color: #666; font-size: 14px;">
                Customize colors, text, and goals - settings will apply to all demo pages!
            </p>
        </div>
        
        <h2>📊 Custom Goal Examples</h2>
        <div class="page-list">';
        
        foreach ($goals as $goalId => $goal) {
            $html .= '<div class="page-item">
                <a href="custom-goal-' . $goalId . '.html">' . htmlspecialchars($goal['title']) . '</a>
                <div class="description">Type: ' . ucfirst($goal['type']) . ' | Target: ' . number_format($goal['target']) . '</div>
            </div>';
        }
        
        $html .= '</div>
        
        <h2>🎨 Design Themes</h2>
        <div class="page-list">';
        
        foreach ($designs as $design) {
            $html .= '<div class="page-item">
                <a href="design-' . $design . '.html">' . ucfirst($design) . ' Theme</a>
                <div class="description">Shows the ' . $design . ' design variation</div>
            </div>';
        }
        
        $html .= '</div>
        
        <h2>🔄 Legacy Comparison</h2>
        <div class="page-list">
            <div class="page-item">
                <a href="legacy-goal.html">Legacy Goal (Simulated)</a>
                <div class="description">Shows how the original API data would look</div>
            </div>
        </div>
        
        <h2>🔧 Configuration</h2>
        <div class="page-list">
            <div class="page-item">
                <a href="config.html">Widget Configuration</a>
                <div class="description">Shows the WordPress admin configuration interface</div>
            </div>
        </div>
        
        <div class="note">
            <strong>📝 Note:</strong> Custom goals use live data from Patreon\'s public pages, 
            while legacy goals show simulated data since the original API requires authentication.
        </div>
        
        <h2>🛠️ How to Use</h2>
        <p>To create custom goals for your own Patreon campaign:</p>
        <ol>
            <li>Use the CLI: <code>php patreon-cli.php goal-add my-goal patrons 1000 "My Goal"</code></li>
            <li>Configure the WordPress widget to use "Custom Goals" mode</li>
            <li>Enter your Patreon username and select the goal</li>
        </ol>
        
        <p><strong>Goal Types Available:</strong></p>
        <ul>
            <li><strong>patrons</strong> - Track total patron count</li>
            <li><strong>members</strong> - Track paid member count</li>
            <li><strong>posts</strong> - Track number of posts created</li>
            <li><strong>income</strong> - Track monthly income (if visible)</li>
        </ul>
    </div>
</body>
</html>';
        
        file_put_contents($this->outputDir . 'index.html', $html);
        echo "✓ Generated: index.html\n";
    }
    
    private function generateBasePage($title, $design, $color, $jsonData, $description)
    {
        $designTemplate = file_get_contents(__DIR__ . "/views/design_$design.html");
        
        // Create a test-friendly version of the button (no actual Patreon widget)
        $testButtonHtml = '<div style="text-align: center; margin: 10px 0;"><span style="display: inline-block; padding: 8px 16px; background: #ff424d; color: white; border-radius: 4px; font-weight: bold; opacity: 0.7;">Become a Patron! (Disabled in test)</span></div>';
        
        // Replace template variables
        $designTemplate = str_replace('{goalietron_button}', $testButtonHtml, $designTemplate);
        $designTemplate = str_replace('{goalietron_json}', $jsonData, $designTemplate);
        $designTemplate = str_replace('{title}', $title, $designTemplate);
        $designTemplate = str_replace('{toptext}', 'Help us reach our goal!', $designTemplate);
        $designTemplate = str_replace('{bottomtext}', 'Every contribution matters', $designTemplate);
        $designTemplate = str_replace('{patreon_userid}', '', $designTemplate); // Remove any remaining references
        $designTemplate = str_replace('{metercolor}', $color, $designTemplate);
        $designTemplate = str_replace('{showgoaltext}', 'true', $designTemplate);
        $designTemplate = str_replace('{hasstripes}', strpos($color, 'nostripes') === false ? 'stripes' : '', $designTemplate);
        
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . ' - GoalieTron Test</title>
    <link rel="stylesheet" href="_inc/goalietron_' . $design . '.css" id="theme-stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="_inc/goalietron.js"></script>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 40px; 
            background: #f5f5f5; 
        }
        .test-container { 
            max-width: 600px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        .test-header { 
            text-align: center; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #0073aa; 
            padding-bottom: 20px; 
        }
        .test-header h1 { 
            color: #333; 
            margin: 0 0 10px 0; 
        }
        .test-description { 
            color: #666; 
            font-style: italic; 
        }
        .widget-container { 
            background: #f9f9f9; 
            padding: 20px; 
            border-radius: 5px; 
            margin: 20px 0; 
        }
        .back-link { 
            display: inline-block; 
            margin-top: 20px; 
            color: #0073aa; 
            text-decoration: none; 
            font-weight: bold; 
        }
        .back-link:hover { 
            text-decoration: underline; 
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="test-header">
            <h1>' . htmlspecialchars($title) . '</h1>
            <div class="test-description">' . htmlspecialchars($description) . '</div>
        </div>
        
        <div class="widget-container">
            ' . $designTemplate . '
        </div>
        
        <a href="index.html" class="back-link">← Back to Test Index</a>
        <a href="config.html" class="config-link" style="margin-left: 20px;">⚙️ Edit Configuration</a>
    </div>
    
    <script>
        // Configuration synchronization with localStorage
        const CONFIG_STORAGE_KEY = "goalietron_test_config";
        
        function applyStoredConfiguration() {
            try {
                const savedConfig = localStorage.getItem(CONFIG_STORAGE_KEY);
                if (!savedConfig) return;
                
                const config = JSON.parse(savedConfig);
                console.log("Applying stored config:", config);
                
                // Update page title if different from stored title
                if (config.title && config.title !== "My Patreon Goal Widget") {
                    document.querySelector(".test-header h1").textContent = config.title;
                }
                
                // Update theme if different from current page
                if (config.design && config.design !== "' . $design . '") {
                    const themeLink = document.getElementById("theme-stylesheet");
                    if (themeLink) {
                        themeLink.href = "_inc/goalietron_" + config.design + ".css";
                    }
                }
                
                // Update meter color if configured
                if (config.metercolor) {
                    const meter = document.getElementById("goalietron_meter");
                    if (meter) {
                        // Remove existing color classes
                        meter.className = meter.className.replace(/\b(red|green|blue|orange)\b/g, "");
                        // Add configured color (handle nostripes)
                        const colorClass = config.metercolor.replace(" nostripes", "");
                        meter.classList.add("meter", colorClass);
                    }
                }
                
                // Update text content
                if (config.toptext) {
                    const topText = document.getElementById("goalietron_toptext");
                    if (topText) topText.textContent = config.toptext;
                }
                
                if (config.bottomtext) {
                    const bottomText = document.getElementById("goalietron_bottomtext");
                    if (bottomText) bottomText.textContent = config.bottomtext;
                }
                
                // Update goal text visibility
                if (config.showgoaltext === "false") {
                    const goalText = document.getElementById("goalietron_goaltext");
                    if (goalText) goalText.style.display = "none";
                }
                
                // Update button visibility
                if (config.showbutton === "false") {
                    const button = document.querySelector(".goalietron_button");
                    if (button) button.style.display = "none";
                }
                
                // Show configuration status
                showConfigStatus("Configuration from localStorage applied", "info");
                
            } catch (error) {
                console.warn("Error applying stored configuration:", error);
            }
        }
        
        function showConfigStatus(message, type) {
            // Create temporary status message
            const statusDiv = document.createElement("div");
            statusDiv.textContent = message;
            statusDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 10px 15px;
                border-radius: 4px;
                font-size: 14px;
                z-index: 1000;
                transition: opacity 0.3s;
                ${type === "info" ? "background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460;" : ""}
                ${type === "success" ? "background: #d4edda; border: 1px solid #c3e6cb; color: #155724;" : ""}
            `;
            
            document.body.appendChild(statusDiv);
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                statusDiv.style.opacity = "0";
                setTimeout(() => {
                    if (statusDiv.parentNode) {
                        statusDiv.parentNode.removeChild(statusDiv);
                    }
                }, 300);
            }, 3000);
        }
        
        // Apply configuration when page loads
        document.addEventListener("DOMContentLoaded", function() {
            // Small delay to ensure all elements are loaded
            setTimeout(applyStoredConfiguration, 100);
        });
    </script>
</body>
</html>';
    }
    
    private function formatCustomGoalForFrontend($goalData, $campaignData)
    {
        $currentValue = $goalData['current'];
        $targetValue = $goalData['target'];
        
        // For income goals, values are in dollars, convert to cents
        // For other goals, values are counts, multiply by 100 for compatibility
        if ($goalData['type'] === 'income') {
            $pledgeSum = $currentValue * 100;
            $goalAmount = $targetValue * 100;
        } else {
            $pledgeSum = $currentValue * 100;
            $goalAmount = $targetValue * 100;
        }
        
        // Format data to match Patreon API v1 structure that the JavaScript expects
        $patreonV1Format = array(
            'data' => array(
                'type' => 'user',
                'id' => 'test-user',
                'attributes' => array(
                    'full_name' => isset($campaignData['campaign_name']) ? $campaignData['campaign_name'] : 'Campaign'
                )
            ),
            'included' => array(
                // Campaign data
                array(
                    'type' => 'campaign',
                    'id' => 'test-campaign',
                    'attributes' => array(
                        'patron_count' => isset($campaignData['patron_count']) ? $campaignData['patron_count'] : 0,
                        'paid_member_count' => isset($campaignData['paid_member_count']) ? $campaignData['paid_member_count'] : 0,
                        'creation_count' => isset($campaignData['creation_count']) ? $campaignData['creation_count'] : 0,
                        'pledge_sum' => $pledgeSum,
                        'pay_per_name' => $goalData['type'] === 'income' ? 'month' : ''
                    )
                ),
                // Goal data
                array(
                    'type' => 'goal',
                    'id' => 'test-goal',
                    'attributes' => array(
                        'amount_cents' => $goalAmount,
                        'description' => $goalData['title'],
                        'title' => $goalData['title'],
                        'goal_type' => $goalData['type']
                    )
                )
            )
        );
        
        return json_encode($patreonV1Format);
    }
    
    private function generateLegacyMockData()
    {
        // Simulate legacy API v1 data structure
        $mockData = array(
            'data' => array(
                'type' => 'user',
                'id' => 'mock-user',
                'attributes' => array(
                    'full_name' => 'Sample Creator Campaign'
                )
            ),
            'included' => array(
                // Campaign data
                array(
                    'type' => 'campaign',
                    'id' => 'mock-campaign',
                    'attributes' => array(
                        'patron_count' => 1247,
                        'pledge_sum' => 342500, // $3,425 in cents
                        'pay_per_name' => 'month'
                    )
                ),
                // Goal data
                array(
                    'type' => 'goal',
                    'id' => 'mock-goal',
                    'attributes' => array(
                        'amount_cents' => 500000, // $5,000 in cents
                        'description' => 'We will create a monthly educational video series',
                        'title' => 'Monthly Video Series'
                    )
                )
            )
        );
        
        return json_encode($mockData);
    }
}

// Generate the test pages
$generator = new TestPageGenerator();
$generator->generateAllPages();