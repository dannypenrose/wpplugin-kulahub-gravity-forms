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

    /**
     * Invoke a private method on the integration class.
     */
    private function invoke_private($method, array $args) {
        $reflection = new ReflectionMethod('KulaHub_GF_Integration', $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($this->integration, $args);
    }

    private function radio_field($value) {
        return array(
            array('id' => '5', 'type' => 'radio', 'inputs' => array()),
            array('5' => $value),
        );
    }

    public function test_email_subscribe_returns_boolean_true_for_affirmative_answers() {
        foreach (array('Yes', 'yes', 'YES', 'True', '1', 'On', 'Yes please', 'Opt in') as $answer) {
            list($field, $entry) = $this->radio_field($answer);

            $this->assertTrue(
                $this->invoke_private('get_email_subscribe_value', array($field, $entry)),
                sprintf('Expected "%s" to resolve to true', $answer)
            );
        }
    }

    public function test_email_subscribe_returns_boolean_false_for_negative_answers() {
        foreach (array('No', 'no', 'NO', 'False', '0', 'Off', 'No thanks', 'Not right now', 'Opt out', 'Unsubscribe') as $answer) {
            list($field, $entry) = $this->radio_field($answer);

            $this->assertFalse(
                $this->invoke_private('get_email_subscribe_value', array($field, $entry)),
                sprintf('Expected "%s" to resolve to false', $answer)
            );
        }
    }

    public function test_email_subscribe_returns_false_when_no_answer_is_given() {
        list($field, $entry) = $this->radio_field('');

        $this->assertFalse($this->invoke_private('get_email_subscribe_value', array($field, $entry)));
    }

    public function test_email_subscribe_treats_a_ticked_checkbox_as_consent() {
        $field = array('id' => '6', 'type' => 'checkbox', 'inputs' => array(array('id' => '6.1')));

        $this->assertTrue($this->invoke_private('get_email_subscribe_value', array($field, array('6.1' => 'Keep me informed'))));
        $this->assertFalse($this->invoke_private('get_email_subscribe_value', array($field, array('6.1' => ''))));
    }

    public function test_email_subscribe_is_encoded_as_a_json_boolean_not_a_string() {
        list($field, $entry) = $this->radio_field('Yes');

        $value = $this->invoke_private('get_email_subscribe_value', array($field, $entry));

        $this->assertSame('{"emailsubscribe":true}', wp_json_encode(array('emailsubscribe' => $value)));
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