=== KulaHub Integration for Gravity Forms ===
Donate link: https://example.com/donate
Contributors: dannypenrose
Tags: gravity forms, kulahub, crm, integration
Requires at least: 5.0
Tested up to: 6.4.3
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrate Gravity Forms with KulaHub CRM to automatically send form submissions to your KulaHub account.

== Description ==

This plugin extends Gravity Forms to allow:
* Specifying KulaHub formId and ClientId for each form
* Setting custom field IDs for form fields
* Automatic submission of form entries to KulaHub
* API key configuration through WordPress admin

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/kulahub-gravity-forms`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure the KulaHub API key under Settings > KulaHub
4. Configure your Gravity Forms with KulaHub field IDs

== Frequently Asked Questions ==

= Do I need a KulaHub account? =

Yes, you need an active KulaHub account and API key to use this plugin.

= Is Gravity Forms required? =

Yes, this plugin requires Gravity Forms 2.4 or higher to be installed and activated.

== Screenshots ==

1. Settings page screenshot
2. Gravity Forms field mapping interface
3. Failed submissions log

== Changelog ==

= 1.1.0 =
* Added comprehensive unit testing suite
* Implemented GDPR compliance with data export/erasure tools
* Added rate limiting for API calls (30 requests per minute)
* Improved error logging system
* Added privacy policy content
* Added security headers
* Added translation support
* Added developer documentation
* Added proper data sanitization and validation
* Added failed submissions retry functionality

= 1.0.1 =
* Added automatic updates from GitHub
* Improved error handling
* Added password protection for API key field

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.1.0 =
This version adds GDPR compliance, improved security, and better error handling. Upgrade recommended for all users.

= 1.0.1 =
This version adds automatic updates and improves security. Upgrade recommended.

== Privacy ==

This plugin:
* Sends form submission data to KulaHub CRM
* Stores failed submissions in the WordPress database
* Provides data export and erasure tools for GDPR compliance

== Third-party Services ==

This plugin connects to:
* KulaHub CRM (https://kulahub.com) - Form submission data