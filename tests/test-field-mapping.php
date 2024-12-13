<?php
class Test_KulaHub_GF_Field_Mapping extends WP_UnitTestCase {
    private $integration;

    public function setUp(): void {
        parent::setUp();
        $this->integration = new KulaHub_GF_Integration();
    }

    public function test_custom_field_settings() {
        ob_start();
        $this->integration->add_custom_field_settings(0, array());
        $output = ob_get_clean();
        
        $this->assertStringContainsString('kulahub_field_id', $output);
    }

    public function test_form_settings() {
        $settings = $this->integration->add_custom_form_settings(array(), array());
        
        $this->assertArrayHasKey('kulahub', $settings);
        $this->assertArrayHasKey('fields', $settings['kulahub']);
    }

    public function test_save_settings() {
        $form = array(
            'id' => 1,
            'kulahub_form_id' => 'test_form',
            'kulahub_client_id' => 'test_client'
        );
        
        $result = $this->integration->save_custom_form_settings($form);
        
        $this->assertEquals('test_form', $result['kulahub_form_id']);
        $this->assertEquals('test_client', $result['kulahub_client_id']);
    }
} 