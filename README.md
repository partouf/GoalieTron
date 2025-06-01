# GoalieTron

[![CI](https://github.com/partouf/GoalieTron/actions/workflows/ci.yml/badge.svg)](https://github.com/partouf/GoalieTron/actions/workflows/ci.yml)
[![Test and Package](https://github.com/partouf/GoalieTron/actions/workflows/test-and-package.yml/badge.svg)](https://github.com/partouf/GoalieTron/actions/workflows/test-and-package.yml)
[![PHP Compatibility](https://img.shields.io/badge/PHP-7.4%20|%208.1-blue.svg)](https://www.php.net/)
[![WordPress](https://img.shields.io/badge/WordPress-4.7.2%2B-blue.svg)](https://wordpress.org/)
[![License](https://img.shields.io/badge/License-GPLv2-green.svg)](LICENSE)

A WordPress block plugin that displays Patreon pledge goals and progress.

## Features

- 📊 Display Patreon campaign goals and current progress
- 🎯 Support for custom goals (patrons, members, posts, income)
- 🎨 Multiple design themes (default, fancy, minimal, streamlined, reversed, swapped)
- 🧩 WordPress block support with server-side rendering for instant previews
- ⚡ No jQuery dependency - pure vanilla JavaScript
- 🔄 Automatic caching to reduce API calls
- 🚀 PHP 7.4+ and PHP 8.x compatible
- 🔧 Command-line interface for testing and debugging
- 🧪 Comprehensive test suite with offline mode for fast testing
- 🌐 Public API scraping (no OAuth required)

## Installation

1. Download the latest release from the [Releases page](https://github.com/partouf/GoalieTron/releases)
2. Upload to your WordPress plugins directory
3. Activate the plugin
4. Add the GoalieTron block to your site

## Usage

1. Edit any page, post, or widget area
2. Add the "GoalieTron" block from the block inserter
3. Configure your Patreon username and goal settings in the block inspector
4. Customize the appearance with different themes and colors

## Command Line Interface

The plugin includes a CLI tool for testing and debugging:

```bash
# Get public campaign data
php patreon-cli.php public <username>

# Get campaign data with goal progress
php patreon-cli.php goals <username>

# List custom goals
php patreon-cli.php goal-list

# Options
--format=json        # Output as JSON
--no-cache          # Skip cache
--timeout=5         # Set timeout in seconds
--offline           # Use offline mode (no network calls)
```

## Development

### Requirements
- PHP 7.4 or higher
- WordPress 4.7.2 or higher

### Testing
```bash
# Run all tests
make test

# Run specific test suites
php tests/test-runner.php              # Basic functionality tests
php tests/test-serverside-rendering.php # Server-side rendering tests
php tests/test-security.php            # Security tests
php tests/test-css-classes.php         # CSS class handling tests

# Run syntax check only
make syntax-check

# Create plugin package
make package
```

The test suite runs in offline mode by default, completing in under 30ms without making any network calls.

### Project Structure
```
goalietron/
├── goalietron.php          # Main plugin file with GoalieTron class
├── PatreonClient.php       # Standalone Patreon API client with offline mode
├── patreon-cli.php         # Command-line interface for Patreon data
├── block.json              # Block metadata and configuration
├── block-render.php        # Block server-side rendering callback
├── block-editor.js         # Block editor React components
├── patreon-goals.json      # Custom goals configuration (optional)
├── _inc/                   # Frontend assets
│   ├── goalietron.js       # Main JavaScript for progress animations
│   └── goalietron_*.css    # Theme stylesheets (default, fancy, minimal, etc.)
├── views/                  # HTML templates with {variable} placeholders
│   ├── design_*.html       # Theme-specific templates
│   └── button.html         # Patreon button template
└── tests/                  # Comprehensive test suite
    ├── test-runner.php     # Main test runner
    ├── test-serverside-rendering.php  # Server-side rendering tests
    ├── test-security.php   # Security and sanitization tests
    ├── test-css-classes.php # CSS class handling tests
    ├── test-html-output.php # HTML output validation
    ├── GoalieTronTestBase.php # Base test class with assertions
    ├── mock-wordpress.php  # WordPress function mocks for testing
    └── example-offline-mode.php # Example of offline mode usage
```

### Architecture Highlights

- **No Build Process**: All assets are pre-built and ready to use
- **Template System**: Simple `{variable}` string replacement in HTML templates
- **Caching**: 60-second cache for Patreon API responses
- **Offline Mode**: Built-in mocking for fast, network-free testing
- **Custom Goals**: Support for defining custom goals via JSON configuration
- **Progressive Enhancement**: JavaScript enhances static HTML/CSS display

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Run tests (`make test`)
4. Commit your changes (`git commit -m 'Add amazing feature'`)
5. Push to the branch (`git push origin feature/amazing-feature`)
6. Open a Pull Request

## License

This project is licensed under the GPLv2 License - see the [LICENSE](LICENSE) file for details.

## Support

- 🐛 [Report bugs](https://github.com/partouf/GoalieTron/issues)
- 💡 [Request features](https://github.com/partouf/GoalieTron/issues)
