# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

GoalieTron is a WordPress block plugin that displays Patreon pledge goals and progress. It fetches data from Patreon's public API and displays it with customizable themes and animations.

## Architecture

### Plugin Structure
- `goalietron.php` - Main plugin file containing the `GoalieTron` class with all server-side logic
- `PatreonClient.php` - Standalone Patreon API client with offline mode for testing
- `patreon-cli.php` - Command-line interface for Patreon data (excluded from plugin package)
- `block.json` - Block metadata and configuration
- `block-render.php` - Block server-side rendering callback
- `block-editor.js` - Block editor React components
- `_inc/` - Frontend assets (JavaScript and CSS files)
- `views/` - HTML templates using `{variable}` placeholders for rendering
- `tests/` - Comprehensive test suite with offline mode
- `patreon-goals.json` - Custom goals configuration (optional)
- No build system - all assets are pre-built and ready to use

### Key Patterns
1. **WordPress Block API** - Plugin registers as a Gutenberg block only (widgets deprecated)
2. **Template System** - HTML templates with string replacement (`{variable_name}` → actual values)
3. **Caching** - 60-second cache for Patreon API responses stored in WordPress options
4. **Progressive Enhancement** - JavaScript animations enhance basic HTML/CSS display
5. **Offline Mode** - Built-in mocking for fast, network-free testing

### Data Flow
1. PHP fetches data from Patreon's public about pages (scraping approach, no API keys needed)
2. Raw JSON is cached and passed to frontend
3. JavaScript (`goalietron.js`) parses JSON and updates DOM
4. Progress bar animations trigger on page load
5. Server-side preview data calculated for WordPress editor

## Development Notes

### Working with Templates
Templates in `/views/` use simple string replacement. Variables available:
- Widget config: `{title}`, `{toptext}`, `{bottomtext}`, `{patreon_username}`
- Design options: `{metercolor}`, `{design}`
- Goal modes: `{goal_mode}` (custom), `{custom_goal_id}`
- Patreon data: Passed as raw JSON to JavaScript

### CSS Themes
Each design has its own CSS file (`goalietron_{theme}.css`). Themes include:
- default, fancy, minimal, streamlined, reversed, swapped

### WordPress Options
All plugin options are prefixed with `goalietron_`. The widget stores its configuration as a serialized array in the database.

### API Integration
The plugin makes direct HTTP calls to Patreon's public pages to scrape campaign data. No authentication required. If the scraping fails, it uses cached data if available.

## Refactored Components

### PatreonClient Class
The Patreon functionality has been extracted into a standalone `PatreonClient.php` class that can be used independently:
- `getPublicCampaignData($username, $useCache)` - Returns campaign data from public pages
- `getCampaignDataWithGoals($username, $useCache)` - Returns campaign data with custom goals
- `createCustomGoal($goalId, $type, $target, $title)` - Create custom goals
- `setCacheTimeout($seconds)` - Configure cache duration
- `setOfflineMode($enabled)` - Enable/disable offline mode for testing
- `isOfflineMode()` - Check if offline mode is enabled

### CLI Application
A command-line interface `patreon-cli.php` provides direct access to Patreon functionality:
```bash
php patreon-cli.php public <username>      # Get public campaign data
php patreon-cli.php goals <username>       # Get campaign data with goals
php patreon-cli.php goal-list              # List custom goals
```

The CLI supports `--format=json`, `--no-cache`, `--timeout=N`, and `--offline` options.

## Testing

### Test Suite
Comprehensive test coverage with 49+ tests including:
- Basic block rendering and functionality
- Server-side rendering for WordPress editor
- Security and XSS prevention
- CSS class handling
- HTML output validation
- Mock WordPress environment for isolated testing
- Offline mode for fast, network-free testing

### Running Tests
```bash
make test                    # Run all tests (recommended)
make test-basic             # Basic functionality tests
make test-serverside        # Server-side rendering tests
make test-security          # Security tests
make test-css               # CSS class tests
make syntax-check           # PHP syntax validation only
```

Tests run in offline mode by default, completing in under 30ms without making network calls.

## WordPress Block Features

### Block Configuration
- Registered as `goalietron/goalietron-block` in "widgets" category
- Supports alignment, spacing, and custom CSS classes
- Server-side rendering with immediate preview data
- Editor interface with React components

### Block Attributes
- `patreon_username` - Patreon username
- `goal_mode` - Always "custom" (legacy mode removed)
- `custom_goal_id` - Custom goal identifier
- `design` - Theme selection (default, fancy, minimal, etc.)
- `metercolor` - Progress bar color
- `showgoaltext` / `showbutton` - Display options
- `title`, `toptext`, `bottomtext` - Custom text content

### Default Values
- `goal_mode`: "custom"
- `toptext`: "Support our work!"
- `bottomtext`: "Every supporter counts!"

### CSS Class Support
Blocks properly handle WordPress editor's "Additional CSS class(es)" feature:
- `block.json` includes `"customClassName": true`
- Render callback uses `get_block_wrapper_attributes()`
- Custom classes are applied alongside default classes

## Security & Compliance

### WordPress.org Compliance
- Plugin headers include License and Text Domain
- All output is properly escaped with `esc_html()` and `esc_attr()`
- `error_log()` calls are wrapped in `WP_DEBUG` checks
- No deprecated WordPress functions used
- Follows WordPress coding standards

### Security Features
- Input sanitization with `sanitize_text_field()`
- XSS prevention with proper escaping
- SQL injection prevention (no direct database queries)
- File path validation for security

## Packaging & Distribution

### Plugin Package
- Uses `.distignore` to exclude development files
- Excludes tests, CLI tools, and development files from distribution
- Package created with `make package`
- GitHub Actions automatically create releases with plugin zip

### Excluded from Package
- `tests/` directory
- `patreon-cli.php` (standalone CLI tool)
- Development files (Makefile, .github/, etc.)
- Documentation files not needed for WordPress.org

## GitHub Actions

### Workflows
- `test-and-package.yml` - Run tests and create packages
- `plugin-check.yml` - WordPress.org plugin compliance check
- `release.yml` - Automatic release creation on tags

### Plugin Check
WordPress Plugin Check action runs automatically to ensure compliance with WordPress.org standards.