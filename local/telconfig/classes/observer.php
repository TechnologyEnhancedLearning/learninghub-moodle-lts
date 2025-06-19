<?php

namespace local_telconfig;
use local_telconfig\course_data_builder;
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

        try {

            // Get enrol instance
             $enrol = $DB->get_record('enrol', ['id' => $event->objectid], '*', MUST_EXIST);

            // Only act if it's for 'self' enrolment.
            if (!isset($event->other['enrol']) || $event->other['enrol'] !== 'self') {
                return;
            }

            // Get course info
            $course = $DB->get_record('course', ['id' => $event->courseid], '*', MUST_EXIST);

            if ((int)$enrol->status === ENROL_INSTANCE_ENABLED) {
                // Fetch the enrolment instance data.
                $data = course_data_builder::build_course_metadata($course);
                helper::send_findwise_api($data);
            } else {
                // Delete from external API when disabled.
                $data = ['course_id' => $course->id];
                helper::send_findwise_api($data,'DELETE');
            }

        } catch (\dml_exception $e) {
            debugging("Failed to fetch course/enrol data: " . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Triggered when a course is updated.
     *
     * @param \core\event\base $event
     * @return void
     */
    public static function local_course_updated(\core\event\base $event): void {
        global $DB;

        try {
            $course = $DB->get_record('course', ['id' => $event->objectid], '*', MUST_EXIST);

            // Rebuild and send metadata to API (as an update).
            $data = course_data_builder::build_course_metadata($course);
            helper::send_findwise_api($data);

        } catch (\dml_exception $e) {
            debugging("Failed to process course update: " . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
