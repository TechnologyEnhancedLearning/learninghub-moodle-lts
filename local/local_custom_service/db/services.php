<?php

defined('MOODLE_INTERNAL') || die();
$functions = array(
   
    'mod_scorm_insert_scorm_resource' => array(
        'classname' => 'insert_scorm_resource',
        'methodname' => 'insert_scorm_resource',
        'classpath' => 'local/local_custom_service/externallib.php',
        'description' => 'Create a scorm resource under a course',
        'type' => 'write',
        'ajax' => true,
    ),
    
);

$services = array(
    'Insert Scorm resource' => array(
        'functions' => array(
           
            'mod_scorm_insert_scorm_resource'
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
          // This field os optional, but requried if the `restrictedusers` value is
        // set, so as to allow configuration via the Web UI.
        'shortname' =>  'InsertScorm',

        // Whether to allow file downloads.
        'downloadfiles' => 0,

        // Whether to allow file uploads.
        'uploadfiles'  => 0,
    )
);