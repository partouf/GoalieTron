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
- 🧩 WordPress block support for modern site editors
- 📱 Responsive design
- ⚡ No jQuery dependency - pure vanilla JavaScript
- 🔄 Automatic caching to reduce API calls
- 🚀 PHP 7.4+ compatible

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

## Development

### Requirements
- PHP 7.4 or higher
- WordPress 4.7.2 or higher

### Testing
```bash
# Run all tests
make test

# Run syntax check only
make syntax-check

# Create plugin package
make package
```

### Project Structure
```
goalietron/
├── goalietron.php          # Main plugin file
├── PatreonClient.php       # Patreon API client
├── block-render.php        # Block server-side rendering
├── block-editor.js         # Block editor JavaScript
├── _inc/                   # CSS and JavaScript assets
├── views/                  # HTML templates
└── tests/                  # Test suite
```

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
- 💰 [Donate Bitcoin](https://blockchain.info/address/1BfATaWYzDQbYXk92XuvwkbsiWEzyjretX)