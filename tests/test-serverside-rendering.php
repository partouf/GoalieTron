<?php
/**
 * Server-Side Rendering Tests for GoalieTron Plugin
 * 
 * Tests the server-side calculation and rendering functionality that provides
 * immediate visual feedback in WordPress editor before JavaScript runs.
 */

// Set up the environment
define('ABSPATH', dirname(__DIR__) . '/');
define('WP_DEBUG', true);

// Include the mock WordPress environment
require_once dirname(__FILE__) . '/mock-wordpress.php';

// Include the test base class
require_once dirname(__FILE__) . '/GoalieTronTestBase.php';

// Include the main plugin file
require_once dirname(__DIR__) . '/goalietron.php';

class GoalieTronServerSideRenderingTester extends GoalieTronTestBase {
    
    public function run() {
        echo "Starting GoalieTron Server-Side Rendering Tests...\n";
        echo "==================================================\n\n";
        
        // Test server-side progress calculation
        $this->test_server_side_progress_calculation();
        
        // Test server-side goal text generation
        $this->test_server_side_goal_text();
        
        // Test HTML injection and escaping
        $this->test_html_injection_prevention();
        
        // Test different goal types
        $this->test_different_goal_types();
        
        // Test edge cases
        $this->test_edge_cases();
        
        // Summary
        $this->printTestSummary('Server-Side Rendering');
        
        return $this->getTestResults()['success'];
    }
    
    private function test_server_side_progress_calculation() {
        echo "Server-Side Progress Calculation Tests\n";
        echo "-------------------------------------\n";
        
        // Test 1: Basic patron goal with realistic progress
        $options = array(
            'goal_mode' => 'custom',
            'custom_goal_id' => 'patrons-25',
            'patreon_username' => 'scishow',
            'design' => 'default'
        );
        
        $instance = GoalieTron::CreateInstance($options);
        $output = $this->getWidgetOutput($instance);
        
        // Should show progress bar with width > 0%
        $this->assert_contains_regex($output, '/style="width: (\d+)%"/', 'Progress bar should have calculated width');
        
        if (preg_match('/style="width: (\d+)%"/', $output, $matches)) {
            $width = intval($matches[1]);
            $this->assert_greater_than($width, 0, 'Progress bar width should be greater than 0');
            $this->assert_less_than_or_equal($width, 100, 'Progress bar width should not exceed 100%');
        }
        
        // Test 2: Completed goal should show 100%
        // SciShow with patrons-25 should be completed (15,126 > 25)
        if (preg_match('/style="width: (\d+)%"/', $output, $matches)) {
            $width = intval($matches[1]);
            $this->assert_equals($width, 100, 'SciShow patrons-25 goal should show 100% (goal completed)');
        }
        
        echo "\n";
    }
    
    private function test_server_side_goal_text() {
        echo "Server-Side Goal Text Generation Tests\n";
        echo "-------------------------------------\n";
        
        // Test 1: Completed patron goal text
        $options = array(
            'goal_mode' => 'custom',
            'custom_goal_id' => 'patrons-25',
            'patreon_username' => 'scishow',
            'design' => 'default'
        );
        
        $instance = GoalieTron::CreateInstance($options);
        $output = $this->getWidgetOutput($instance);
        
        // Should show "reached" text for completed goals
        $this->assert_contains($output, '25 - reached!', 'Completed patron goal should show reached text');
        
        // Test 2: Goal text should be properly escaped in HTML
        $this->assert_contains($output, '<span class="goalietron_goalmoneytext">25 - reached!</span>', 'Goal text should be in proper HTML structure');
        
        // Test 3: Income goal text format
        $income_options = array(
            'goal_mode' => 'custom',
            'custom_goal_id' => 'equipment-fund', // $250 income goal
            'patreon_username' => 'scishow',
            'design' => 'default'
        );
        
        $income_instance = GoalieTron::CreateInstance($income_options);
        $income_output = $this->getWidgetOutput($income_instance);
        
        // Income goals should have goal text (format may vary based on earnings visibility)
        $this->assert_contains($income_output, 'goalietron_goalmoneytext', 'Income goal should have goal money text element');
        
        echo "\n";
    }
    
    private function test_html_injection_prevention() {
        echo "HTML Injection Prevention Tests\n";
        echo "------------------------------\n";
        
        // Create malicious JSON data to test server-side processing
        $malicious_json = json_encode(array(
            'data' => array(
                'type' => 'user',
                'id' => 'test-user'
            ),
            'included' => array(
                array(
                    'type' => 'campaign',
                    'id' => 'test-campaign',
                    'attributes' => array(
                        'patron_count' => 50,
                        'paid_member_count' => 25,
                        'creation_count' => 100,
                        'pledge_sum' => 15000 // $150 in cents
                    )
                ),
                array(
                    'type' => 'goal',
                    'id' => 'test-goal',
                    'attributes' => array(
                        'amount_cents' => 10000, // $100 target
                        'description' => '<script>alert("XSS")</script>Test Goal',
                        'title' => '<script>alert("XSS")</script>Test Goal',
                        'goal_type' => 'income'
                    )
                )
            )
        ));
        
        // Test server-side processing with malicious data
        $options = array(
            'goal_mode' => 'custom',
            'custom_goal_id' => 'test-goal',
            'patreon_username' => 'testuser',
            'design' => 'default'
        );
        
        $instance = GoalieTron::CreateInstance($options);
        
        // Manually inject malicious data for testing
        $reflection = new ReflectionClass($instance);
        $property = $reflection->getProperty('options');
        $property->setAccessible(true);
        $options_array = $property->getValue($instance);
        $options_array['cache'] = $malicious_json;
        $options_array['cache_age'] = time(); // Make cache valid
        $property->setValue($instance, $options_array);
        
        $output = $this->getWidgetOutput($instance);
        
        // Should not contain unescaped script tags
        $this->assert_not_contains($output, '<script>alert("XSS")</script>', 'Script tags should be escaped in goal text');
        
        // Should not contain executable script content
        $this->assert_not_contains($output, 'alert("XSS")', 'JavaScript code should not be executable');
        
        echo "\n";
    }
    
    private function test_different_goal_types() {
        echo "Different Goal Types Tests\n";
        echo "-------------------------\n";
        
        // Test income goal calculations
        $test_data = array(
            'patrons' => array(
                'goal_id' => 'patrons-10',
                'current_field' => 'patron_count',
                'expected_format' => '/\d+ of \d+|\d+ - reached!/'
            ),
            'members' => array(
                'goal_id' => 'paid-members-5',
                'current_field' => 'paid_member_count', 
                'expected_format' => '/\d+ of \d+|\d+ - reached!/'
            ),
            'posts' => array(
                'goal_id' => 'weekly-content',
                'current_field' => 'creation_count',
                'expected_format' => '/\d+ of \d+|\d+ - reached!/'
            ),
            'income' => array(
                'goal_id' => 'coffee-fund',
                'current_field' => 'pledge_sum',
                'expected_format' => '/goalietron_goalmoneytext/'
            )
        );
        
        foreach ($test_data as $goal_type => $test_config) {
            $options = array(
                'goal_mode' => 'custom',
                'custom_goal_id' => $test_config['goal_id'],
                'patreon_username' => 'scishow',
                'design' => 'default'
            );
            
            $instance = GoalieTron::CreateInstance($options);
            $output = $this->getWidgetOutput($instance);
            
            // Check that goal text follows expected format
            $this->assert_contains_regex($output, $test_config['expected_format'], "Goal type '$goal_type' should show correct format");
            
            // Check that progress bar has some width
            $this->assert_contains_regex($output, '/style="width: \d+%"/', "Goal type '$goal_type' should have calculated progress width");
        }
        
        echo "\n";
    }
    
    private function test_edge_cases() {
        echo "Edge Cases Tests\n";
        echo "---------------\n";
        
        // Test 1: Missing goal data
        $options = array(
            'goal_mode' => 'custom',
            'custom_goal_id' => 'nonexistent-goal',
            'patreon_username' => 'scishow',
            'design' => 'default'
        );
        
        $instance = GoalieTron::CreateInstance($options);
        $output = $this->getWidgetOutput($instance);
        
        // Should handle missing goals gracefully
        $this->assert_contains($output, 'goalietron_goalmoneytext', 'Missing goal should have goal text element');
        $this->assert_contains($output, 'width:', 'Missing goal should have progress bar width set');
        
        // Test 2: Empty username
        $options2 = array(
            'goal_mode' => 'custom',
            'custom_goal_id' => 'patrons-25',
            'patreon_username' => '',
            'design' => 'default'
        );
        
        $instance2 = GoalieTron::CreateInstance($options2);
        $output2 = $this->getWidgetOutput($instance2);
        
        // Should fall back to test data
        $this->assert_contains_regex($output2, '/\d+ of \d+/', 'Empty username should use fallback test data');
        
        // Test 3: Invalid JSON data
        $options3 = array(
            'goal_mode' => 'custom',
            'custom_goal_id' => 'patrons-25',
            'patreon_username' => 'testuser',
            'design' => 'default'
        );
        
        $instance3 = GoalieTron::CreateInstance($options3);
        
        // Inject invalid JSON
        $reflection = new ReflectionClass($instance3);
        $property = $reflection->getProperty('options');
        $property->setAccessible(true);
        $options_array = $property->getValue($instance3);
        $options_array['cache'] = 'invalid json {';
        $options_array['cache_age'] = time();
        $property->setValue($instance3, $options_array);
        
        $output3 = $this->getWidgetOutput($instance3);
        
        // Should handle invalid JSON gracefully
        $this->assert_contains($output3, 'style="width: 0%"', 'Invalid JSON should result in 0% progress');
        $this->assert_contains($output3, '<span class="goalietron_goalmoneytext"></span>', 'Invalid JSON should result in empty goal text');
        
        echo "\n";
    }
    
    private function getWidgetOutput($instance) {
        $widget_args = array(
            'before_widget' => '<div class="widget goalietron_widget">',
            'after_widget' => '</div>',
            'before_title' => '<h2 class="widget-title">',
            'after_title' => '</h2>'
        );
        
        ob_start();
        $instance->DisplayWidget($widget_args);
        return ob_get_clean();
    }
    
}

// Run the tests
$tester = new GoalieTronServerSideRenderingTester();
$success = $tester->run();
exit($success ? 0 : 1);
