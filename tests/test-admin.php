<?php
class Test_KulaHub_GF_Admin extends WP_UnitTestCase {
    private $admin;

    public function setUp(): void {
        parent::setUp();
        $this->admin = new KulaHub_GF_Admin();
    }

    public function test_sanitize_api_key() {
        // Test valid API key
        $valid_key = str_repeat('a', 32);
        $this->assertEquals($valid_key, $this->admin->sanitize_api_key($valid_key));

        // Test invalid API key
        $invalid_key = 'short';
        $this->assertNotEquals($invalid_key, $this->admin->sanitize_api_key($invalid_key));
    }

    public function test_settings_registration() {
        global $wp_registered_settings;
        
        $this->admin->register_settings();
        $this->assertArrayHasKey('kulahub_api_key', $wp_registered_settings);
    }

    public function test_menu_addition() {
        $this->admin->add_admin_menu();
        global $admin_page_hooks;
        
        $this->assertContains('kulahub-settings', array_keys($admin_page_hooks));
    }
} 