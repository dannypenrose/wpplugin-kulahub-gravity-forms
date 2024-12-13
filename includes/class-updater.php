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
    private $github_url = 'https://github.com/dannypenrose/kulahub-gravity-forms';
    private $github_username = 'dannypenrose';
    private $github_repo = 'kulahub-gravity-forms';

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
            $request_uri = sprintf('https://api.github.com/repos/%s/%s/releases/latest', 
                $this->github_username, $this->github_repo);

            $response = wp_remote_get($request_uri);

            if (is_wp_error($response)) {
                error_log('GitHub API Error: ' . $response->get_error_message());
                return false;
            }

            $body = wp_remote_retrieve_body($response);
            error_log('GitHub API Response: ' . $body);

            $response = json_decode($body);

            if ($response) {
                $this->github_response = $response;
            }
        }
    }

    public function modify_transient($transient) {
        if (property_exists($transient, 'checked')) {
            if ($checked = $transient->checked) {
                $this->get_repository_info();

                if (!$this->github_response) {
                    return $transient;
                }

                $out_of_date = version_compare(
                    $this->github_response->tag_name,
                    $checked[$this->basename],
                    'gt'
                );

                if ($out_of_date) {
                    $plugin = array(
                        'url' => $this->github_url,
                        'slug' => current(explode('/', $this->basename)),
                        'package' => $this->github_response->zipball_url,
                        'new_version' => $this->github_response->tag_name
                    );

                    $transient->response[$this->basename] = (object) $plugin;
                }
            }
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