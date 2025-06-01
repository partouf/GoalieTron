# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

GoalieTron is a WordPress block plugin that displays Patreon pledge goals and progress. It uses public data scraping and custom goals for tracking progress with customizable themes and animations. Legacy Patreon API v1 functionality has been removed due to authentication requirements.

## Architecture

### Plugin Structure
- `goalietron.php` - Main plugin file containing the `GoalieTron` class with all server-side logic
- `block-render.php` - WordPress block render callback for Gutenberg editor
- `_inc/` - Frontend assets (JavaScript and CSS files)
- `views/` - HTML templates using `{variable}` placeholders for rendering
- `tests/` - Comprehensive test suite with mock WordPress environment
- `PatreonClient.php` - Standalone class for Patreon data and custom goals
- `patreon-cli.php` - Command-line interface for goal management
- `patreon-goals.json` - Custom goals configuration file
- No build system - all assets are pre-built and ready to use

### Key Patterns
1. **WordPress Block API** - Plugin registers as a Gutenberg block (legacy widget support removed)
2. **Template System** - HTML templates with string replacement (`{variable_name}` → actual values)
3. **Caching** - 60-second cache for public data scraping stored in WordPress options
4. **Vanilla JavaScript** - No jQuery dependency, pure JavaScript handles DOM updates and animations
5. **Custom Goals** - User-defined goals with progress tracking using public Patreon data

### Data Flow
**Current Implementation (Custom Goals Only):**
1. PHP scrapes public data from `https://patreon.com/{username}/about`
2. Custom goals are managed via CLI and stored in `patreon-goals.json`
3. Goal progress is calculated using public patron/member/post counts
4. Data is transformed to legacy API format for frontend compatibility
5. Vanilla JavaScript parses JSON and updates DOM with animations

**Block Rendering:**
1. WordPress calls `register_block_type()` with `block-render.php` callback
2. Block attributes passed to `GoalieTron::CreateInstance()`
3. Template variables replaced with actual values (`{patreon_username}` etc.)
4. Unique widget IDs prevent conflicts between multiple blocks

## Development Notes

### Working with Templates
Templates in `/views/` use simple string replacement. Variables available:
- Widget config: `{title}`, `{toptext}`, `{bottomtext}`, `{patreon_username}`
- Design options: `{goalcolor}`, `{hasstripes}`
- Button: `{goalietron_button}` replaced with `button.html` content when `showbutton` enabled
- Patreon data: Passed as raw JSON to JavaScript with unique variable names per widget

### CSS Themes
Each design has its own CSS file (`goalietron_{theme}.css`). Themes include:
- default, fancy, minimal, streamlined, reversed, swapped

### WordPress Options
All plugin options are prefixed with `goalietron_`. The widget stores its configuration as a serialized array in the database.

### API Integration
**Current Implementation:** Scrapes public data from Patreon about pages - no authentication required. If scraping fails, uses cached data if available. Legacy API v1 support has been completely removed.

## Refactored Components

### PatreonClient Class
The Patreon functionality has been extracted into a standalone `PatreonClient.php` class that can be used independently. Legacy API v1 methods have been removed.

**Public Data Methods:**
- `getPublicCampaignData($username, $useCache)` - Scrapes public data from Patreon about page
- `getCampaignDataWithGoals($username, $useCache)` - Returns campaign data with custom goals
- `setCacheTimeout($seconds)` - Configure cache duration
- `setFetchTimeout($seconds)` - Configure HTTP request timeout

**Custom Goals Methods:**
- `createCustomGoal($id, $type, $target, $title)` - Create a new custom goal
- `removeCustomGoal($id)` - Delete a custom goal
- `getCustomGoals()` - Get all custom goals
- `calculateGoalProgress($username, $goalId, $useCache)` - Calculate goal completion percentage
- `loadCustomGoalsFromFile($filePath)` - Load goals from JSON file
- `saveCustomGoalsToFile($filePath)` - Save goals to JSON file

### CLI Application
A command-line interface `patreon-cli.php` provides direct access to Patreon functionality. Legacy API commands have been removed.

**Available Commands:**
```bash
php patreon-cli.php public scishow        # Get public campaign data
php patreon-cli.php goals scishow         # Get campaign data with custom goal progress
php patreon-cli.php goal-add my-goal patrons 1000 "My Goal"  # Create goal
php patreon-cli.php goal-remove my-goal   # Delete goal
php patreon-cli.php goal-list             # List all custom goals
```

The CLI supports `--format=json`, `--no-cache`, and `--timeout=N` options. Goals are automatically saved to and loaded from `patreon-goals.json`.

### WordPress Integration
The main plugin (`goalietron.php`) is now block-only with custom goals support:

**Block Registration:**
- Registers as `goalietron/goalietron-block` in the "widgets" category
- Uses `block-render.php` for server-side rendering
- No longer supports legacy widget API

**Block Attributes:**
- `patreon_username` - Patreon username for public data scraping
- `custom_goal_id` - Selected custom goal ID from patreon-goals.json
- `showbutton` - Whether to display "Become a Patron!" button
- `goal_mode` - Defaults to "custom" (legacy mode removed)
- All existing options (design, colors, text) still available

### Frontend JavaScript (`goalietron.js`)
Enhanced to handle different goal types:

**Goal Types:**
- `income` - Shows currency format: "$1,500 of $3,000 per month"
- `patrons` - Shows count format: "15,119 of 20,000" 
- `members` - Shows count format: "5,152 of 10,000"
- `posts` - Shows count format: "284 of 500"

**Data Structure:**
The JavaScript expects Patreon API v1 format with additional fields:
```javascript
{
  "included": [
    {
      "type": "campaign",
      "attributes": {
        "patron_count": 15119,
        "paid_member_count": 5152,
        "creation_count": 284,
        "pledge_sum": 1511900,
        "pay_per_name": "month" // or "" for count goals
      }
    },
    {
      "type": "goal", 
      "attributes": {
        "amount_cents": 2000000,
        "goal_type": "patrons", // NEW: identifies goal type
        "title": "Reach 20,000 patrons"
      }
    }
  ]
}
```

### Test Pages System
A comprehensive test page generator (`test-page-generator.php`) creates HTML demos:

**Generated Pages:**
- `index.html` - Main test page index
- `config.html` - WordPress admin interface demo with localStorage persistence
- `custom-goal-{type}-{target}.html` - Custom goal examples
- `design-{theme}.html` - Design theme showcases
- `legacy-goal.html` - Simulated legacy API data

**Features:**
- Live data from public Patreon API
- localStorage configuration sync across all pages
- Interactive configuration interface
- Real-time preview of all themes and settings

### Custom Goals System
Goals are stored in `patreon-goals.json` with this structure:
```json
{
  "goal-id": {
    "type": "patrons|members|posts|income",
    "target": 20000,
    "title": "Reach 20,000 patrons",
    "current": 15119,
    "progress_percentage": 75.6
  }
}
```

**Supported Goal Types:**
- **patrons** - Total patron count
- **members** - Paid member count  
- **posts** - Number of posts created
- **income** - Monthly income (if visible on about page)

## Key Lessons Learned

### JavaScript Goal Type Handling
The frontend JavaScript was enhanced to distinguish between income and count-based goals:
- Count goals show numbers without currency symbols
- Income goals show dollar amounts with "per month"
- Percentage calculations use appropriate current values (patron_count vs pledge_sum)
- Goal completion logic correctly identifies when targets are reached

### Data Format Compatibility
Custom goals are transformed to match the legacy Patreon API v1 format:
- Target values stored as `amount_cents` (multiplied by 100 for compatibility)
- Current values mapped to appropriate fields (`patron_count`, `paid_member_count`, etc.)
- `goal_type` field added to distinguish between goal types
- `pay_per_name` field used to control "per X" display text

### Public Data Extraction
Patreon's public about pages contain JSON data with:
- `patron_count` - Total number of patrons
- `paid_member_count` - Number of paying members
- `creation_count` - Number of posts/creations
- Campaign name and other metadata

This data is extracted via regex parsing and cached for performance.

## Testing Framework

### Comprehensive Test Suite
The plugin includes a robust testing framework in `/tests/` directory:

**Core Test Files:**
- `test-runner.php` - Main test suite with all functional tests
- `test-html-output.php` - HTML structure validation tests
- `mock-wordpress.php` - Complete WordPress environment simulation

**Mock WordPress Environment:**
- Fully functional `add_action()` and `add_filter()` with validation
- Asset registration validation (`wp_register_script`, `wp_register_style`)
- Block registration validation (`register_block_type`)
- File existence checking for all registered assets
- Action/filter execution simulation with `do_action()` and `apply_filters()`

**Test Coverage:**
1. **Block Rendering** - Basic widget output and structure
2. **Custom Goal Mode** - Goal data loading and display
3. **Multiple Blocks** - Unique widget ID generation to prevent conflicts
4. **Design Themes** - All 6 theme variations (default, fancy, minimal, etc.)
5. **Block Categories** - WordPress block category filter functionality
6. **Button Display** - Patron button enabled/disabled states with username links
7. **HTML Structure** - Comprehensive output validation and data extraction

**Running Tests:**
```bash
make test              # Run full test suite
php tests/test-runner.php        # Run functional tests only
php tests/test-html-output.php   # Run HTML validation tests only
```

**Test Validation Features:**
- File existence checking for CSS/JS assets before registration
- WordPress hook parameter validation (empty tags, invalid priorities)
- Block registration validation (editor scripts, styles, render callbacks)
- HTML structure parsing and content verification
- JavaScript variable uniqueness across multiple widget instances

### Build System
Simple Makefile with common development tasks:

```bash
make test              # Run all tests (syntax check + test suite)
make syntax-check      # PHP syntax validation only
```

**Continuous Integration:**
- GitHub Actions workflow for automated testing
- PHP 7+ compatibility verification
- Comprehensive test coverage reporting

### Development Workflow
1. **Make Changes** - Edit plugin files as needed
2. **Run Tests** - `make test` to verify functionality
3. **Check Output** - HTML output tests validate structure
4. **Commit** - All tests must pass before committing

**Test-Driven Features:**
- Block categories filter (`goalietron_block_categories()`)
- Button display with username-based URLs
- Multiple widget isolation with unique IDs
- WordPress hook validation and execution
- Asset dependency checking