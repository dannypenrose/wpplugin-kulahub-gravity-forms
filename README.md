# KulaHub Integration for Gravity Forms

This WordPress plugin integrates [Gravity Forms](https://www.gravityforms.com/) with [KulaHub CRM](https://kulahub.com), allowing you to automatically send form submissions to your KulaHub account.

## Features

### Form Integration
- Map Gravity Forms fields to KulaHub fields
- Support for all standard field types
- Custom field mapping for specialized KulaHub fields
- Automatic handling of checkbox and multi-select fields
- Form-specific KulaHub form ID and client ID configuration

### Data Management
- Failed submission tracking and retry system
- Rate limiting (30 requests per minute)
- Comprehensive error logging
- Data sanitization and validation
- GDPR compliance tools for data export/erasure

### Security
- API key encryption
- Security headers implementation
- Protected log files
- XSS protection
- Input sanitization

### Administration
- User-friendly settings interface
- Connection testing tool
- Failed submissions management interface
- Detailed error logging
- Translation ready

### Developer Features
- Extensive hook system
- Comprehensive unit testing suite
- Developer documentation
- Custom field mapping API
- Rate limiting controls
- Error handling system

## Requirements

- WordPress 5.0 or higher
- [Gravity Forms](https://www.gravityforms.com/) 2.4 or higher
- PHP 7.4 or higher
- Active [KulaHub](https://kulahub.com) account with API access

## Installation

1. Download the latest release from the [Releases page](https://github.com/dannypenrose/wpplugin-kulahub-gravity-forms/releases/latest)
2. In your WordPress admin panel, go to Plugins > Add New > Upload Plugin
3. Upload the downloaded zip file
4. Activate the plugin
5. Go to Settings > KulaHub to configure your API key

## Configuration

1. Obtain your API key from your [KulaHub account](https://kulahub.com)
2. Navigate to Settings > KulaHub in your WordPress admin
3. Enter your API key and save changes
4. Edit your Gravity Forms to configure KulaHub field mappings
5. Set form-specific KulaHub form ID and client ID

## Developer Documentation

See the [developer documentation](docs/developer.md) for information about:
- Available hooks and filters
- Custom field mapping
- API integration
- Error handling
- Rate limiting
- Testing

## Support

For support, please [open an issue](https://github.com/dannypenrose/wpplugin-kulahub-gravity-forms/issues) on our GitHub repository.

## License

This plugin is licensed under the GPL v2 or later.

## Privacy

This plugin:
- Sends form submission data to KulaHub CRM
- Stores failed submissions in the WordPress database
- Provides data export and erasure tools for GDPR compliance
- Includes privacy policy content suggestions

For more information about KulaHub's privacy policy, visit [KulaHub's Privacy Policy](https://kulahub.com/privacy).