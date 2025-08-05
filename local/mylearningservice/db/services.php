<?php
$functions = array(
    'mylearningservice_get_recent_courses' => array(
        'classname'   => 'mylearningservice_external',
        'methodname'  => 'get_recent_courses',
        'classpath'   => 'local/mylearningservice/externallib.php',
        'description' => 'Get courses a user was enrolled in within the last 6 months',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'moodle/course:view',
    ),
);
$services = array(
    'Get Recent Courses' => array(
        'functions' => array(
           
            'mylearningservice_get_recent_courses'
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
          // This field os optional, but requried if the `restrictedusers` value is
        // set, so as to allow configuration via the Web UI.
        'shortname' =>  'GetRecentCourses',

        // Whether to allow file downloads.
        'downloadfiles' => 0,

        // Whether to allow file uploads.
        'uploadfiles'  => 0,
    )
);