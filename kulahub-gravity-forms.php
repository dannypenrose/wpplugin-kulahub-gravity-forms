<?php
/**
 * Plugin Name: KulaHub Integration for Gravity Forms
 * Plugin URI: https://github.com/dannypenrose/wpplugin-kulahub-gravity-forms
 * Description: Integrates Gravity Forms with KulaHub CRM
 * Version: 1.1.2
 * Author: Danny Penrose
 * Author URI: https://kulahub.com
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kulahub-gf
 * Domain Path: /languages
 * GitHub Plugin URI: https://github.com/dannypenrose/wpplugin-kulahub-gravity-forms
 * Primary Branch: main
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Requires Plugins: gravityforms
 * Network: true
 */

// Load plugin textdomain
add_action('plugins_loaded', 'kulahub_gf_load_textdomain');
function kulahub_gf_load_textdomain() {
    load_plugin_textdomain('kulahub-gf', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Initialize the updater
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'includes/class-updater.php';
    new KulaHub_GF_Updater(__FILE__);
}

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Plugin version.
define('KULAHUB_GF_VERSION', '1.1.2');
define('KULAHUB_GF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KULAHUB_GF_PLUGIN_URL', plugin_dir_url(__FILE__));

// Require the GitHub Updater plugin
require_once KULAHUB_GF_PLUGIN_DIR . 'includes/class-admin.php';
require_once KULAHUB_GF_PLUGIN_DIR . 'includes/class-gravity-forms.php';
require_once KULAHUB_GF_PLUGIN_DIR . 'includes/class-api.php';
require_once KULAHUB_GF_PLUGIN_DIR . 'includes/class-updater.php';
require_once KULAHUB_GF_PLUGIN_DIR . 'includes/class-failed-submissions.php';

/**
 * Initialize the plugin
 */
function kulahub_gf_init() {
    // Initialize admin
    new KulaHub_GF_Admin();
    
    // Initialize Gravity Forms integration
    new KulaHub_GF_Integration();
    
    // Initialize API
    new KulaHub_GF_API();

    // Initialize Failed Submissions
    new KulaHub_GF_Failed_Submissions();
}
add_action('plugins_loaded', 'kulahub_gf_init');

/**
 * Activation hook
 */
function kulahub_gf_activate() {
    // Create logs directory
    $logs_dir = KULAHUB_GF_PLUGIN_DIR . 'logs';
    if (!file_exists($logs_dir)) {
        wp_mkdir_p($logs_dir);
        // Create .htaccess to protect logs
        $htaccess_content = "Order deny,allow\nDeny from all";
        file_put_contents($logs_dir . '/.htaccess', $htaccess_content);
        // Create index.php to prevent directory listing
        file_put_contents($logs_dir . '/index.php', '<?php // Silence is golden');
    }

    // Create failed submissions table
    $failed_submissions = new KulaHub_GF_Failed_Submissions();
    $failed_submissions->create_table();

    // Add a version option for future updates
    add_option('kulahub_gf_version', KULAHUB_GF_VERSION);
}
register_activation_hook(__FILE__, 'kulahub_gf_activate');

/**
 * Deactivation hook
 */
function kulahub_gf_deactivate() {
    // Deactivation tasks
}
register_deactivation_hook(__FILE__, 'kulahub_gf_deactivate');

// Add this check
function kulahub_gf_check_gravity_forms() {
    if (!class_exists('GFCommon')) {
        add_action('admin_notices', function() {
            ?>
            <div class="error notice">
                <p><?php _e('KulaHub Integration requires Gravity Forms to be installed and activated.', 'kulahub-gf'); ?></p>
            </div>
            <?php
        });
        return false;
    }
    return true;
}
add_action('admin_init', 'kulahub_gf_check_gravity_forms');

function kulahub_gf_privacy_policy_content() {
    if (!function_exists('wp_add_privacy_policy_content')) {
        return;
    }

    $content = sprintf(
        __('When you submit a form, this plugin sends the following data to KulaHub CRM: %s', 'kulahub-gf'),
        implode(', ', array(
            __('name', 'kulahub-gf'),
            __('email', 'kulahub-gf'),
            __('form responses', 'kulahub-gf')
        ))
    );

    wp_add_privacy_policy_content(
        'KulaHub Integration for Gravity Forms',
        wp_kses_post(wpautop($content))
    );
}
add_action('admin_init', 'kulahub_gf_privacy_policy_content');

function kulahub_gf_security_headers() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
}
add_action('send_headers', 'kulahub_gf_security_headers');

function kulahub_gf_check_requirements() {
    $api_key = get_option('kulahub_api_key');
    
    if (empty($api_key)) {
        add_action('admin_notices', function() {
            ?>
            <div class="error notice">
                <p><?php _e('KulaHub Integration requires an API key to be configured. Please go to Settings > KulaHub to set up your API key.', 'kulahub-gf'); ?></p>
            </div>
            <?php
        });
    }
}
add_action('admin_init', 'kulahub_gf_check_requirements'); 