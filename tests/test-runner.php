<?php
/**
 * Test runner for GoalieTron plugin
 * 
 * Usage: php tests/test-runner.php
 */

// Load mock WordPress environment
require_once __DIR__ . '/mock-wordpress.php';

// Load the plugin files
require_once dirname(__DIR__) . '/PatreonClient.php';
require_once dirname(__DIR__) . '/goalietron.php';

// Test helper class
class GoalieTronTester {
    private $test_count = 0;
    private $pass_count = 0;
    private $fail_count = 0;
    
    public function run_all_tests() {
        echo "Starting GoalieTron Tests...\n";
        echo "============================\n\n";
        
        // Basic widget rendering
        $this->test_basic_widget_rendering();
        
        // Custom goal mode with data
        $this->test_custom_goal_mode();
        
        // Undefined goal_mode handling
        $this->test_undefined_goal_mode();
        
        // Multiple blocks (check for unique IDs)
        $this->test_multiple_blocks();
        
        // Different designs
        $this->test_different_designs();
        
        // Summary
        echo "\n============================\n";
        echo "Test Summary:\n";
        echo "Total tests: {$this->test_count}\n";
        echo "Passed: {$this->pass_count}\n";
        echo "Failed: {$this->fail_count}\n";
        echo "============================\n";
        
        return $this->fail_count === 0;
    }
    
    private function test_basic_widget_rendering() {
        echo "Basic Block Rendering\n";
        
        reset_wp_state();
        
        // Simulate WordPress initialization to register blocks
        simulate_wp_init();
        
        $goalietron = new GoalieTron();
        $args = array(
            'before_widget' => '<div class="goalietron-block">',
            'after_widget' => '</div>',
            'before_title' => '<h2>',
            'after_title' => '</h2>'
        );
        
        ob_start();
        $goalietron->DisplayWidget($args);
        $output = ob_get_clean();
        
        $this->assert_contains($output, '<div class="goalietron-block">', 'Block wrapper present');
        $this->assert_contains($output, '_PatreonData', 'PatreonData variable present');
        $this->assert_contains($output, 'goalietron_meter', 'Progress meter present');
        
        echo "\n";
    }
    
    private function test_custom_goal_mode() {
        echo "Custom Goal Mode\n";
        
        reset_wp_state();
        
        // Simulate WordPress initialization to register blocks
        simulate_wp_init();
        
        // Create instance with custom goal configuration
        $custom_options = array(
            'goal_mode' => 'custom',
            'custom_goal_id' => 'patrons-10',
            'patreon_username' => 'testuser',
            'design' => 'streamlined',
            'toptext' => 'Support us!',
            'bottomtext' => 'Thank you!'
        );
        
        $goalietron = GoalieTron::CreateInstance($custom_options);
        
        $args = array(
            'before_widget' => '<div class="widget">',
            'after_widget' => '</div>',
            'before_title' => '<h2>',
            'after_title' => '</h2>'
        );
        
        ob_start();
        $goalietron->DisplayWidget($args);
        $output = ob_get_clean();
        
        $this->assert_contains($output, 'Support us!', 'Top text present');
        $this->assert_contains($output, 'Thank you!', 'Bottom text present');
        $this->assert_contains($output, '_PatreonData', 'Unique PatreonData variable');
        $this->assert_contains($output, 'data-widget-id="gt_', 'Widget ID attribute present');
        
        // Check that it's not empty data
        $this->assert_not_contains($output, 'PatreonData = {}', 'PatreonData is not empty');
        
        echo "\n";
    }
    
    private function test_undefined_goal_mode() {
        echo "Undefined Goal Mode Handling\n";
        
        reset_wp_state();
        
        // Simulate WordPress initialization to register blocks
        simulate_wp_init();
        
        // Create instance without goal_mode specified (should default to custom)
        $undefined_options = array(
            'design' => 'default',
            'toptext' => 'Help us out!',
            'bottomtext' => 'Thanks!'
            // No goal_mode, custom_goal_id, or patreon_username
        );
        
        $goalietron = GoalieTron::CreateInstance($undefined_options);
        
        $args = array(
            'before_widget' => '<div class="widget">',
            'after_widget' => '</div>',
            'before_title' => '<h2>',
            'after_title' => '</h2>'
        );
        
        ob_start();
        $goalietron->DisplayWidget($args);
        $output = ob_get_clean();
        
        // Should still render without errors
        $this->assert_contains($output, 'Help us out!', 'Top text present with undefined goal_mode');
        $this->assert_contains($output, 'Thanks!', 'Bottom text present with undefined goal_mode');
        $this->assert_contains($output, '_PatreonData', 'PatreonData variable present with undefined goal_mode');
        $this->assert_contains($output, 'goalietron_meter', 'Progress meter present with undefined goal_mode');
        
        // Should have fallback data (not completely empty)
        $this->assert_not_contains($output, 'PatreonData = {}', 'Should use fallback data, not empty JSON');
        
        // Should contain test goal data since no specific goal is configured
        $this->assert_contains($output, 'Reach 10 patrons', 'Should use default test goal when no goal configured');
        
        echo "\n";
    }
    
    private function test_multiple_blocks() {
        echo "Multiple Blocks\n";
        
        reset_wp_state();
        
        // Simulate WordPress initialization to register blocks
        simulate_wp_init();
        
        // First block
        $block1_options = array(
            'goal_mode' => 'custom',
            'custom_goal_id' => 'patrons-10',
            'patreon_username' => 'user1',
            'design' => 'default'
        );
        
        $goalietron1 = GoalieTron::CreateInstance($block1_options);
        
        // Second block
        $block2_options = array(
            'goal_mode' => 'custom',
            'custom_goal_id' => 'patrons-10',
            'patreon_username' => 'user2',
            'design' => 'fancy'
        );
        
        $goalietron2 = GoalieTron::CreateInstance($block2_options);
        
        $args = array(
            'before_widget' => '<div class="widget">',
            'after_widget' => '</div>',
            'before_title' => '<h2>',
            'after_title' => '</h2>'
        );
        
        ob_start();
        $goalietron1->DisplayWidget($args);
        $output1 = ob_get_clean();
        
        ob_start();
        $goalietron2->DisplayWidget($args);
        $output2 = ob_get_clean();
        
        // Extract widget IDs
        preg_match('/data-widget-id="(gt_[^"]+)"/', $output1, $matches1);
        preg_match('/data-widget-id="(gt_[^"]+)"/', $output2, $matches2);
        
        $widget_id1 = isset($matches1[1]) ? $matches1[1] : '';
        $widget_id2 = isset($matches2[1]) ? $matches2[1] : '';
        
        $this->assert_not_equals($widget_id1, $widget_id2, 'Widget IDs are unique');
        $this->assert_contains($output1, $widget_id1 . '_PatreonData', 'Block 1 has unique variable');
        $this->assert_contains($output2, $widget_id2 . '_PatreonData', 'Block 2 has unique variable');
        
        echo "\n";
    }
    
    private function test_different_designs() {
        echo "Different Designs\n";
        
        $designs = array('default', 'fancy', 'minimal', 'streamlined', 'reversed', 'swapped');
        
        foreach ($designs as $design) {
            reset_wp_state();
            
            // Simulate WordPress initialization to register blocks
            simulate_wp_init();
            
            $options = array(
                'goal_mode' => 'custom',
                'custom_goal_id' => 'patrons-10',
                'patreon_username' => 'testuser',
                'design' => $design
            );
            
            $goalietron = GoalieTron::CreateInstance($options);
            
            $args = array(
                'before_widget' => '<div class="widget">',
                'after_widget' => '</div>',
                'before_title' => '<h2>',
                'after_title' => '</h2>'
            );
            
            ob_start();
            $goalietron->DisplayWidget($args);
            $output = ob_get_clean();
            
            $this->assert_contains($output, 'goalietron_meter', "Design '$design' contains meter");
        }
        
        echo "\n";
    }
    
    // Assertion helpers
    private function assert_contains($haystack, $needle, $message) {
        $this->test_count++;
        if (strpos($haystack, $needle) !== false) {
            echo "✓ PASS: $message\n";
            $this->pass_count++;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  Expected to find: '$needle'\n";
            echo "  In output of length: " . strlen($haystack) . "\n";
            $this->fail_count++;
        }
    }
    
    private function assert_not_contains($haystack, $needle, $message) {
        $this->test_count++;
        if (strpos($haystack, $needle) === false) {
            echo "✓ PASS: $message\n";
            $this->pass_count++;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  Did not expect to find: '$needle'\n";
            $this->fail_count++;
        }
    }
    
    private function assert_equals($actual, $expected, $message) {
        $this->test_count++;
        if ($actual === $expected) {
            echo "✓ PASS: $message\n";
            $this->pass_count++;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  Expected: '$expected'\n";
            echo "  Actual: '$actual'\n";
            $this->fail_count++;
        }
    }
    
    private function assert_not_equals($actual, $expected, $message) {
        $this->test_count++;
        if ($actual !== $expected) {
            echo "✓ PASS: $message\n";
            $this->pass_count++;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  Expected values to be different, but both were: '$actual'\n";
            $this->fail_count++;
        }
    }
}

// Run tests
$tester = new GoalieTronTester();
$success = $tester->run_all_tests();

// Exit with appropriate code
exit($success ? 0 : 1);