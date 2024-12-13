<?php
class Test_KulaHub_GF_Updater extends WP_UnitTestCase {
    private $updater;

    public function setUp(): void {
        parent::setUp();
        $this->updater = new KulaHub_GF_Updater(__FILE__);
    }

    public function test_plugin_info() {
        $this->updater->set_plugin_properties();
        $this->assertNotNull($this->updater->plugin);
    }

    public function test_transient_modification() {
        $transient = new stdClass();
        $transient->checked = array(
            plugin_basename(__FILE__) => '1.0.0'
        );
        
        $result = $this->updater->modify_transient($transient);
        $this->assertInstanceOf(stdClass::class, $result);
    }
} 