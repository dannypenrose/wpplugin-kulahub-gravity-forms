<?php
class Test_KulaHub_GF_Failed_Submissions extends WP_UnitTestCase {
    private $failed_submissions;

    public function setUp(): void {
        parent::setUp();
        $this->failed_submissions = new KulaHub_GF_Failed_Submissions();
    }

    public function test_table_creation() {
        global $wpdb;
        $this->failed_submissions->create_table();
        
        $table_name = $wpdb->prefix . 'kulahub_failed_submissions';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        $this->assertTrue($table_exists);
    }

    public function test_add_failed_submission() {
        $form_id = 1;
        $entry_id = 1;
        $error = 'Test error';
        
        $result = $this->failed_submissions->add_failed_submission($form_id, $entry_id, $error);
        $this->assertTrue($result > 0);
    }

    public function test_retry_submission() {
        $form_id = 1;
        $entry_id = 1;
        $error = 'Test error';
        
        $id = $this->failed_submissions->add_failed_submission($form_id, $entry_id, $error);
        $result = $this->failed_submissions->handle_retry($id);
        
        $this->assertTrue($result);
    }
} 