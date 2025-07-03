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

            // Only proceed if the course has self enrolment enabled
           if (!self::is_course_self_enrollable($course->id)) {
                return;
            }

            // Rebuild and send metadata to API (as an update).
            $data = course_data_builder::build_course_metadata($course);
            helper::send_findwise_api($data);

        } catch (\dml_exception $e) {
            debugging("Failed to process local course update: " . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }    

    /**
     * Triggered when a section is updated.
     *
     * @param \core\event\base $event
     * @return void
     */
    public static function local_section_changed(\core\event\base $event): void {
        global $DB;

        try {            
            $course = $DB->get_record('course', ['id' => $event->courseid], '*', MUST_EXIST);

            // Only proceed if the course has self enrolment enabled
            if (!self::is_course_self_enrollable($course->id)) {
                return;
            }

            // Handle the update, e.g., send new metadata
            $data = course_data_builder::build_course_metadata($course); // or enrich this with section title
            helper::send_findwise_api($data);

        } catch (\Throwable $e) {
            debugging('Error handling local section change: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Triggered when a module is updated.
     *
     * @param \core\event\base $event
     * @return void
     */
    public static function local_module_changed(\core\event\base $event): void {
        global $DB;

        try {
            $courseid = $event->courseid;
            $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

            // Only proceed if the course has self enrolment enabled
            if (!self::is_course_self_enrollable($course->id)) {
                return;
            }

            // Build metadata
            $data = course_data_builder::build_course_metadata($course); // or enrich with mod/section name
            helper::send_findwise_api($data);

        } catch (\Throwable $e) {
            debugging('Error handling local module change: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
    private static function is_course_self_enrollable(int $courseid): bool {
        global $DB;

        return $DB->record_exists('enrol', [
            'courseid' => $courseid,
            'enrol' => 'self',
            'status' => ENROL_INSTANCE_ENABLED,
        ]);
    }

    public static function on_user_loggedout(\core\event\user_loggedout $event) {
        global $DB, $CFG, $USER;

        $user = $event->get_record_snapshot('user', $event->objectid);

        if ($user->auth !== 'oidc') {
            return true; // Ignore non-OIDC users
        }

        // Load token
        $tokenrec = $DB->get_record('auth_oidc_token', ['userid' => $user->id]);
        if ($tokenrec && isset($tokenrec->idtoken)) {
            $idtoken = $tokenrec->idtoken;
            
            $logouturl = get_config('auth_oidc', 'logouturi');
            if (!$logouturl) {
                $logouturl = 'https://login.microsoftonline.com/organizations/oauth2/logout?post_logout_redirect_uri=' .
                    urlencode($CFG->wwwroot);
            }
            // Append id_token_hint
            $logouturl .= '&id_token_hint=' . $idtoken;

            // Use redirect now (Moodle already logged out the session)
            redirect($logouturl);            
        }

        return true;
    }
}
