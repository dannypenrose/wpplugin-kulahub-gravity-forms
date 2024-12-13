class Test_KulaHub_GF_API extends WP_UnitTestCase {
    private $api;

    public function setUp(): void {
        parent::setUp();
        $this->api = new KulaHub_GF_API();
    }

    public function test_missing_api_key() {
        $response = $this->api->send_data(array(), 1, 1);
        $this->assertTrue(is_wp_error($response));
        $this->assertEquals('missing_api_key', $response->get_error_code());
    }

    public function test_validate_api_key() {
        $response = $this->api->validate_api_key('invalid_key');
        $this->assertTrue(is_wp_error($response));
    }

    public function test_rate_limiting() {
        for ($i = 0; $i < 31; $i++) {
            $this->api->send_data(array(), 1, 1);
        }
        
        $response = $this->api->send_data(array(), 1, 1);
        $this->assertTrue(is_wp_error($response));
        $this->assertEquals('rate_limit_exceeded', $response->get_error_code());
    }
} 