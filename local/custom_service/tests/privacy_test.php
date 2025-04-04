<?php
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/custom_service/classes/privacy/provider.php');

use core_privacy\tests\provider_testcase;
use local_custom_service\privacy\provider;

class local_custom_service_privacy_test extends provider_testcase {

    public function test_privacy_provider() {
        $this->assertInstanceOf(
            \core_privacy\local\metadata\null_provider::class,
            new provider()
        );
    }
}
