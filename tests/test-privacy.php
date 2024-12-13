class Test_KulaHub_GF_Privacy extends WP_UnitTestCase {
    private $privacy;

    public function setUp(): void {
        parent::setUp();
        $this->privacy = new KulaHub_GF_Privacy();
    }

    public function test_export_data() {
        // Add test data
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'kulahub_failed_submissions',
            array(
                'form_id' => 1,
                'entry_id' => 1,
                'form_data' => json_encode(array('email' => 'test@example.com'))
            )
        );

        $export = $this->privacy->export_data('test@example.com');
        $this->assertNotEmpty($export['data']);
    }
} 