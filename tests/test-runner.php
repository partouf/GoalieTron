<?php
/**
 * Test runner for GoalieTron plugin
 * 
 * Usage: php tests/test-runner.php
 */

// Enable testing mode to use offline/mocked data
define('GOALIETRON_TESTING', true);

// Load mock WordPress environment
require_once __DIR__ . '/mock-wordpress.php';

// Include the test base class
require_once __DIR__ . '/GoalieTronTestBase.php';

// Load the plugin files
require_once dirname(__DIR__) . '/PatreonClient.php';
require_once dirname(__DIR__) . '/goalietron.php';


// Test helper class
class GoalieTronTester extends GoalieTronTestBase {
    
    public function run_all_tests() {
        echo "Starting GoalieTron Tests...\n";
        echo "============================\n\n";
        
        // Block categories filter (test first before other tests clear filters)
        $this->test_block_categories_filter();
        
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
        
        // Button display
        $this->test_button_display();
        
        // Block CSS class handling
        $this->test_block_css_classes();
        
        // Security tests
        $this->test_security();
        
        // Summary
        $this->printTestSummary('Basic Functionality');
        
        return $this->getTestResults()['success'];
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
            'patreon_username' => '',
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
                'patreon_username' => '',  // Empty username to use fallback data
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
    
    private function test_block_categories_filter() {
        echo "Block Categories Filter\n";
        
        // Don't reset filters for this test since the block_categories_all filter
        // is registered at global scope when goalietron.php loads
        reset_wp_options();
        reset_wp_assets();
        
        // Simulate WordPress initialization to register blocks and filters
        simulate_wp_init();
        
        // Test case 1: Categories without 'widgets' category (should add it)
        $initial_categories = array(
            array('slug' => 'text', 'title' => 'Text'),
            array('slug' => 'media', 'title' => 'Media'),
            array('slug' => 'design', 'title' => 'Design')
        );
        
        $filtered_categories = apply_filters('block_categories_all', $initial_categories);
        
        // Should add widgets category
        $widgets_found = false;
        foreach ($filtered_categories as $category) {
            if ($category['slug'] === 'widgets') {
                $widgets_found = true;
                break;
            }
        }
        
        $this->assert_equals($widgets_found, true, 'Widgets category added when missing');
        $this->assert_equals(count($filtered_categories), 4, 'Category count increased by 1');
        
        // Test case 2: Categories already containing 'widgets' category (should not duplicate)
        $categories_with_widgets = array(
            array('slug' => 'text', 'title' => 'Text'),
            array('slug' => 'widgets', 'title' => 'Widgets'),
            array('slug' => 'media', 'title' => 'Media')
        );
        
        $filtered_categories_2 = apply_filters('block_categories_all', $categories_with_widgets);
        
        $this->assert_equals(count($filtered_categories_2), 3, 'Category count unchanged when widgets already exists');
        
        // Test case 3: Empty categories array (should add widgets)
        $empty_categories = array();
        $filtered_empty = apply_filters('block_categories_all', $empty_categories);
        
        $this->assert_equals(count($filtered_empty), 1, 'Widgets category added to empty array');
        $this->assert_equals($filtered_empty[0]['slug'], 'widgets', 'Added category has correct slug');
        
        echo "\n";
    }
    
    private function test_button_display() {
        echo "Button Display\n";
        
        reset_wp_state();
        
        // Simulate WordPress initialization to register blocks
        simulate_wp_init();
        
        // Test case 1: Button enabled
        $button_enabled_options = array(
            'goal_mode' => 'custom',
            'custom_goal_id' => 'patrons-10',
            'patreon_username' => 'offline-test',
            'design' => 'default',
            'showbutton' => 'true',
            'toptext' => 'Support us!',
            'bottomtext' => 'Thank you!'
        );
        
        $goalietron_with_button = GoalieTron::CreateInstance($button_enabled_options);
        
        $args = array(
            'before_widget' => '<div class="widget">',
            'after_widget' => '</div>',
            'before_title' => '<h2>',
            'after_title' => '</h2>'
        );
        
        ob_start();
        $goalietron_with_button->DisplayWidget($args);
        $output_with_button = ob_get_clean();
        
        $this->assert_contains($output_with_button, 'Become a Patron!', 'Button text present when enabled');
        $this->assert_contains($output_with_button, 'https://www.patreon.com/offline-test', 'Button links to username when enabled');
        $this->assert_contains($output_with_button, 'data-patreon-widget-type="become-patron-button"', 'Button has Patreon widget attributes');
        
        // Test case 2: Button disabled (default behavior)
        $button_disabled_options = array(
            'goal_mode' => 'custom',
            'custom_goal_id' => 'patrons-10',
            'patreon_username' => 'offline-test',
            'design' => 'default',
            'showbutton' => 'false',
            'toptext' => 'Support us!',
            'bottomtext' => 'Thank you!'
        );
        
        $goalietron_no_button = GoalieTron::CreateInstance($button_disabled_options);
        
        ob_start();
        $goalietron_no_button->DisplayWidget($args);
        $output_no_button = ob_get_clean();
        
        $this->assert_not_contains($output_no_button, 'Become a Patron!', 'Button text absent when disabled');
        $this->assert_not_contains($output_no_button, 'https://www.patreon.com/offline-test', 'Button link absent when disabled');
        
        echo "\n";
    }
    
    private function test_block_css_classes() {
        echo "Block CSS Class Handling\n";
        
        reset_wp_state();
        
        // Simulate WordPress initialization to register blocks
        simulate_wp_init();
        
        // Include the block render callback
        require_once dirname(__DIR__) . '/block-render.php';
        
        // Test case 1: Default classes without custom className
        reset_mock_block_custom_class();
        
        $attributes = array(
            'goal_mode' => 'custom',
            'custom_goal_id' => 'patrons-10',
            'patreon_username' => '',
            'design' => 'default',
            'toptext' => 'Support us!',
            'bottomtext' => 'Thank you!'
        );
        
        $output = goalietron_block_render_callback($attributes, '');
        
        $this->assert_contains($output, 'class="widget goalietron_widget"', 'Default classes present without custom className');
        $this->assert_contains($output, 'Support us!', 'Block content rendered correctly');
        
        // Test case 2: Custom CSS class added from editor
        set_mock_block_custom_class('my-custom-class');
        
        $output_with_custom = goalietron_block_render_callback($attributes, '');
        
        $this->assert_contains($output_with_custom, 'widget goalietron_widget', 'Default classes still present with custom className');
        $this->assert_contains($output_with_custom, 'my-custom-class', 'Custom CSS class from editor included');
        $this->assert_contains($output_with_custom, 'class="widget goalietron_widget my-custom-class"', 'All classes properly combined');
        
        // Test case 3: Multiple custom classes
        set_mock_block_custom_class('class-one class-two custom-style');
        
        $output_multiple = goalietron_block_render_callback($attributes, '');
        
        $this->assert_contains($output_multiple, 'widget goalietron_widget', 'Default classes preserved with multiple custom classes');
        $this->assert_contains($output_multiple, 'class-one class-two custom-style', 'Multiple custom classes included');
        $this->assert_contains($output_multiple, 'class="widget goalietron_widget class-one class-two custom-style"', 'All classes properly combined with multiple custom classes');
        
        // Test case 4: Empty custom class (edge case)
        set_mock_block_custom_class('');
        
        $output_empty = goalietron_block_render_callback($attributes, '');
        
        $this->assert_contains($output_empty, 'class="widget goalietron_widget"', 'Default classes only when custom class is empty');
        $this->assert_not_contains($output_empty, 'class="widget goalietron_widget "', 'No trailing space when custom class is empty');
        
        // Test case 5: Block supports customClassName (verify block.json configuration)
        $block_json_path = dirname(__DIR__) . '/block.json';
        if (file_exists($block_json_path)) {
            $block_json = json_decode(file_get_contents($block_json_path), true);
            $this->assert_equals(
                isset($block_json['supports']['customClassName']) && $block_json['supports']['customClassName'] === true, 
                true, 
                'Block.json supports customClassName'
            );
        }
        
        echo "\n";
    }
    
    private function test_security() {
        echo "Security (XSS/Injection Prevention)\n";
        
        reset_wp_state();
        
        // Simulate WordPress initialization
        simulate_wp_init();
        
        // Include the block render callback
        require_once dirname(__DIR__) . '/block-render.php';
        
        // Test 1: XSS in title
        $xss_title = '<script>alert("XSS")</script>My Widget';
        $attributes = array(
            'title' => $xss_title,
            'goal_mode' => 'custom',
            'custom_goal_id' => 'patrons-10',
            'patreon_username' => '',
            'design' => 'default',
            'toptext' => 'Normal text',
            'bottomtext' => 'Normal bottom'
        );
        
        $output = goalietron_block_render_callback($attributes, '');
        
        $this->assert_not_contains($output, '<script>alert("XSS")</script>', 'Script tag should be stripped from title');
        // With proper WordPress sanitization, the script tag and its content are completely removed
        $this->assert_contains($output, '<h2 class="widget-title">My Widget', 'Title should be sanitized to safe content only');
        
        // Test 2: SQL injection in custom_goal_id
        $sql_injection = "patrons-10'; DROP TABLE goals; --";
        $attributes2 = array(
            'custom_goal_id' => $sql_injection,
            'patreon_username' => '',
            'design' => 'default'
        );
        
        $instance = GoalieTron::CreateInstance($attributes2);
        $this->assert_equals($instance->options['custom_goal_id'], 'patrons-10DROPTABLEgoals--', 'SQL injection characters removed');
        
        // Test 3: Invalid design value
        $attributes3 = array(
            'design' => '<script>alert("XSS")</script>',
            'patreon_username' => ''
        );
        
        $instance3 = GoalieTron::CreateInstance($attributes3);
        $this->assert_equals($instance3->options['design'], 'default', 'Invalid design defaults to safe value');
        
        // Test 4: XSS in toptext
        $html_injection = '<img src=x onerror="alert(\'XSS\')">';
        $attributes4 = array(
            'toptext' => $html_injection,
            'goal_mode' => 'custom',
            'patreon_username' => ''
        );
        
        $output4 = goalietron_block_render_callback($attributes4, '');
        $this->assert_not_contains($output4, '<img src=x onerror=', 'Image XSS stripped from output');
        
        echo "\n";
    }
    
}

// Run tests
$tester = new GoalieTronTester();
$success = $tester->run_all_tests();

// Exit with appropriate code
exit($success ? 0 : 1);
