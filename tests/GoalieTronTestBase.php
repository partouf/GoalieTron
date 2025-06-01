<?php
/**
 * GoalieTron Test Base Class
 * 
 * Provides shared assertion methods and utilities for all GoalieTron test classes.
 * This eliminates code duplication across test files and ensures consistent
 * test output formatting.
 */

abstract class GoalieTronTestBase {
    protected $test_count = 0;
    protected $passed_count = 0;
    protected $failed_count = 0;
    
    /**
     * Get test results summary
     * 
     * @return array Associative array with test counts
     */
    public function getTestResults() {
        return array(
            'total' => $this->test_count,
            'passed' => $this->passed_count,
            'failed' => $this->failed_count,
            'success' => $this->failed_count === 0
        );
    }
    
    /**
     * Print test summary
     * 
     * @param string $testSuiteName Name of the test suite
     */
    protected function printTestSummary($testSuiteName) {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "$testSuiteName Test Summary:\n";
        echo "Total tests: {$this->test_count}\n";
        echo "Passed: {$this->passed_count}\n";
        echo "Failed: {$this->failed_count}\n";
        echo str_repeat("=", 50) . "\n";
    }
    
    // =====================================
    // ASSERTION METHODS
    // =====================================
    
    /**
     * Assert that a haystack contains a needle
     */
    protected function assert_contains($haystack, $needle, $message) {
        $this->test_count++;
        if (strpos($haystack, $needle) !== false) {
            echo "✓ PASS: $message\n";
            $this->passed_count++;
            return true;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  Expected to find: '$needle'\n";
            $this->failed_count++;
            return false;
        }
    }
    
    /**
     * Assert that a haystack does not contain a needle
     */
    protected function assert_not_contains($haystack, $needle, $message) {
        $this->test_count++;
        if (strpos($haystack, $needle) === false) {
            echo "✓ PASS: $message\n";
            $this->passed_count++;
            return true;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  Should not contain: '$needle'\n";
            $this->failed_count++;
            return false;
        }
    }
    
    /**
     * Assert that a haystack matches a regular expression pattern
     */
    protected function assert_contains_regex($haystack, $pattern, $message) {
        $this->test_count++;
        if (preg_match($pattern, $haystack)) {
            echo "✓ PASS: $message\n";
            $this->passed_count++;
            return true;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  Expected to match pattern: $pattern\n";
            $this->failed_count++;
            return false;
        }
    }
    
    /**
     * Assert that two values are equal
     */
    protected function assert_equals($actual, $expected, $message) {
        $this->test_count++;
        if ($actual === $expected) {
            echo "✓ PASS: $message\n";
            $this->passed_count++;
            return true;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  Expected: " . var_export($expected, true) . "\n";
            echo "  Actual: " . var_export($actual, true) . "\n";
            $this->failed_count++;
            return false;
        }
    }
    
    /**
     * Assert that two values are not equal
     */
    protected function assert_not_equals($actual, $expected, $message) {
        $this->test_count++;
        if ($actual !== $expected) {
            echo "✓ PASS: $message\n";
            $this->passed_count++;
            return true;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  Expected values to be different, but both were: " . var_export($actual, true) . "\n";
            $this->failed_count++;
            return false;
        }
    }
    
    /**
     * Assert that a condition is true
     */
    protected function assert_true($condition, $message) {
        $this->test_count++;
        if ($condition) {
            echo "✓ PASS: $message\n";
            $this->passed_count++;
            return true;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  Expected: true\n";
            echo "  Actual: false\n";
            $this->failed_count++;
            return false;
        }
    }
    
    /**
     * Assert that a condition is false
     */
    protected function assert_false($condition, $message) {
        $this->test_count++;
        if (!$condition) {
            echo "✓ PASS: $message\n";
            $this->passed_count++;
            return true;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  Expected: false\n";
            echo "  Actual: true\n";
            $this->failed_count++;
            return false;
        }
    }
    
    /**
     * Assert that a value is not null
     */
    protected function assert_not_null($value, $message) {
        $this->test_count++;
        if ($value !== null) {
            echo "✓ PASS: $message\n";
            $this->passed_count++;
            return true;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  Expected: not null\n";
            echo "  Actual: null\n";
            $this->failed_count++;
            return false;
        }
    }
    
    /**
     * Assert that a value is null
     */
    protected function assert_null($value, $message) {
        $this->test_count++;
        if ($value === null) {
            echo "✓ PASS: $message\n";
            $this->passed_count++;
            return true;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  Expected: null\n";
            echo "  Actual: " . var_export($value, true) . "\n";
            $this->failed_count++;
            return false;
        }
    }
    
    /**
     * Assert that actual value is greater than expected
     */
    protected function assert_greater_than($actual, $expected, $message) {
        $this->test_count++;
        if ($actual > $expected) {
            echo "✓ PASS: $message\n";
            $this->passed_count++;
            return true;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  Expected: > $expected\n";
            echo "  Actual: $actual\n";
            $this->failed_count++;
            return false;
        }
    }
    
    /**
     * Assert that actual value is less than expected
     */
    protected function assert_less_than($actual, $expected, $message) {
        $this->test_count++;
        if ($actual < $expected) {
            echo "✓ PASS: $message\n";
            $this->passed_count++;
            return true;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  Expected: < $expected\n";
            echo "  Actual: $actual\n";
            $this->failed_count++;
            return false;
        }
    }
    
    /**
     * Assert that actual value is less than or equal to expected
     */
    protected function assert_less_than_or_equal($actual, $expected, $message) {
        $this->test_count++;
        if ($actual <= $expected) {
            echo "✓ PASS: $message\n";
            $this->passed_count++;
            return true;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  Expected: <= $expected\n";
            echo "  Actual: $actual\n";
            $this->failed_count++;
            return false;
        }
    }
    
    /**
     * Assert that actual value is greater than or equal to expected
     */
    protected function assert_greater_than_or_equal($actual, $expected, $message) {
        $this->test_count++;
        if ($actual >= $expected) {
            echo "✓ PASS: $message\n";
            $this->passed_count++;
            return true;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  Expected: >= $expected\n";
            echo "  Actual: $actual\n";
            $this->failed_count++;
            return false;
        }
    }
    
    /**
     * Assert that a file exists
     */
    protected function assert_file_exists($filepath, $message) {
        $this->test_count++;
        if (file_exists($filepath)) {
            echo "✓ PASS: $message\n";
            $this->passed_count++;
            return true;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  File not found: $filepath\n";
            $this->failed_count++;
            return false;
        }
    }
    
    /**
     * Assert that a file does not exist
     */
    protected function assert_file_not_exists($filepath, $message) {
        $this->test_count++;
        if (!file_exists($filepath)) {
            echo "✓ PASS: $message\n";
            $this->passed_count++;
            return true;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  File should not exist: $filepath\n";
            $this->failed_count++;
            return false;
        }
    }
    
    /**
     * Assert that an array has a specific key
     */
    protected function assert_array_has_key($key, $array, $message) {
        $this->test_count++;
        if (is_array($array) && array_key_exists($key, $array)) {
            echo "✓ PASS: $message\n";
            $this->passed_count++;
            return true;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  Key '$key' not found in array\n";
            $this->failed_count++;
            return false;
        }
    }
    
    /**
     * Assert that an array does not have a specific key
     */
    protected function assert_array_not_has_key($key, $array, $message) {
        $this->test_count++;
        if (!is_array($array) || !array_key_exists($key, $array)) {
            echo "✓ PASS: $message\n";
            $this->passed_count++;
            return true;
        } else {
            echo "✗ FAIL: $message\n";
            echo "  Key '$key' should not exist in array\n";
            $this->failed_count++;
            return false;
        }
    }
    
    /**
     * Fail a test with a custom message
     */
    protected function fail($message) {
        $this->test_count++;
        echo "✗ FAIL: $message\n";
        $this->failed_count++;
        return false;
    }
    
    /**
     * Pass a test with a custom message
     */
    protected function pass($message) {
        $this->test_count++;
        echo "✓ PASS: $message\n";
        $this->passed_count++;
        return true;
    }
}