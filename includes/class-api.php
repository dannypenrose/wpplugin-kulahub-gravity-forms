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
    private $api_endpoint = 'https://api.kulahub.com/v1';

    /**
     * API key
     *
     * @var string
     */
    private $api_key;

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
        $this->api_key = get_option('kulahub_api_key');
        $this->failed_submissions = new KulaHub_GF_Failed_Submissions();
    }

    /**
     * Send data to KulaHub
     *
     * @param array  $data Form data to send
     * @param string $form_id Gravity Form ID
     * @param int    $entry_id Gravity Form entry ID
     * @return array|WP_Error Response array or WP_Error on failure
     */
    public function send_data($data, $form_id, $entry_id) {
        if (empty($this->api_key)) {
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
            $this->api_endpoint . '/submissions',
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                    'User-Agent'    => 'KulaHub-GF/' . KULAHUB_GF_VERSION,
                ),
                'body'    => wp_json_encode($data),
                'timeout' => 30,
            )
        );

        if (is_wp_error($response)) {
            $this->log_error($response->get_error_message(), $data, $form_id, $entry_id);
            $this->failed_submissions->add_failed_submission($form_id, $entry_id, $response->get_error_message());
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code !== 200) {
            $error_message = isset($response_body['message']) 
                ? $response_body['message'] 
                : __('Unknown API error', 'kulahub-gf');
            
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
            file_put_contents($log_file, $log_file, FILE_APPEND);
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