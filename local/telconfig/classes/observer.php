<?php

namespace local_telconfig;
use local_telconfig\helper;
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify

defined('MOODLE_INTERNAL') || die();

class observer {

    /**
     * Triggered when a self enrolment instance is created, updated, or deleted.
     *
     * @param \core\event\base $event
     * @return void
     */
    public static function enrol_instance_changed(\core\event\base $event): void {
    global $DB;

    // Only act if it's for 'self' enrolment.
    if (!isset($event->other['enrol']) || $event->other['enrol'] !== 'self') {
        return;
    }

    try {
        // Get enrol instance
        $enrol = $DB->get_record('enrol', ['id' => $event->objectid], '*', MUST_EXIST);

        // Get course info
        $course = $DB->get_record('course', ['id' => $event->courseid], '*', MUST_EXIST);

        $data = [
            '_id' => $course->id,
            'event' => $event->eventname,
            'enrolid' => $enrol->id,
            'courseid' => $course->id,
            'coursename' => $course->fullname,
            'shortname' => $course->shortname,
            'summary' => $course->summary,
            'startdate' => $course->startdate,
            'enddate' => $course->enddate,
            'enrolstatus' => $enrol->status, // 0 = enabled, 1 = disabled
            'time' => time()
        ];

        helper::send_findwise_api($data);
    } catch (\dml_exception $e) {
        debugging("Failed to fetch course/enrol data: " . $e->getMessage(), DEBUG_DEVELOPER);
    }
}


    /**
     * Sends data to the configured external API endpoint.
     *
     * @param array $data
     * @return void
     */
    private static function send_external_api(array $data): void {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        // Get plugin config values
        $apiurl = get_config('local_telconfig', 'apiurl');
        $apitoken = get_config('local_telconfig', 'apitoken');

        if (empty($apiurl) || empty($apitoken)) {
            debugging('API URL or Token not configured in local_telconfig.', DEBUG_DEVELOPER);
            return;
        }

        $curl = new \curl();
        $headers = [
            "Authorization: Bearer {$apitoken}",
            "Content-Type: application/json"
        ];

        $options = [
            'CURLOPT_HTTPHEADER' => $headers,
            'timeout' => 5,
        ];

        try {
            $response = $curl->post($apiurl, json_encode($data), $options);
            // Optional: log the response if debugging
            if (debugging('', DEBUG_DEVELOPER)) {
                debugging('Self enrol API response: ' . $response, DEBUG_DEVELOPER);
            }
        } catch (\Exception $e) {
            debugging('Error sending to external API: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
