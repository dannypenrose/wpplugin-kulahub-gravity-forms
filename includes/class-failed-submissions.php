<?php
class KulaHub_GF_Failed_Submissions {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'kulahub_failed_submissions';
        
        add_action('admin_menu', array($this, 'add_failed_submissions_page'));
        add_action('admin_post_retry_failed_submission', array($this, 'handle_retry'));
    }

    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            entry_id bigint(20) NOT NULL,
            form_id bigint(20) NOT NULL,
            error_message text NOT NULL,
            retry_count int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_retry datetime DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_failed_submission($entry_id, $form_id, $error_message) {
        global $wpdb;
        
        return $wpdb->insert(
            $this->table_name,
            array(
                'entry_id' => $entry_id,
                'form_id' => $form_id,
                'error_message' => $error_message,
            ),
            array('%d', '%d', '%s')
        );
    }

    public function add_failed_submissions_page() {
        add_submenu_page(
            'tools.php',
            __('Failed KulaHub Submissions', 'kulahub-gf'),
            __('Failed KulaHub Submissions', 'kulahub-gf'),
            'manage_options',
            'kulahub-failed-submissions',
            array($this, 'render_failed_submissions_page')
        );
    }

    public function render_failed_submissions_page() {
        global $wpdb;
        
        $failed_submissions = $wpdb->get_results(
            "SELECT * FROM $this->table_name ORDER BY created_at DESC"
        );
        
        include KULAHUB_GF_PLUGIN_DIR . 'templates/failed-submissions.php';
    }
} 