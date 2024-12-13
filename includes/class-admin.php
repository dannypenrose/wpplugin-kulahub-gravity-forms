<?php
/**
 * Admin functionality
 */
class KulaHub_GF_Admin {
    /**
     * Initialize the admin
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('KulaHub Settings', 'kulahub-gf'),
            __('KulaHub', 'kulahub-gf'),
            'manage_options',
            'kulahub-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'kulahub_settings',
            'kulahub_api_key',
            array(
                'sanitize_callback' => array($this, 'sanitize_api_key'),
                'default' => ''
            )
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'kulahub-gf'));
        }

        // Add nonce for security
        wp_nonce_field('kulahub_settings_action', 'kulahub_settings_nonce');
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('kulahub_settings');
                do_settings_sections('kulahub_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="kulahub_api_key"><?php _e('API Key', 'kulahub-gf'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="kulahub_api_key" name="kulahub_api_key" 
                                   value="<?php echo esc_attr(get_option('kulahub_api_key')); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Sanitize and validate the API key
     */
    public function sanitize_api_key($key) {
        // Sanitize and validate the API key
        $key = sanitize_text_field($key);
        
        // Add your API key format validation here
        if (empty($key) || strlen($key) < 32) {
            add_settings_error(
                'kulahub_api_key',
                'invalid_api_key',
                __('Invalid API key format', 'kulahub-gf')
            );
            return get_option('kulahub_api_key'); // Return existing value
        }
        
        return $key;
    }
} 