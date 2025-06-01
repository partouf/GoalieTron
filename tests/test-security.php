<?php
/**
 * Security Tests for GoalieTron Plugin
 * 
 * Tests to ensure proper sanitization, escaping, and validation
 * of user input and output to prevent security vulnerabilities.
 */

// Enable testing mode to use offline/mocked data
define('GOALIETRON_TESTING', true);

// Set up the environment
define('ABSPATH', dirname(__DIR__) . '/');
define('WP_DEBUG', true);

// Include the mock WordPress environment
require_once dirname(__FILE__) . '/mock-wordpress.php';

// Include the test base class
require_once dirname(__FILE__) . '/GoalieTronTestBase.php';

// Include the main plugin file
require_once dirname(__DIR__) . '/goalietron.php';

class GoalieTronSecurityTester extends GoalieTronTestBase {
    
    public function run() {
        echo "Starting GoalieTron Security Tests...\n";
        echo "=====================================\n\n";
        
        // Test XSS prevention in widget output
        $this->test_xss_prevention();
        
        // Test input sanitization
        $this->test_input_sanitization();
        
        // Test file path validation
        $this->test_file_path_validation();
        
        // Test JSON validation
        $this->test_json_validation();
        
        // Summary
        $this->printTestSummary('Security');
        
        return $this->getTestResults()['success'];
    }
    
    private function test_xss_prevention() {
        echo "XSS Prevention Tests\n";
        echo "-------------------\n";
        
        // Test 1: Script tag in title should be escaped
        $xss_title = '<script>alert("XSS")</script>My Widget';
        $attributes = array(
            'title' => $xss_title,
            'goal_mode' => 'custom',
            'custom_goal_id' => 'patrons-10',
            'patreon_username' => 'testuser',
            'design' => 'default',
            'toptext' => 'Normal text',
            'bottomtext' => 'Normal bottom'
        );
        
        // Create instance with XSS attempt
        $instance = GoalieTron::CreateInstance($attributes);
        
        // Capture widget output
        ob_start();
        $widget_args = array(
            'before_widget' => '<div class="widget">',
            'after_widget' => '</div>',
            'before_title' => '<h2>',
            'after_title' => '</h2>'
        );
        $instance->DisplayWidget($widget_args);
        $output = ob_get_clean();
        
        // Check that script tag is not present in the title (it gets completely stripped)
        $this->assert_not_contains($output, '<h2><script>alert("XSS")</script>', 'Script tag should not be in output');
        $this->assert_contains($output, '<h2>My Widget</h2>', 'Title should be sanitized to safe content only');
        
        // Test 2: HTML injection in toptext
        $html_injection = '<img src=x onerror="alert(\'XSS\')">';
        $attributes['toptext'] = $html_injection;
        $instance2 = GoalieTron::CreateInstance($attributes);
        
        ob_start();
        $instance2->DisplayWidget($widget_args);
        $output2 = ob_get_clean();
        
        // The image tag gets stripped by sanitize_text_field, so check it's not in output
        $this->assert_not_contains($output2, '<img src=x onerror=', 'Image XSS should not be in output');
        $this->assert_not_contains($output2, 'onerror=', 'No onerror attribute should be present');
        
        // Test 3: JavaScript URL in patreon_username
        $js_url = 'javascript:alert("XSS")';
        $attributes['patreon_username'] = $js_url;
        $instance3 = GoalieTron::CreateInstance($attributes);
        
        // Check that javascript: URLs are sanitized
        $this->assert_equals($instance3->options['patreon_username'], 'javascriptalertXSS', 'JavaScript URL should be sanitized');
        
        // Test 4: Actual XSS attempt that would execute if not sanitized
        $actual_xss = '"><script>document.body.innerHTML="HACKED";</script><div class="';
        $attributes['toptext'] = $actual_xss;
        $instance4 = GoalieTron::CreateInstance($attributes);
        
        ob_start();
        $instance4->DisplayWidget($widget_args);
        $output4 = ob_get_clean();
        
        $this->assert_not_contains($output4, '<script>', 'No script tags should be in output');
        // Debug to see actual output
        if (preg_match('/<div class="goalietron_toptext">(.*?)<\/div>/s', $output4, $matches)) {
            echo "  DEBUG: Sanitized toptext: " . htmlspecialchars(substr($matches[1], 0, 100)) . "\n";
        }
        // With proper WordPress sanitization, most malicious content is stripped  
        $this->assert_not_contains($output4, 'document.body.innerHTML', 'XSS JavaScript code should be stripped');
        
        // Test 5: SQL injection attempt in title
        $sql_title = "'; DROP TABLE wp_options; --";
        $attributes['title'] = $sql_title;
        $instance5 = GoalieTron::CreateInstance($attributes);
        
        ob_start();
        $instance5->DisplayWidget($widget_args);
        $output5 = ob_get_clean();
        
        $this->assert_contains($output5, '&#039;; DROP TABLE wp_options; --', 'SQL injection should be escaped');
        
        echo "\n";
    }
    
    private function test_input_sanitization() {
        echo "Input Sanitization Tests\n";
        echo "-----------------------\n";
        
        // Test 1: SQL injection attempt in custom_goal_id
        $sql_injection = "patrons-10'; DROP TABLE goals; --";
        $attributes = array(
            'custom_goal_id' => $sql_injection,
            'patreon_username' => 'testuser'
        );
        
        $instance = GoalieTron::CreateInstance($attributes);
        
        // Should only contain alphanumeric, underscore, hyphen
        $this->assert_equals($instance->options['custom_goal_id'], 'patrons-10DROPTABLEgoals--', 'SQL injection characters should be removed');
        
        // Test 2: Invalid design value
        $attributes['design'] = 'malicious_design';
        $instance2 = GoalieTron::CreateInstance($attributes);
        
        $this->assert_equals($instance2->options['design'], 'default', 'Invalid design should default to "default"');
        
        // Test 3: Invalid boolean value
        $attributes['showbutton'] = 'yes'; // Should be 'true' or 'false'
        $instance3 = GoalieTron::CreateInstance($attributes);
        
        $this->assert_equals($instance3->options['showbutton'], 'false', 'Invalid boolean should default to "false"');
        
        // Test 4: Malicious metercolor
        $attributes['metercolor'] = 'green; background: url(evil.js)';
        $instance4 = GoalieTron::CreateInstance($attributes);
        
        $this->assert_equals($instance4->options['metercolor'], 'green', 'Invalid color should default to "green"');
        
        // Test 5: Cache age should be integer
        $attributes['cache_age'] = '12345; malicious code';
        $instance5 = GoalieTron::CreateInstance($attributes);
        
        $this->assert_equals($instance5->options['cache_age'], 12345, 'Cache age should be sanitized to integer');
        
        echo "\n";
    }
    
    private function test_file_path_validation() {
        echo "File Path Validation Tests\n";
        echo "-------------------------\n";
        
        // Note: We can't directly test the file path validation in loadCustomGoals()
        // because it's a private method, but we can verify the security measures exist
        
        // Test that PatreonClient validates file paths
        require_once dirname(__DIR__) . '/PatreonClient.php';
        $client = new PatreonClient();
        
        // Test 1: Attempt to load file outside plugin directory
        $malicious_path = '/etc/passwd';
        $result = $client->loadCustomGoalsFromFile($malicious_path);
        
        $this->assert_equals($result, false, 'Should reject file paths outside plugin directory');
        
        // Test 2: Non-existent file
        $fake_path = dirname(__DIR__) . '/non-existent-file.json';
        $result2 = $client->loadCustomGoalsFromFile($fake_path);
        
        $this->assert_equals($result2, false, 'Should return false for non-existent files');
        
        echo "\n";
    }
    
    private function test_json_validation() {
        echo "JSON Validation Tests\n";
        echo "--------------------\n";
        
        // Create test JSON files
        $test_dir = dirname(__FILE__) . '/test-json-files/';
        if (!file_exists($test_dir)) {
            mkdir($test_dir, 0755, true);
        }
        
        // Test 1: Malformed JSON
        $malformed_json = '{"patrons-10": {"type": "patrons", "target": 100, "title": "Test Goal"'; // Missing closing braces
        file_put_contents($test_dir . 'malformed.json', $malformed_json);
        
        $client = new PatreonClient();
        $result = $client->loadCustomGoalsFromFile($test_dir . 'malformed.json');
        
        $this->assert_equals($result, false, 'Should reject malformed JSON');
        
        // Test 2: Invalid goal structure (missing required fields)
        $invalid_goal = json_encode(array(
            'bad-goal' => array(
                'type' => 'patrons'
                // Missing 'target' and 'title'
            )
        ));
        file_put_contents($test_dir . 'invalid-goal.json', $invalid_goal);
        
        $client2 = new PatreonClient();
        $result2 = $client2->loadCustomGoalsFromFile($test_dir . 'invalid-goal.json');
        $goals = $client2->getCustomGoals();
        
        $this->assert_equals(isset($goals['bad-goal']), false, 'Should reject goals missing required fields');
        
        // Test 3: Invalid goal type
        $bad_type = json_encode(array(
            'xss-goal' => array(
                'type' => '<script>alert("XSS")</script>',
                'target' => 100,
                'title' => 'Test Goal'
            )
        ));
        file_put_contents($test_dir . 'bad-type.json', $bad_type);
        
        $client3 = new PatreonClient();
        $result3 = $client3->loadCustomGoalsFromFile($test_dir . 'bad-type.json');
        $goals3 = $client3->getCustomGoals();
        
        $this->assert_equals(isset($goals3['xss-goal']), false, 'Should reject invalid goal types');
        
        // Test 4: Negative target value
        $negative_target = json_encode(array(
            'negative-goal' => array(
                'type' => 'patrons',
                'target' => -100,
                'title' => 'Negative Goal'
            )
        ));
        file_put_contents($test_dir . 'negative.json', $negative_target);
        
        $client4 = new PatreonClient();
        $result4 = $client4->loadCustomGoalsFromFile($test_dir . 'negative.json');
        $goals4 = $client4->getCustomGoals();
        
        $this->assert_equals(isset($goals4['negative-goal']), false, 'Should reject negative target values');
        
        // Test 5: Goal ID with special characters
        $bad_id = json_encode(array(
            'goal-<script>' => array(
                'type' => 'patrons',
                'target' => 100,
                'title' => 'Test Goal'
            )
        ));
        file_put_contents($test_dir . 'bad-id.json', $bad_id);
        
        $client5 = new PatreonClient();
        $result5 = $client5->loadCustomGoalsFromFile($test_dir . 'bad-id.json');
        $goals5 = $client5->getCustomGoals();
        
        $this->assert_equals(isset($goals5['goal-<script>']), false, 'Should reject goal IDs with special characters');
        
        // Test 6: Valid goal should be sanitized
        $long_title = str_repeat('A', 200); // 200 characters - within limit
        $valid_goal = json_encode(array(
            'valid-goal' => array(
                'type' => 'patrons',
                'target' => 100.5, // Should be converted to integer
                'title' => $long_title
            )
        ));
        file_put_contents($test_dir . 'valid.json', $valid_goal);
        
        $client6 = new PatreonClient();
        $result6 = $client6->loadCustomGoalsFromFile($test_dir . 'valid.json');
        $goals6 = $client6->getCustomGoals();
        
        // Debug output
        if (!isset($goals6['valid-goal'])) {
            echo "  DEBUG: Valid goal test failed\n";
            echo "  File contents: " . file_get_contents($test_dir . 'valid.json') . "\n";
            echo "  Load result: " . var_export($result6, true) . "\n";
            echo "  Goals loaded: " . var_export($goals6, true) . "\n";
        }
        
        $this->assert_equals(isset($goals6['valid-goal']), true, 'Valid goal should be accepted');
        if (isset($goals6['valid-goal'])) {
            $this->assert_equals($goals6['valid-goal']['target'], 100, 'Target should be sanitized to integer');
            $this->assert_equals(strlen($goals6['valid-goal']['title']), 200, 'Title should be preserved if within limit');
        }
        
        // Clean up test files
        array_map('unlink', glob($test_dir . '*.json'));
        rmdir($test_dir);
        
        echo "\n";
    }
    
}

// Run the tests
$tester = new GoalieTronSecurityTester();
$success = $tester->run();
exit($success ? 0 : 1);
