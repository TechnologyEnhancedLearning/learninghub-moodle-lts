<?php
namespace local_custom_service\privacy;
 
defined('MOODLE_INTERNAL') || die;
 
use core_privacy\local\request\null_provider;

class provider implements null_provider {
    public static function get_reason() : string {
        return 'privacy:metadata';
    }
}