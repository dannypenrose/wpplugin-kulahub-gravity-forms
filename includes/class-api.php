<?php
/**
 * KulaHub API Handler
 *
 * @package KulaHub_GF
 * @since 1.0.0
 */
class KulaHub_GF_API {
    /**
     * API endpoint
     *
     * @var string
     */
    private $api_endpoint = 'https://kulahub-api.azurewebsites.net/api/Forms/addFormEntry';

    /**
     * API keys
     *
     * @var array
     */
    private $api_keys;

    /**
     * Failed submissions handler
     *
     * @var KulaHub_GF_Failed_Submissions
     */
    private $failed_submissions;

    /**
     * Initialize the API handler
     */
    public function __construct() {
        $this->api_keys = get_option('kulahub_api_keys', array());
        $this->failed_submissions = new KulaHub_GF_Failed_Submissions();
    }

    /**
     * Get API key by ID
     *
     * @param string $key_id Key ID to retrieve
     * @return string|null API key or null if not found
     */
    public function get_api_key($key_id) {
        return isset($this->api_keys[$key_id]['key']) ? $this->api_keys[$key_id]['key'] : null;
    }

    /**
     * Get API key name by ID
     *
     * @param string $key_id Key ID to retrieve name for
     * @return string API key name or empty string if not found
     */
    public function get_api_key_name($key_id) {
        return isset($this->api_keys[$key_id]['name']) ? $this->api_keys[$key_id]['name'] : '';
    }

    /**
     * Get all API keys as options for select field
     *
     * @return array Array of key ID => key name pairs
     */
    public function get_api_keys_options() {
        $options = array();
        
        foreach ($this->api_keys as $id => $key_data) {
            $options[$id] = $key_data['name'];
        }
        
        return $options;
    }

    /**
     * Send data to KulaHub
     *
     * @param array  $data Form data to send
     * @param string $form_id Gravity Form ID
     * @param int    $entry_id Gravity Form entry ID
     * @param string $api_key_id API key ID to use for authentication
     * @return array|WP_Error Response array or WP_Error on failure
     */
    public function send_data($data, $form_id, $entry_id, $api_key_id = '') {
        // Debug logging
        error_log('KulaHub GF: Attempting to send data');
        error_log('KulaHub GF: API Endpoint: ' . $this->api_endpoint);
        
        // Add DNS check
        $host = parse_url($this->api_endpoint, PHP_URL_HOST);
        if (!$host) {
            error_log('KulaHub GF: Invalid API endpoint URL');
            return new WP_Error(
                'invalid_endpoint',
                __('Invalid API endpoint URL', 'kulahub-gf')
            );
        }

        // Check if DNS resolution works
        $dns_check = gethostbyname($host);
        if ($dns_check === $host) {
            error_log('KulaHub GF: DNS resolution failed for ' . $host);
            return new WP_Error(
                'dns_resolution_failed',
                sprintf(__('Could not resolve API host: %s', 'kulahub-gf'), $host)
            );
        }

        // Get the API key
        $api_key = null;
        
        if (!empty($api_key_id)) {
            $api_key = $this->get_api_key($api_key_id);
        } else {
            // Fallback to the first key if no key ID specified
            if (!empty($this->api_keys)) {
                $first_key = reset($this->api_keys);
                $api_key = $first_key['key'];
            }
        }
        
        if (empty($api_key)) {
            // Fallback to legacy API key
            $api_key = get_option('kulahub_api_key');
        }
        
        if (empty($api_key)) {
            error_log('KulaHub GF: API key is missing');
            return new WP_Error(
                'missing_api_key',
                __('KulaHub API key is not configured', 'kulahub-gf'),
                array('status' => 403)
            );
        }

        if ($this->is_rate_limited()) {
            return new WP_Error(
                'rate_limit_exceeded',
                __('Rate limit exceeded', 'kulahub-gf'),
                array('status' => 429)
            );
        }

        $response = wp_remote_post(
            $this->api_endpoint . '/?x-api-key=' . $api_key,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json'
                ),
                'body'    => wp_json_encode($data),
                'timeout' => 30,
                'sslverify' => true
            )
        );

        if (is_wp_error($response)) {
            error_log('KulaHub GF: API request failed - ' . $response->get_error_message());
            $this->log_error($response->get_error_message(), $data, $form_id, $entry_id);
            $this->failed_submissions->add_failed_submission($form_id, $entry_id, $response->get_error_message());
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $raw_body      = wp_remote_retrieve_body($response);
        $response_body = json_decode($raw_body, true);

        error_log('KulaHub GF: API response code: ' . $response_code);
        error_log('KulaHub GF: API response body: ' . $raw_body);

        if ($response_code !== 200) {
            $error_message = $this->extract_error_message($response_body, $raw_body, $response_code);

            $error = new WP_Error(
                'api_error',
                $error_message,
                array(
                    'status' => $response_code,
                    'response' => $response_body
                )
            );

            $this->log_error($error_message, $data, $form_id, $entry_id, $response_body);
            $this->failed_submissions->add_failed_submission($form_id, $entry_id, $error_message);
            
            return $error;
        }

        return $response_body;
    }

    /**
     * Validate API key
     *
     * @param string $api_key API key to validate
     * @return bool|WP_Error True if valid, WP_Error if not
     */
    public function validate_api_key($api_key) {
        $response = wp_remote_get(
            $this->api_endpoint . '/validate',
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Accept'        => 'application/json',
                    'User-Agent'    => 'KulaHub-GF/' . KULAHUB_GF_VERSION,
                ),
                'timeout' => 15,
            )
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            return new WP_Error(
                'invalid_api_key',
                __('Invalid API key', 'kulahub-gf'),
                array('status' => $response_code)
            );
        }

        return true;
    }

    /**
     * Test API connection
     *
     * @param string $api_key Optional API key to test. If empty, uses the first configured key.
     * @return bool|WP_Error
     */
    public function test_connection($api_key = '') {
        if (empty($api_key)) {
            if (!empty($this->api_keys)) {
                // Use the first key if none specified
                $first_key = reset($this->api_keys);
                $api_key = $first_key['key'];
            } else {
                // Fallback to legacy key
                $api_key = get_option('kulahub_api_key');
            }
        }
        
        if (empty($api_key)) {
            return new WP_Error(
                'missing_api_key',
                __('KulaHub API key is not configured', 'kulahub-gf')
            );
        }

        // Test connection using the actual endpoint
        $response = wp_remote_get(
            $this->api_endpoint . '/?x-api-key=' . $api_key,
            array(
                'timeout' => 15,
                'sslverify' => true,
            )
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code === 405) {  // Method Not Allowed means the endpoint exists but doesn't accept GET
            return true;  // This is actually good - means we found the endpoint
        }
        
        return new WP_Error(
            'connection_failed',
            sprintf(__('API connection test failed with status: %s', 'kulahub-gf'), $response_code)
        );
    }

    /**
     * Build a useful error message from an API response
     *
     * The KulaHub API reports validation failures as an ASP.NET problem details
     * document, which has no top level "message" key. Falling straight through
     * to "Unknown API error" hid the reason a submission was rejected.
     */
    private function extract_error_message($response_body, $raw_body, $response_code) {
        if (is_array($response_body)) {
            foreach (array('message', 'Message') as $key) {
                if (!empty($response_body[$key]) && is_string($response_body[$key])) {
                    return $response_body[$key];
                }
            }

            if (!empty($response_body['errors']) && is_array($response_body['errors'])) {
                $messages = array();

                foreach ($response_body['errors'] as $field => $field_errors) {
                    $field_errors = is_array($field_errors) ? $field_errors : array($field_errors);
                    $field_errors = array_filter($field_errors, 'is_scalar');

                    if (empty($field_errors)) {
                        continue;
                    }

                    $prefix     = is_string($field) ? $field . ': ' : '';
                    $messages[] = $prefix . implode(' ', array_map('strval', $field_errors));
                }

                if (!empty($messages)) {
                    return implode(' | ', $messages);
                }
            }

            foreach (array('title', 'detail', 'error') as $key) {
                if (!empty($response_body[$key]) && is_string($response_body[$key])) {
                    return $response_body[$key];
                }
            }
        }

        // Fall back to the raw body (for example a plain text or HTML error page),
        // trimmed so a full error page never ends up in the log or the admin screen.
        if (is_string($raw_body) && trim(wp_strip_all_tags($raw_body)) !== '') {
            $plain_body = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags($raw_body)));

            if (mb_strlen($plain_body) > 300) {
                $plain_body = mb_substr($plain_body, 0, 300) . '...';
            }

            return $plain_body;
        }

        return sprintf(
            /* translators: %s: HTTP status code returned by the KulaHub API. */
            __('Unknown API error (HTTP %s)', 'kulahub-gf'),
            $response_code
        );
    }

    /**
     * Log API errors
     *
     * @param string $message Error message
     * @param array  $data Request data
     * @param string $form_id Form ID
     * @param int    $entry_id Entry ID
     * @param array  $response_body Response body
     */
    private function log_error($message, $data, $form_id, $entry_id, $response_body = null) {
        $log_entry = sprintf(
            "[%s] Error: %s\nForm ID: %s\nEntry ID: %s\nData: %s\nResponse: %s\n",
            current_time('c'),
            $message,
            $form_id,
            $entry_id,
            wp_json_encode($data),
            wp_json_encode($response_body)
        );

        $logs_dir = KULAHUB_GF_PLUGIN_DIR . 'logs';
        $log_file = $logs_dir . '/api-errors-' . date('Y-m-d') . '.log';

        if (wp_mkdir_p($logs_dir)) {
            file_put_contents($log_file, $log_entry, FILE_APPEND);
        }
    }

    private function is_rate_limited() {
        $transient_key = 'kulahub_gf_rate_limit';
        $rate_count = get_transient($transient_key);
        
        if (false === $rate_count) {
            set_transient($transient_key, 1, MINUTE_IN_SECONDS);
            return false;
        }
        
        if ($rate_count >= 30) { // 30 requests per minute
            return true;
        }
        
        set_transient($transient_key, $rate_count + 1, MINUTE_IN_SECONDS);
        return false;
    }
} 