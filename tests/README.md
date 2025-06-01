# GoalieTron Test Suite

This directory contains automated tests for the GoalieTron WordPress plugin that can be run outside of WordPress.

## Running Tests

### Quick Start
```bash
# Run all tests
./test.sh

# Or use make
make test
```

### Individual Test Commands
```bash
# Run basic functionality tests
php tests/test-runner.php

# Run HTML output validation tests  
php tests/test-html-output.php

# Run with debug output
DEBUG_OUTPUT=true php tests/test-runner.php
```

### Using Make
```bash
make test         # Run all tests
make test-basic   # Run basic functionality tests
make test-html    # Run HTML output validation tests
make test-all     # Run all tests with verbose output
make clean        # Clean up test artifacts
make package      # Create plugin zip file
```

## Test Coverage

### Basic Tests (`test-runner.php`)
- Basic widget rendering
- Custom goal mode with data
- Legacy mode (empty data)
- Multiple blocks isolation
- Different design themes

### HTML Output Tests (`test-html-output.php`)
- Custom goal HTML structure validation
- Block render output verification
- Multiple blocks isolation testing
- PatreonData JSON validation
- Widget ID uniqueness

## Test Environment

The tests use a mock WordPress environment (`mock-wordpress.php`) that provides:
- Mock WordPress functions (get_option, add_option, etc.)
- Mock WP_Widget class
- Mock script/style enqueueing functions
- Mock escaping and translation functions

## Adding New Tests

1. Add test methods to the appropriate test class
2. Use the assertion helpers (assert_contains, assert_equals, etc.)
3. Run the tests to ensure they pass
4. Commit your changes

## Troubleshooting

If tests fail:
1. Check PHP syntax: `php -l goalietron.php`
2. Ensure all required files exist
3. Check error messages for specific failures
4. Run with DEBUG_OUTPUT=true for more details