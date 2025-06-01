#!/bin/bash

# Simple test runner for GoalieTron

echo "=========================================="
echo "GoalieTron Test Suite"
echo "=========================================="
echo ""

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "Error: PHP is not installed or not in PATH"
    exit 1
fi

# Run syntax checks first
echo "1. Checking PHP syntax..."
php -l goalietron.php > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "   ✓ goalietron.php syntax OK"
else
    echo "   ✗ goalietron.php has syntax errors"
    exit 1
fi

php -l PatreonClient.php > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "   ✓ PatreonClient.php syntax OK"
else
    echo "   ✗ PatreonClient.php has syntax errors"
    exit 1
fi

echo ""
echo "2. Running basic functionality tests..."
echo "--------------------------------------"
php tests/test-runner.php
TEST1_RESULT=$?

echo ""
echo "3. Running HTML output validation..."
echo "-----------------------------------"
php tests/test-html-output.php
TEST2_RESULT=$?

echo ""
echo "=========================================="
echo "Test Results Summary"
echo "=========================================="

if [ $TEST1_RESULT -eq 0 ] && [ $TEST2_RESULT -eq 0 ]; then
    echo "✓ All tests passed!"
    exit 0
else
    echo "✗ Some tests failed"
    exit 1
fi