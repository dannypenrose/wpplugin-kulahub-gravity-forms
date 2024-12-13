<?php
class KulaHub_GF_Logger {
    private static $instance = null;
    private $log_directory;

    private function __construct() {
        $this->log_directory = KULAHUB_GF_PLUGIN_DIR . 'logs';
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function log($level, $message, $context = array()) {
        if (!is_dir($this->log_directory)) {
            wp_mkdir_p($this->log_directory);
        }

        $log_file = $this->log_directory . '/kulahub-' . date('Y-m-d') . '.log';
        
        $entry = sprintf(
            "[%s] %s: %s %s\n",
            current_time('c'),
            strtoupper($level),
            $message,
            !empty($context) ? json_encode($context) : ''
        );

        error_log($entry, 3, $log_file);
    }

    public function error($message, $context = array()) {
        $this->log('error', $message, $context);
    }

    public function info($message, $context = array()) {
        $this->log('info', $message, $context);
    }

    public function debug($message, $context = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->log('debug', $message, $context);
        }
    }
} 