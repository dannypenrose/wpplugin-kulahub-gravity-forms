# KulaHub Integration for Gravity Forms

This WordPress plugin integrates Gravity Forms with KulaHub CRM, allowing you to automatically send form submissions to your KulaHub account.

## Installation

1. Download the latest release from the [Releases page](https://github.com/dannypenrose/wpplugin-kulahub-gravity-forms/releases/latest)
2. In your WordPress admin panel, go to Plugins > Add New > Upload Plugin
3. Upload the downloaded zip file
4. Activate the plugin
5. Go to Settings > KulaHub to configure your API key

## Requirements

- WordPress 5.0 or higher
- Gravity Forms 2.4 or higher
- PHP 7.4 or higher

## Configuration

1. Obtain your API key from your KulaHub account
2. Navigate to Settings > KulaHub in your WordPress admin
3. Enter your API key and save changes
4. Edit your Gravity Forms to configure KulaHub field mappings

## Updates

The plugin includes automatic updates. When a new version is released, you'll see the update notification in your WordPress admin panel under Updates or Plugins.

## Support

For support, please [open an issue](https://github.com/dannypenrose/wpplugin-kulahub-gravity-forms/issues) on our GitHub repository.

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### 1.1.0
- Added comprehensive unit testing suite
- Implemented GDPR compliance with data export/erasure tools
- Added rate limiting for API calls (30 requests per minute)
- Improved error logging system
- Added privacy policy content
- Added security headers
- Added translation support
- Added developer documentation
- Added proper data sanitization and validation
- Added failed submissions retry functionality

### 1.0.1
- Added automatic updates from GitHub
- Improved error handling
- Added password protection for API key field

### 1.0.0
- Initial release