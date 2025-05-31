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
    <link rel="stylesheet" href="_inc/goalietron_' . $design . '.css">
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
    </div>
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
                        'pledge_sum' => $pledgeSum,
                        'pay_per_name' => $goalData['type'] === 'income' ? 'month' : ucfirst($goalData['type'])
                    )
                ),
                // Goal data
                array(
                    'type' => 'goal',
                    'id' => 'test-goal',
                    'attributes' => array(
                        'amount_cents' => $goalAmount,
                        'description' => $goalData['title'],
                        'title' => $goalData['title']
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