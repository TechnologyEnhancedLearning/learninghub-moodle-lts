<?php

$observers = [
    [
        'eventname'   => '\core\event\enrol_instance_updated',
        'callback'    => '\local_telconfig\observer::enrol_instance_changed',
        'priority'    => 9999,
        'internal'    => false,
    ],
    [
        'eventname'   => '\core\event\course_updated',
        'callback'    => '\local_telconfig\observer::local_course_updated',
        'priority'    => 9999,
        'internal'    => false,
    ],   
    [
        'eventname'   => '\core\event\course_section_updated',
        'callback'    => '\local_telconfig\observer::local_section_updated',
        'priority'    => 9999,
        'internal'    => false,
    ],
    [
        'eventname'   => '\core\event\course_module_updated',
        'callback'    => '\local_telconfig\observer::local_module_updated',
        'priority'    => 9999,
        'internal'    => false,
    ],
];
