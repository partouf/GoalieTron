<?php
/**
 * Detailed HTML output tests for GoalieTron
 * 
 * This script tests specific HTML patterns and validates the output structure
 */

// Enable testing mode to use offline/mocked data
define('GOALIETRON_TESTING', true);

require_once __DIR__ . '/mock-wordpress.php';
require_once dirname(__DIR__) . '/PatreonClient.php';
require_once dirname(__DIR__) . '/goalietron.php';

class HTMLOutputTester {
    
    public function test_custom_goal_html_structure() {
        echo "Testing Custom Goal HTML Structure\n";
        echo "==================================\n";
        
        reset_wp_options();
        
        // Create a custom goal instance
        $options = array(
            'goal_mode' => 'custom',
            'custom_goal_id' => 'patrons-10',
            'patreon_username' => 'test-offline-user',
            'design' => 'streamlined',
            'toptext' => 'Fund me!',
            'bottomtext' => 'Please?',
            'showgoaltext' => 'true',
            'metercolor' => 'green'
        );
        
        $goalietron = GoalieTron::CreateInstance($options);
        
        $args = array(
            'before_widget' => '<div class="widget goalietron-widget">',
            'after_widget' => '</div>',
            'before_title' => '<h2 class="widget-title">',
            'after_title' => '</h2>'
        );
        
        ob_start();
        $goalietron->DisplayWidget($args);
        $html = ob_get_clean();
        
        echo "Generated HTML:\n";
        echo "---------------\n";
        echo $this->format_html($html);
        echo "\n\n";
        
        // Extract and validate key components
        $this->validate_html_structure($html);
        
        return $html;
    }
    
    public function test_block_render_output() {
        echo "\nTesting Block Render Output\n";
        echo "===========================\n";
        
        // Include block render file
        require_once dirname(__DIR__) . '/block-render.php';
        
        // Test block attributes
        $attributes = array(
            'toptext' => 'Support our work',
            'bottomtext' => 'Every patron counts!',
            'design' => 'fancy',
            'goal_mode' => 'custom',
            'patreon_username' => 'testcreator',
            'custom_goal_id' => 'patrons-10',
            'title' => 'Our Goal',
            'metercolor' => 'blue',
            'showgoaltext' => 'true',
            'showbutton' => 'false'
        );
        
        $html = goalietron_block_render_callback($attributes, '');
        
        echo "Block HTML Output:\n";
        echo "-----------------\n";
        echo $this->format_html($html);
        echo "\n\n";
        
        // Validate block-specific attributes
        if (strpos($html, 'wp-block-goalietron') !== false) {
            echo "✓ Block class wrapper found\n";
        } else {
            echo "✗ Block class wrapper missing\n";
        }
        
        if (strpos($html, 'Support our work') !== false) {
            echo "✓ Custom top text rendered\n";
        } else {
            echo "✗ Custom top text missing\n";
        }
        
        return $html;
    }
    
    public function test_multiple_blocks_isolation() {
        echo "\nTesting Multiple Blocks Isolation\n";
        echo "=================================\n";
        
        require_once dirname(__DIR__) . '/block-render.php';
        
        // First block
        $block1_attrs = array(
            'goal_mode' => 'custom',
            'patreon_username' => 'creator1',
            'custom_goal_id' => 'patrons-10',
            'toptext' => 'Block 1'
        );
        
        // Second block
        $block2_attrs = array(
            'goal_mode' => 'legacy',
            'patreon_userid' => '',
            'toptext' => 'Block 2'
        );
        
        $html1 = goalietron_block_render_callback($block1_attrs, '');
        $html2 = goalietron_block_render_callback($block2_attrs, '');
        
        // Extract widget IDs
        preg_match('/data-widget-id="(gt_[^"]+)"/', $html1, $matches1);
        preg_match('/data-widget-id="(gt_[^"]+)"/', $html2, $matches2);
        
        $id1 = $matches1[1] ?? 'not-found';
        $id2 = $matches2[1] ?? 'not-found';
        
        echo "Block 1 Widget ID: $id1\n";
        echo "Block 2 Widget ID: $id2\n";
        
        if ($id1 !== $id2 && $id1 !== 'not-found' && $id2 !== 'not-found') {
            echo "✓ Widget IDs are unique\n";
        } else {
            echo "✗ Widget IDs are not unique or not found\n";
        }
        
        // Check variable names
        if (strpos($html1, $id1 . '_PatreonData') !== false) {
            echo "✓ Block 1 has unique PatreonData variable\n";
        }
        
        if (strpos($html2, $id2 . '_PatreonData') !== false) {
            echo "✓ Block 2 has unique PatreonData variable\n";
        }
        
        return array($html1, $html2);
    }
    
    private function validate_html_structure($html) {
        echo "Validating HTML Structure:\n";
        echo "-------------------------\n";
        
        // Check for script tag with data
        if (preg_match('/<script[^>]*data-widget-id="(gt_[^"]+)"[^>]*>/', $html, $matches)) {
            echo "✓ Script tag with widget ID found: {$matches[1]}\n";
            
            // Check for unique variable names
            $widget_id = $matches[1];
            if (strpos($html, $widget_id . '_PatreonData') !== false) {
                echo "✓ Unique PatreonData variable found\n";
            } else {
                echo "✗ Unique PatreonData variable not found\n";
            }
        } else {
            echo "✗ Script tag with widget ID not found\n";
        }
        
        // Check for required elements
        $required_elements = array(
            'goalietron_toptext' => 'Top text element',
            'goalietron_goalmoneytext' => 'Goal money text element',
            'goalietron_meter' => 'Progress meter element',
            'goalietron_bottomtext' => 'Bottom text element'
        );
        
        foreach ($required_elements as $class => $description) {
            // Check if the class name appears in the HTML (either as standalone class or as part of multiple classes)
            if (strpos($html, $class) !== false) {
                echo "✓ $description found\n";
            } else {
                echo "✗ $description missing\n";
            }
        }
        
        // Check for meter color class
        if (preg_match('/class="meter\s+(\w+)"/', $html, $matches)) {
            echo "✓ Meter color class found: {$matches[1]}\n";
        }
        
        // Extract and display PatreonData
        if (preg_match('/var\s+\w+_PatreonData\s*=\s*({[^;]+});/', $html, $matches)) {
            $json_data = $matches[1];
            echo "\nExtracted PatreonData:\n";
            
            $decoded = json_decode($json_data, true);
            if ($decoded) {
                echo "✓ Valid JSON data\n";
                
                // Check structure
                if (isset($decoded['data']) && isset($decoded['included'])) {
                    echo "✓ Has required 'data' and 'included' fields\n";
                    
                    // Check for campaign data
                    foreach ($decoded['included'] as $item) {
                        if ($item['type'] === 'campaign') {
                            echo "✓ Campaign data found: patron_count = " . 
                                 ($item['attributes']['patron_count'] ?? 'N/A') . "\n";
                        }
                        if ($item['type'] === 'goal') {
                            echo "✓ Goal data found: " . 
                                 ($item['attributes']['title'] ?? 'N/A') . 
                                 " (target: " . (($item['attributes']['amount_cents'] ?? 0) / 100) . ")\n";
                        }
                    }
                }
            } else {
                echo "✗ Invalid JSON data\n";
            }
        }
    }
    
    private function format_html($html) {
        // Basic HTML formatting for readability
        $html = preg_replace('/</', "\n<", $html);
        $html = preg_replace('/>\s*</', ">\n<", $html);
        $html = trim($html);
        
        // Limit output for very long HTML
        if (strlen($html) > 2000) {
            return substr($html, 0, 2000) . "\n... (truncated)";
        }
        
        return $html;
    }
}

// Run the tests
$tester = new HTMLOutputTester();

// Test 1: Custom goal HTML structure
$tester->test_custom_goal_html_structure();

// Test 2: Block render output
$tester->test_block_render_output();

// Test 3: Multiple blocks isolation
$tester->test_multiple_blocks_isolation();