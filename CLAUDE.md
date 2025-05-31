# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

GoalieTron is a WordPress widget plugin that displays Patreon pledge goals and progress. It fetches data from Patreon's public API and displays it with customizable themes and animations.

## Architecture

### Plugin Structure
- `goalietron.php` - Main plugin file containing the `GoalieTron` class with all server-side logic
- `_inc/` - Frontend assets (JavaScript and CSS files)
- `views/` - HTML templates using `{variable}` placeholders for rendering
- No build system - all assets are pre-built and ready to use

### Key Patterns
1. **WordPress Widget API** - Plugin registers as a sidebar widget
2. **Template System** - HTML templates with string replacement (`{variable_name}` → actual values)
3. **Caching** - 60-second cache for Patreon API responses stored in WordPress options
4. **Progressive Enhancement** - jQuery animations enhance basic HTML/CSS display

### Data Flow
**Legacy Mode (Patreon API v1):**
1. PHP fetches JSON from `https://api.patreon.com/user/{id}` (deprecated, requires auth)
2. Raw JSON is cached and passed to frontend
3. JavaScript (`goalietron.js`) parses JSON and updates DOM
4. Progress bar animations trigger on page load

**Custom Goals Mode (Public Data):**
1. PHP scrapes public data from `https://patreon.com/{username}/about`
2. Custom goals are managed via CLI and stored in `patreon-goals.json`
3. Goal progress is calculated using public patron/member/post counts
4. Data is transformed to match legacy API format for frontend compatibility

## Development Notes

### Working with Templates
Templates in `/views/` use simple string replacement. Variables available:
- Widget config: `{title}`, `{toptext}`, `{bottomtext}`, `{patreonid}`
- Design options: `{goalcolor}`, `{hasstripes}`
- Patreon data: Passed as raw JSON to JavaScript

### CSS Themes
Each design has its own CSS file (`goalietron_{theme}.css`). Themes include:
- default, fancy, minimal, streamlined, reversed, swapped

### WordPress Options
All plugin options are prefixed with `goalietron_`. The widget stores its configuration as a serialized array in the database.

### API Integration
**Legacy Mode:** Direct HTTP calls to Patreon's API v1 (deprecated, requires authentication).
**Custom Goals Mode:** Scrapes public data from Patreon about pages - no authentication required. If scraping fails, uses cached data if available.

## Refactored Components

### PatreonClient Class
The Patreon functionality has been extracted into a standalone `PatreonClient.php` class that can be used independently:

**Legacy API Methods:**
- `getUserData($userId, $useCache)` - Returns decoded JSON data as array
- `getUserDataRaw($userId, $useCache)` - Returns raw JSON string 
- `getUserIdFromUsername($username)` - Converts username to user ID
- `setCacheTimeout($seconds)` - Configure cache duration
- `clearCache($userId)` - Clear cache for specific user or all users

**Custom Goals Methods:**
- `getPublicCampaignData($username, $useCache)` - Scrapes public data from Patreon about page
- `getCampaignDataWithGoals($username, $useCache)` - Returns campaign data with custom goals
- `createCustomGoal($id, $type, $target, $title)` - Create a new custom goal
- `removeCustomGoal($id)` - Delete a custom goal
- `getCustomGoals()` - Get all custom goals
- `calculateGoalProgress($goalData, $campaignData)` - Calculate goal completion percentage

### CLI Application
A command-line interface `patreon-cli.php` provides direct access to Patreon functionality:

**Legacy API Commands:**
```bash
php patreon-cli.php user 123456           # Get user data
php patreon-cli.php username someuser     # Convert username to ID
php patreon-cli.php goal 123456           # Get goal information
php patreon-cli.php cache clear           # Clear cache
```

**Custom Goals Commands:**
```bash
php patreon-cli.php public scishow        # Get public campaign data
php patreon-cli.php goals                 # List all custom goals
php patreon-cli.php goal-add my-goal patrons 1000 "My Goal"  # Create goal
php patreon-cli.php goal-remove my-goal   # Delete goal
php patreon-cli.php goal-list             # List goals with progress
```

The CLI supports `--format=json`, `--no-cache`, and `--timeout=N` options.

### WordPress Integration
The main plugin (`goalietron.php`) now supports two modes:

**Legacy Mode:** Uses `patreon_userid` to fetch data from deprecated API v1
**Custom Goals Mode:** Uses `patreon_username` and `custom_goal_id` to track custom goals

Widget options include:
- `goal_mode` - Switch between "legacy" and "custom" modes
- `patreon_username` - Patreon username for public data scraping
- `custom_goal_id` - Selected custom goal ID
- All existing options (design, colors, text) work with both modes

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