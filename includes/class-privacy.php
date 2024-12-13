class KulaHub_GF_Privacy {
    public function __construct() {
        add_filter('wp_privacy_personal_data_exporters', array($this, 'register_exporters'));
        add_filter('wp_privacy_personal_data_erasers', array($this, 'register_erasers'));
    }

    public function register_exporters($exporters) {
        $exporters['kulahub-gf'] = array(
            'exporter_friendly_name' => __('KulaHub Form Submissions', 'kulahub-gf'),
            'callback' => array($this, 'export_data'),
        );
        return $exporters;
    }

    public function register_erasers($erasers) {
        $erasers['kulahub-gf'] = array(
            'eraser_friendly_name' => __('KulaHub Form Submissions', 'kulahub-gf'),
            'callback' => array($this, 'erase_data'),
        );
        return $erasers;
    }

    public function export_data($email_address, $page = 1) {
        global $wpdb;
        $data = array();
        
        // Get submissions from failed submissions table
        $submissions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}kulahub_failed_submissions 
            WHERE form_data LIKE %s",
            '%' . $email_address . '%'
        ));

        foreach ($submissions as $submission) {
            $data[] = array(
                'group_id' => 'kulahub_submissions',
                'group_label' => __('KulaHub Form Submissions', 'kulahub-gf'),
                'item_id' => 'submission-' . $submission->id,
                'data' => array(
                    array(
                        'name' => __('Form ID', 'kulahub-gf'),
                        'value' => $submission->form_id
                    ),
                    array(
                        'name' => __('Date', 'kulahub-gf'),
                        'value' => $submission->created_at
                    )
                )
            );
        }

        return array(
            'data' => $data,
            'done' => true
        );
    }

    public function erase_data($email_address, $page = 1) {
        global $wpdb;
        
        // Find and anonymize failed submissions
        $count = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}kulahub_failed_submissions 
            SET form_data = '[data removed]' 
            WHERE form_data LIKE %s",
            '%' . $email_address . '%'
        ));

        return array(
            'items_removed' => $count,
            'items_retained' => false,
            'messages' => array(),
            'done' => true
        );
    }
} 