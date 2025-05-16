<?php

$observers = [
    [
        'eventname'   => '\core\event\enrol_instance_updated',
        'callback'    => '\local_telconfig\observer::enrol_instance_changed',
        'priority'    => 9999,
        'internal'    => false,
    ],
];
