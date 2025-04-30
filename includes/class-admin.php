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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
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
            'kulahub_api_keys',
            array(
                'sanitize_callback' => array($this, 'sanitize_api_keys'),
                'default' => array()
            )
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_kulahub-settings' !== $hook) {
            return;
        }

        wp_enqueue_script(
            'kulahub-admin-js',
            KULAHUB_GF_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            KULAHUB_GF_VERSION,
            true
        );
        
        wp_enqueue_style(
            'kulahub-admin-css',
            KULAHUB_GF_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            KULAHUB_GF_VERSION
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

        // Handle test connection
        if (isset($_POST['test_connection']) && check_admin_referer('kulahub_test_connection')) {
            $api_key_id = isset($_POST['test_connection_key_id']) ? sanitize_text_field($_POST['test_connection_key_id']) : '';
            $api_keys = get_option('kulahub_api_keys', array());
            $api_key = isset($api_keys[$api_key_id]['key']) ? $api_keys[$api_key_id]['key'] : '';
            
            if (!empty($api_key)) {
                $api = new KulaHub_GF_API();
                $test_result = $api->test_connection($api_key);
                
                if (is_wp_error($test_result)) {
                    add_settings_error(
                        'kulahub_settings',
                        'connection_test',
                        $test_result->get_error_message(),
                        'error'
                    );
                } else {
                    add_settings_error(
                        'kulahub_settings',
                        'connection_test',
                        sprintf(__('Connection test successful for "%s"', 'kulahub-gf'), $api_keys[$api_key_id]['name']),
                        'success'
                    );
                }
            } else {
                add_settings_error(
                    'kulahub_settings',
                    'connection_test',
                    __('API key not found', 'kulahub-gf'),
                    'error'
                );
            }
        }

        // Show any settings errors
        settings_errors('kulahub_settings');
        
        // Get stored API keys
        $api_keys = get_option('kulahub_api_keys', array());
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <!-- API Keys Settings Form -->
            <form action="options.php" method="post">
                <?php
                settings_fields('kulahub_settings');
                do_settings_sections('kulahub_settings');
                ?>
                <h2><?php _e('KulaHub API Keys', 'kulahub-gf'); ?></h2>
                <p><?php _e('Add and manage your KulaHub API keys. Each key can be used with different Gravity Forms.', 'kulahub-gf'); ?></p>
                
                <table class="form-table" id="kulahub-api-keys-table">
                    <thead>
                        <tr>
                            <th><?php _e('Account Name', 'kulahub-gf'); ?></th>
                            <th><?php _e('API Key', 'kulahub-gf'); ?></th>
                            <th><?php _e('Actions', 'kulahub-gf'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($api_keys)) : ?>
                            <tr class="api-key-row">
                                <td>
                                    <input type="text" name="kulahub_api_keys[new-0][name]" value="" class="regular-text">
                                </td>
                                <td>
                                    <input type="password" name="kulahub_api_keys[new-0][key]" value="" class="regular-text">
                                </td>
                                <td>
                                    <button type="button" class="button remove-key"><?php _e('Remove', 'kulahub-gf'); ?></button>
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($api_keys as $id => $key_data) : ?>
                                <tr class="api-key-row">
                                    <td>
                                        <input type="text" name="kulahub_api_keys[<?php echo esc_attr($id); ?>][name]" 
                                               value="<?php echo esc_attr($key_data['name']); ?>" class="regular-text">
                                    </td>
                                    <td>
                                        <input type="password" name="kulahub_api_keys[<?php echo esc_attr($id); ?>][key]" 
                                               value="<?php echo esc_attr($key_data['key']); ?>" class="regular-text">
                                    </td>
                                    <td>
                                        <button type="button" class="button remove-key"><?php _e('Remove', 'kulahub-gf'); ?></button>
                                        <button type="submit" name="test_connection" value="1" class="button test-connection"
                                                form="test-connection-form-<?php echo esc_attr($id); ?>">
                                            <?php _e('Test Connection', 'kulahub-gf'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <button type="button" class="button" id="add-api-key"><?php _e('Add API Key', 'kulahub-gf'); ?></button>
                
                <?php submit_button(); ?>
            </form>

            <!-- Test Connection Forms - One for each key -->
            <?php foreach ($api_keys as $id => $key_data) : ?>
                <form action="" method="post" id="test-connection-form-<?php echo esc_attr($id); ?>" style="display:none;">
                    <?php wp_nonce_field('kulahub_test_connection'); ?>
                    <input type="hidden" name="test_connection_key_id" value="<?php echo esc_attr($id); ?>">
                    <input type="hidden" name="test_connection" value="1">
                </form>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Sanitize and validate the API keys
     */
    public function sanitize_api_keys($keys) {
        $sanitized_keys = array();
        $new_key_index = 0;
        
        if (!is_array($keys)) {
            return array();
        }
        
        foreach ($keys as $id => $key_data) {
            $name = isset($key_data['name']) ? sanitize_text_field($key_data['name']) : '';
            $key = isset($key_data['key']) ? sanitize_text_field($key_data['key']) : '';
            
            // Skip empty entries
            if (empty($name) || empty($key)) {
                continue;
            }
            
            // Validate key format
            if (strlen($key) < 32) {
                add_settings_error(
                    'kulahub_api_keys',
                    'invalid_api_key',
                    sprintf(__('Invalid API key format for "%s"', 'kulahub-gf'), $name)
                );
                continue;
            }
            
            // Generate a unique ID for new keys
            if (strpos($id, 'new-') === 0) {
                $id = 'key-' . time() . '-' . $new_key_index;
                $new_key_index++;
            }
            
            $sanitized_keys[$id] = array(
                'name' => $name,
                'key' => $key
            );
        }
        
        // Migrate the old API key if it exists
        $old_api_key = get_option('kulahub_api_key');
        if (!empty($old_api_key) && empty($sanitized_keys)) {
            $sanitized_keys['key-' . time()] = array(
                'name' => __('Default Account', 'kulahub-gf'),
                'key' => $old_api_key
            );
            
            // Delete the old option after migration
            delete_option('kulahub_api_key');
        }
        
        return $sanitized_keys;
    }
} 