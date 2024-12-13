=== KulaHub Integration for Gravity Forms ===
Donate link: https://kulahub.com/donate
Contributors: dannypenrose
Tags: gravity forms, kulahub, crm, integration, forms, automation
Requires at least: 5.0
Tested up to: 6.4.3
Requires PHP: 7.4
Stable tag: 1.1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrate Gravity Forms with KulaHub CRM to automatically send form submissions to your KulaHub account.

== Description ==

This plugin provides seamless integration between [Gravity Forms](https://www.gravityforms.com/) and [KulaHub CRM](https://kulahub.com).

Key Features:

= Form Integration =
* Map any Gravity Forms field to KulaHub fields
* Support for all standard field types
* Custom field mapping capabilities
* Automatic handling of complex field types
* Form-specific KulaHub configuration

= Data Management =
* Failed submission tracking and retry system
* Rate limiting protection
* Comprehensive error logging
* Data sanitization and validation
* GDPR compliance tools

= Security =
* API key encryption
* Security headers
* Protected log files
* XSS protection
* Input sanitization

= Administration =
* User-friendly settings interface
* Connection testing tool
* Failed submissions management
* Detailed error logging
* Translation ready

= Developer Features =
* Extensive hook system
* Unit testing suite
* Developer documentation
* Custom field mapping API
* Rate limiting controls

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/kulahub-gravity-forms`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure the KulaHub API key under Settings > KulaHub
4. Configure your Gravity Forms with KulaHub field IDs

== Requirements ==

* WordPress 5.0 or higher
* [Gravity Forms](https://www.gravityforms.com/) 2.4 or higher
* PHP 7.4 or higher
* Active [KulaHub](https://kulahub.com) account

== Frequently Asked Questions ==

= Do I need a KulaHub account? =

Yes, you need an active KulaHub account and API key. Visit [KulaHub.com](https://kulahub.com) to sign up.

= Is Gravity Forms required? =

Yes, this plugin requires [Gravity Forms](https://www.gravityforms.com/) 2.4 or higher.

= How do I map fields? =

1. Edit your Gravity Form
2. Click on a field to edit it
3. Look for the "KulaHub Field ID" setting
4. Enter the corresponding KulaHub field ID

= What happens if a submission fails? =

Failed submissions are:
1. Logged in the error log
2. Stored in a dedicated database table
3. Available for retry in the admin interface
4. Automatically retried based on your settings

== Screenshots ==

1. Settings page with API configuration
2. Gravity Forms field mapping interface
3. Failed submissions management
4. Error logs viewer
5. Form-specific KulaHub settings

== Changelog ==

= 1.1.0 =
* Added comprehensive unit testing suite
* Implemented GDPR compliance tools
* Added rate limiting (30 requests/minute)
* Improved error logging system
* Added privacy policy content
* Added security headers
* Added translation support
* Added developer documentation
* Added data sanitization
* Added failed submissions system

= 1.0.1 =
* Added automatic updates
* Improved error handling
* Added API key encryption

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.1.0 =
Major update with GDPR compliance, security improvements, and better error handling.

= 1.0.1 =
Security and stability improvements.

== Privacy ==

This plugin:
* Sends form data to KulaHub CRM
* Stores failed submissions locally
* Provides GDPR compliance tools
* Includes privacy policy content

For KulaHub's privacy policy, visit [KulaHub.com/privacy](https://kulahub.com/privacy)

== Third-party Services ==

This plugin connects to:
* [KulaHub CRM](https://kulahub.com) - Form submission processing
* [Gravity Forms](https://www.gravityforms.com/) - Form creation and management