<?php
/**
 * Dedicated test for GoalieTron CSS class handling
 * 
 * Usage: php tests/test-css-classes.php
 */

// Load mock WordPress environment
require_once __DIR__ . '/mock-wordpress.php';

// Load the plugin files
require_once dirname(__DIR__) . '/PatreonClient.php';
require_once dirname(__DIR__) . '/goalietron.php';

// Test helper class for CSS classes
class GoalieTronCSSClassTester {
    private $test_count = 0;
    private $pass_count = 0;
    private $fail_count = 0;
    
    public function run_css_class_tests() {
        echo "Starting GoalieTron CSS Class Tests...\n";
        echo "=====================================\n\n";
        
        $this->test_block_supports_custom_classname();
        $this->test_get_block_wrapper_attributes_usage();
        $this->test_default_classes_preservation();
        $this->test_custom_classes_integration();
        $this->test_edge_cases();
        
        // Summary
        echo "\n=====================================\n";
        echo "CSS Class Test Summary:\n";
        echo "Total tests: {$this->test_count}\n";
        echo "Passed: {$this->pass_count}\n";
        echo "Failed: {$this->fail_count}\n";
        echo "=====================================\n";
        
        return $this->fail_count === 0;
    }
    
    private function test_block_supports_custom_classname() {
        echo "Block Configuration Support\n";
        
        // Test that block.json properly supports customClassName
        $block_json_path = dirname(__DIR__) . '/block.json';
        $this->assert_file_exists($block_json_path, 'Block.json file exists');
        
        if (file_exists($block_json_path)) {
            $block_json = json_decode(file_get_contents($block_json_path), true);
            
            $this->assert_not_null($block_json, 'Block.json is valid JSON');
            $this->assert_true(
                isset($block_json['supports']['customClassName']), 
                'Block.json has customClassName support defined'
            );
            $this->assert_equals(
                $block_json['supports']['customClassName'], 
                true, 
                'Block.json customClassName support is enabled'
            );
        }
        
        echo "\n";
    }
    
    private function test_get_block_wrapper_attributes_usage() {
        echo "Block Wrapper Attributes Usage\n";
        
        reset_wp_state();
        simulate_wp_init();
        
        // Include the block render callback
        require_once dirname(__DIR__) . '/block-render.php';
        
        // Test that the render callback properly uses get_block_wrapper_attributes
        $attributes = array(
            'goal_mode' => 'custom',
            'custom_goal_id' => 'patrons-10',
            'patreon_username' => 'testuser',
            'design' => 'default'
        );
        
        // Mock custom class to verify integration
        set_mock_block_custom_class('editor-custom-class');
        
        $output = goalietron_block_render_callback($attributes, '');
        
        // Verify the output contains proper wrapper attributes
        $this->assert_contains(
            $output, 
            'class="widget goalietron_widget editor-custom-class"', 
            'get_block_wrapper_attributes properly integrates custom classes'
        );
        
        echo "\n";
    }
    
    private function test_default_classes_preservation() {
        echo "Default Classes Preservation\n";
        
        reset_wp_state();
        simulate_wp_init();
        require_once dirname(__DIR__) . '/block-render.php';
        
        $attributes = array(
            'goal_mode' => 'custom',
            'custom_goal_id' => 'patrons-10',
            'patreon_username' => 'testuser',
            'design' => 'default'
        );
        
        // Test without any custom classes
        reset_mock_block_custom_class();
        $output = goalietron_block_render_callback($attributes, '');
        
        $this->assert_contains($output, 'widget', 'Default widget class preserved');
        $this->assert_contains($output, 'goalietron_widget', 'Default goalietron_widget class preserved');
        $this->assert_contains($output, 'class="widget goalietron_widget"', 'Default classes properly formatted');
        
        // Test with custom classes - defaults should still be there
        set_mock_block_custom_class('my-custom-class');
        $output_with_custom = goalietron_block_render_callback($attributes, '');
        
        $this->assert_contains($output_with_custom, 'widget', 'Default widget class preserved with custom classes');
        $this->assert_contains($output_with_custom, 'goalietron_widget', 'Default goalietron_widget class preserved with custom classes');
        
        echo "\n";
    }
    
    private function test_custom_classes_integration() {
        echo "Custom Classes Integration\n";
        
        reset_wp_state();
        simulate_wp_init();
        require_once dirname(__DIR__) . '/block-render.php';
        
        $attributes = array(
            'goal_mode' => 'custom',
            'custom_goal_id' => 'patrons-10',
            'patreon_username' => 'testuser',
            'design' => 'default'
        );
        
        // Test single custom class
        set_mock_block_custom_class('custom-style');
        $output = goalietron_block_render_callback($attributes, '');
        
        $this->assert_contains($output, 'custom-style', 'Single custom class included');
        $this->assert_contains($output, 'class="widget goalietron_widget custom-style"', 'Single custom class properly positioned');
        
        // Test multiple custom classes
        set_mock_block_custom_class('class-one class-two theme-dark');
        $output_multiple = goalietron_block_render_callback($attributes, '');
        
        $this->assert_contains($output_multiple, 'class-one', 'First custom class included');
        $this->assert_contains($output_multiple, 'class-two', 'Second custom class included');
        $this->assert_contains($output_multiple, 'theme-dark', 'Third custom class included');
        $this->assert_contains($output_multiple, 'class="widget goalietron_widget class-one class-two theme-dark"', 'Multiple custom classes properly formatted');
        
        // Test custom class with special characters (valid CSS class names)
        set_mock_block_custom_class('my-class_name block123');
        $output_special = goalietron_block_render_callback($attributes, '');
        
        $this->assert_contains($output_special, 'my-class_name', 'Custom class with hyphen and underscore included');
        $this->assert_contains($output_special, 'block123', 'Custom class with numbers included');
        
        echo "\n";
    }
    
    private function test_edge_cases() {
        echo "Edge Cases\n";
        
        reset_wp_state();
        simulate_wp_init();
        require_once dirname(__DIR__) . '/block-render.php';
        
        $attributes = array(
            'goal_mode' => 'custom',
            'custom_goal_id' => 'patrons-10',
            'patreon_username' => 'testuser',
            'design' => 'default'
        );
        
        // Test empty custom class
        set_mock_block_custom_class('');
        $output_empty = goalietron_block_render_callback($attributes, '');
        
        $this->assert_contains($output_empty, 'class="widget goalietron_widget"', 'Empty custom class results in clean default classes');
        $this->assert_not_contains($output_empty, 'class="widget goalietron_widget "', 'No trailing space with empty custom class');
        
        // Test custom class with only spaces
        set_mock_block_custom_class('   ');
        $output_spaces = goalietron_block_render_callback($attributes, '');
        
        // The trim in get_block_wrapper_attributes should handle this
        $this->assert_not_contains($output_spaces, 'class="widget goalietron_widget    "', 'Spaces-only custom class handled gracefully');
        
        // Test very long custom class name
        $long_class = str_repeat('a', 100);
        set_mock_block_custom_class($long_class);
        $output_long = goalietron_block_render_callback($attributes, '');
        
        $this->assert_contains($output_long, $long_class, 'Very long custom class name included');
        $this->assert_contains($output_long, 'widget goalietron_widget', 'Default classes preserved with long custom class');
        
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
    
    private function assert_true($value, $message) {
        $this->assert_equals($value, true, $message);
    }
    
    private function assert_not_null($value, $message) {
        $this->test_count++;
        if ($value !== null) {
            echo "✓ PASS: $message\n";
            $this->pass_count++;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  Expected: non-null value\n";
            echo "  Actual: null\n";
            $this->fail_count++;
        }
    }
    
    private function assert_file_exists($filepath, $message) {
        $this->test_count++;
        if (file_exists($filepath)) {
            echo "✓ PASS: $message\n";
            $this->pass_count++;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  Expected file to exist: $filepath\n";
            $this->fail_count++;
        }
    }
}

// Run the CSS class tests
$tester = new GoalieTronCSSClassTester();
$success = $tester->run_css_class_tests();

// Exit with appropriate code
exit($success ? 0 : 1);