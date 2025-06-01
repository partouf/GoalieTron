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
        
        // Test 1: Basic widget rendering
        $this->test_basic_widget_rendering();
        
        // Test 2: Custom goal mode with data
        $this->test_custom_goal_mode();
        
        // Test 3: Legacy mode (should return empty)
        $this->test_legacy_mode();
        
        // Test 4: Multiple blocks (check for unique IDs)
        $this->test_multiple_blocks();
        
        // Test 5: Different designs
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
        echo "Test 1: Basic Block Rendering\n";
        
        reset_wp_options();
        
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
        echo "Test 2: Custom Goal Mode\n";
        
        reset_wp_options();
        
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
    
    private function test_legacy_mode() {
        echo "Test 3: Legacy Mode\n";
        
        reset_wp_options();
        
        $legacy_options = array(
            'goal_mode' => 'legacy',
            'patreon_userid' => '', // Empty user ID
            'design' => 'default'
        );
        
        $goalietron = GoalieTron::CreateInstance($legacy_options);
        
        $args = array(
            'before_widget' => '<div class="widget">',
            'after_widget' => '</div>',
            'before_title' => '<h2>',
            'after_title' => '</h2>'
        );
        
        ob_start();
        $goalietron->DisplayWidget($args);
        $output = ob_get_clean();
        
        $this->assert_contains($output, '_PatreonData = {}', 'Legacy mode returns empty data');
        
        echo "\n";
    }
    
    private function test_multiple_blocks() {
        echo "Test 4: Multiple Blocks\n";
        
        reset_wp_options();
        
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
            'goal_mode' => 'legacy',
            'patreon_userid' => '',
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
        echo "Test 5: Different Designs\n";
        
        $designs = array('default', 'fancy', 'minimal', 'streamlined', 'reversed', 'swapped');
        
        foreach ($designs as $design) {
            reset_wp_options();
            
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