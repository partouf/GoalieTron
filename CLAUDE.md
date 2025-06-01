# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

GoalieTron is a WordPress plugin that displays Patreon pledge goals and progress. It supports both classic widgets and modern Gutenberg blocks, fetching data from Patreon's public API with customizable themes and animations.

## Architecture

### Plugin Structure
- `goalietron.php` - Main plugin file containing the `GoalieTron` class with all server-side logic
- `block.json` - Gutenberg block configuration with attributes and supports
- `block-render.php` - Server-side rendering for Gutenberg blocks
- `block-editor.js` - Block editor JavaScript for Gutenberg interface
- `_inc/` - Frontend assets (JavaScript and CSS files)
- `views/` - HTML templates using `{variable}` placeholders for rendering
- `tests/` - Comprehensive test suite with WordPress mocking
- `PatreonClient.php` - Standalone class for Patreon data access
- `patreon-cli.php` - Command-line interface for Patreon functionality
- No build system - all assets are pre-built and ready to use

### Key Patterns
1. **WordPress Block API** - Plugin registers as both widget and Gutenberg block
2. **Widget Isolation** - Multiple widgets use class selectors instead of IDs to prevent conflicts
3. **Template System** - HTML templates with string replacement (`{variable_name}` → actual values)
4. **Caching** - 60-second cache for Patreon API responses stored in WordPress options
5. **Progressive Enhancement** - jQuery animations enhance basic HTML/CSS display

### Data Flow
1. PHP fetches JSON from `https://api.patreon.com/user/{id}` or `https://api.patreon.com/{username}`
2. Raw JSON is cached and passed to frontend with unique variable names per widget
3. JavaScript (`goalietron.js`) parses JSON using widget-scoped selectors
4. Progress bar animations trigger on page load for each widget independently

## Development Notes

### Multiple Widget Support
Each widget instance operates independently:
- HTML uses class selectors (`.goalietron_*`) instead of IDs
- JavaScript uses container-scoped queries (`widgetContainer.querySelector('.goalietron_*')`)
- Unique PatreonData variables per widget (`PatreonData_widget_123`)
- CSS files use class selectors for proper styling isolation

### Working with Templates
Templates in `/views/` use simple string replacement. Variables available:
- Widget config: `{title}`, `{toptext}`, `{bottomtext}`, `{patreonid}`, `{patreonusername}`
- Design options: `{goalcolor}`, `{hasstripes}`
- Goal modes: `{goal_mode}` (legacy/custom), `{custom_goal_id}`
- Patreon data: Passed as raw JSON to JavaScript with unique variable names

### CSS Themes & Block Support
Each design has its own CSS file (`goalietron_{theme}.css`). Themes include:
- default, fancy, minimal, streamlined, reversed, swapped
- All CSS uses class selectors for multi-widget compatibility
- Blocks support WordPress editor's "Additional CSS class(es)" feature via `customClassName`

### Goal Modes
- **Legacy Mode**: Uses Patreon's built-in goal system (deprecated by Patreon)
- **Custom Mode**: Uses custom goals created through Patreon's creator tools
- Block render defaults to 'custom' mode; widget defaults to 'legacy' for backward compatibility

### WordPress Options
All plugin options are prefixed with `goalietron_`. The widget stores its configuration as a serialized array in the database.

### API Integration
The plugin makes direct HTTP calls to Patreon's public API. No authentication required. If the API fails, it uses cached data if available.

## Refactored Components

### PatreonClient Class
The Patreon functionality has been extracted into a standalone `PatreonClient.php` class that can be used independently:
- `getUserData($userId, $useCache)` - Returns decoded JSON data as array
- `getUserDataRaw($userId, $useCache)` - Returns raw JSON string 
- `getUserIdFromUsername($username)` - Converts username to user ID
- `setCacheTimeout($seconds)` - Configure cache duration
- `clearCache($userId)` - Clear cache for specific user or all users

### CLI Application
A command-line interface `patreon-cli.php` provides direct access to Patreon functionality:
```bash
php patreon-cli.php user 123456           # Get user data
php patreon-cli.php username someuser     # Convert username to ID
php patreon-cli.php goal 123456           # Get goal information
php patreon-cli.php cache clear           # Clear cache
```

The CLI supports `--format=json`, `--no-cache`, and `--timeout=N` options.

## Testing

### Test Suite
Comprehensive test coverage with 44+ tests including:
- Block rendering and widget functionality
- CSS class handling and WordPress editor integration
- Multiple widget isolation
- Goal mode handling (legacy/custom)
- Button display functionality
- Mock WordPress environment for isolated testing

### Running Tests
```bash
make test                    # Run all tests (recommended)
make syntax-check           # PHP syntax validation only
```

### Debug Functions
JavaScript debug helpers available in browser console:
- `GoalieTronDebug.listWidgets()` - List all widgets on page
- `GoalieTronDebug.inspectWidget(n)` - Inspect specific widget
- `GoalieTronDebug.reprocessWidget(n)` - Reprocess widget data

## WordPress Block Features

### Block Configuration
- Registered as `goalietron/goalietron-block` in "widgets" category
- Supports alignment, spacing, and custom CSS classes
- Server-side rendering with `block-render.php`
- Editor interface with `block-editor.js`

### Block Attributes
- `patreon_username` / `patreon_userid` - Patreon account identification
- `goal_mode` - legacy/custom goal mode selection
- `custom_goal_id` - Custom goal identifier
- `design` - Theme selection (default, fancy, minimal, etc.)
- `metercolor` - Progress bar color
- `showgoaltext` / `showbutton` - Display options
- `title`, `toptext`, `bottomtext` - Custom text content

### CSS Class Support
Blocks properly handle WordPress editor's "Additional CSS class(es)" feature:
- `block.json` includes `"customClassName": true`
- Render callback uses `get_block_wrapper_attributes()`
- Custom classes are applied alongside default classes
- Multiple custom classes are supported