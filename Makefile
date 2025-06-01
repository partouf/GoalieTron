# GoalieTron Makefile for testing and packaging

.PHONY: test test-basic test-html test-all clean package help

# Default target
help:
	@echo "GoalieTron Test Commands:"
	@echo "  make test        - Run all tests (syntax + functionality)"
	@echo "  make syntax-check - Check PHP syntax only"
	@echo "  make test-basic  - Run basic functionality tests"
	@echo "  make test-html   - Run HTML output validation tests"
	@echo "  make clean       - Clean up test artifacts"
	@echo "  make package     - Create plugin zip file"

# Run all tests
test: syntax-check test-basic test-html

# Run basic tests
test-basic:
	@echo "Running basic tests..."
	@php tests/test-runner.php

# Run HTML output tests
test-html:
	@echo "Running HTML output tests..."
	@php tests/test-html-output.php

# Run all tests with verbose output
test-all:
	@echo "Running all tests with debug output..."
	@DEBUG_OUTPUT=true php tests/test-runner.php
	@echo ""
	@DEBUG_OUTPUT=true php tests/test-html-output.php

# Clean up any test artifacts
clean:
	@echo "Cleaning up test artifacts..."
	@rm -f tests/*.log
	@rm -f tests/*.tmp

# Create plugin package
package:
	@echo "Creating plugin package..."
	@bash package.sh
	@echo "Package created: goalietron-plugin.zip"

# Syntax check all PHP files
syntax-check:
	@echo "Checking PHP syntax..."
	@php -l goalietron.php > /dev/null && echo "  ✓ goalietron.php"
	@php -l PatreonClient.php > /dev/null && echo "  ✓ PatreonClient.php"
	@php -l block-render.php > /dev/null && echo "  ✓ block-render.php"
	@php -l patreon-cli.php > /dev/null && echo "  ✓ patreon-cli.php"
	@echo "✓ All PHP files have valid syntax"
	@echo ""

# Quick test - just verify the plugin loads without errors
quick-test: syntax-check