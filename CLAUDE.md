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
1. PHP fetches JSON from `https://api.patreon.com/user/{id}`
2. Raw JSON is cached and passed to frontend
3. JavaScript (`goalietron.js`) parses JSON and updates DOM
4. Progress bar animations trigger on page load

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