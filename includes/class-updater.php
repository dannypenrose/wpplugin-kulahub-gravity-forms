<?php
/**
 * GitHub Updater Class
 */
class KulaHub_GF_Updater {
    private $file;
    private $plugin;
    private $basename;
    private $active;
    private $github_response;
    private $github_url = 'https://github.com/dannypenrose/wpplugin-kulahub-gravity-forms';
    private $github_username = 'dannypenrose';
    private $github_repo = 'wpplugin-kulahub-gravity-forms';

    public function __construct($file) {
        $this->file = $file;
        
        // Include plugin.php to get access to is_plugin_active()
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        
        add_action('admin_init', array($this, 'set_plugin_properties'));

        $this->basename = plugin_basename($this->file);
        $this->active = is_plugin_active($this->basename);

        // Hooks for update system
        add_filter('pre_set_site_transient_update_plugins', array($this, 'modify_transient'), 10, 1);
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
    }

    public function set_plugin_properties() {
        $this->plugin = get_plugin_data($this->file);
    }

    private function get_repository_info() {
        if (is_null($this->github_response)) {
            $request_uri = sprintf('https://api.github.com/repos/%s/%s/releases', 
                $this->github_username, $this->github_repo);

            $args = array(
                'headers' => array(
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo('version'),
                    'Cache-Control' => 'no-cache'
                ),
                'timeout' => 15,
                'sslverify' => true
            );

            error_log('KulaHub GF Updater: Making request to: ' . $request_uri);

            $response = wp_remote_get($request_uri, $args);

            if (is_wp_error($response)) {
                error_log('KulaHub GF Updater Error: ' . $response->get_error_message());
                return false;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            error_log('KulaHub GF Updater: Response Code: ' . $response_code);
            error_log('KulaHub GF Updater: Response Body: ' . $body);

            if ($response_code !== 200) {
                error_log('KulaHub GF Updater: Non-200 response code received: ' . $response_code);
                return false;
            }

            $releases = json_decode($body);

            if (!is_array($releases) || empty($releases)) {
                error_log('KulaHub GF Updater: No releases found or invalid response format');
                return false;
            }

            // Get the latest release
            $this->github_response = $releases[0];
            
            error_log('KulaHub GF Updater: Successfully retrieved release data');
            error_log('KulaHub GF Updater: Tag name: ' . $this->github_response->tag_name);
        }
        
        return true;
    }

    public function modify_transient($transient) {
        if (!property_exists($transient, 'checked')) {
            return $transient;
        }

        if (!($checked = $transient->checked)) {
            return $transient;
        }

        error_log('KulaHub GF Updater: Checking for updates...');
        error_log('KulaHub GF Updater: Current versions: ' . wp_json_encode($checked));

        // Get repository info and handle failure
        if (!$this->get_repository_info()) {
            error_log('KulaHub GF Updater: Failed to get repository info');
            return $transient;
        }

        if (!$this->github_response) {
            error_log('KulaHub GF Updater: No valid GitHub response available');
            return $transient;
        }

        // Ensure we have the required properties
        if (!isset($this->github_response->tag_name) || !isset($this->github_response->zipball_url)) {
            error_log('KulaHub GF Updater: Missing required properties in GitHub response');
            error_log('KulaHub GF Updater: Available properties: ' . print_r(get_object_vars($this->github_response), true));
            return $transient;
        }

        // Clean up version strings
        $current_version = isset($checked[$this->basename]) ? $checked[$this->basename] : '0.0.0';
        $latest_version = str_replace(
            array('v', 'V', 'version', 'Version', ' '), 
            '', 
            $this->github_response->tag_name
        );

        error_log('KulaHub GF Updater: Comparing versions:');
        error_log('KulaHub GF Updater: Current version: ' . $current_version);
        error_log('KulaHub GF Updater: Latest version: ' . $latest_version);

        // Ensure we have valid version strings
        if (empty($latest_version)) {
            error_log('KulaHub GF Updater: Invalid latest version string');
            return $transient;
        }

        $out_of_date = version_compare($latest_version, $current_version, 'gt');
        error_log('KulaHub GF Updater: Update needed? ' . ($out_of_date ? 'Yes' : 'No'));

        if ($out_of_date) {
            error_log('KulaHub GF Updater: Building update object...');
            
            $plugin = array(
                'url' => $this->github_url,
                'slug' => current(explode('/', $this->basename)),
                'package' => $this->github_response->zipball_url,
                'new_version' => $latest_version,
                'tested' => '6.4.3',
                'requires' => '5.0',
                'requires_php' => '7.4'
            );

            error_log('KulaHub GF Updater: Update object created: ' . wp_json_encode($plugin));
            $transient->response[$this->basename] = (object) $plugin;
        }

        return $transient;
    }

    public function plugin_popup($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if (!empty($args->slug)) {
            if ($args->slug == current(explode('/', $this->basename))) {
                $this->get_repository_info();

                $plugin = array(
                    'name'              => $this->plugin["Name"],
                    'slug'              => $this->basename,
                    'version'           => $this->github_response->tag_name,
                    'author'            => $this->plugin["AuthorName"],
                    'author_profile'    => $this->plugin["AuthorURI"],
                    'last_updated'      => $this->github_response->published_at,
                    'homepage'          => $this->plugin["PluginURI"],
                    'short_description' => $this->plugin["Description"],
                    'sections'          => array(
                        'Description'   => $this->plugin["Description"],
                        'Updates'       => $this->github_response->body,
                    ),
                    'download_link'     => $this->github_response->zipball_url
                );

                return (object) $plugin;
            }
        }

        return $result;
    }

    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;

        $install_directory = plugin_dir_path($this->file);
        $wp_filesystem->move($result['destination'], $install_directory);
        $result['destination'] = $install_directory;

        if ($this->active) {
            activate_plugin($this->basename);
        }

        return $result;
    }
} 