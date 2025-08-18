<?php
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

class mylearningservice_external extends external_api {
  public static function get_recent_courses_parameters() {
    return new external_function_parameters(
        array(
            'userid' => new external_value(PARAM_INT, 'User ID'),
            // Allow omission by setting VALUE_DEFAULT and defaulting to 0 (meaning no limit)
            'months' => new external_value(PARAM_INT, 'Number of past months to include', VALUE_DEFAULT, 0),
            // Optional status filter
            'statusfilter' => new external_value(PARAM_ALPHA, 'Course status filter: completed, inprogress, or empty for all', VALUE_DEFAULT, ''),
            // Optional search term
            'search' => new external_value(PARAM_RAW, 'Search keyword for course fullname/shortname', VALUE_DEFAULT, '')
        )
    );
}

   public static function get_recent_courses($userid, $months = 0, $statusfilter = '', $search = '') {
     global $CFG, $USER, $DB, $OUTPUT;
     require_once($CFG->dirroot . '/course/lib.php');
     require_once($CFG->dirroot . '/user/lib.php');
     require_once($CFG->libdir . '/completionlib.php');
     // Do basic automatic PARAM checks on incoming data, using params description
     // If any problems are found then exceptions are thrown with helpful error messages
     $returnusercount = true;
     $courses = enrol_get_users_courses($userid, true, '*');
     $result = array();
     // Get user data including last access to courses.
     $user = get_complete_user_data('id', $userid);
     $sameuser = $USER->id == $userid;
     // Retrieve favourited courses (starred).
     $favouritecourseids = array();
     if ($sameuser) {
         $ufservice = \core_favourites\service_factory::get_service_for_user_context(\context_user::instance($userid));
         $favourites = $ufservice->find_favourites_by_type('core_course', 'courses');

         if ($favourites) {
             $favouritecourseids = array_flip(array_map(
                 function($favourite) {
                     return $favourite->itemid;
                 }, $favourites));
         }
     }
     if(!empty($months))
     {
           $sixmonthsago = strtotime("-{$months} months"); // Calculate 6 months ago  
     }
     
     foreach ($courses as $course) {
        if (!empty($search)) {
        $searchlower = core_text::strtolower($search);
        $coursename   = core_text::strtolower($course->fullname);
        $courseshort  = core_text::strtolower($course->shortname);

        if (strpos($coursename, $searchlower) === false &&
            strpos($courseshort, $searchlower) === false) {
            continue; // Skip if search term not found
        }
    }
        
         $enrolrecords = $DB->get_records_sql("
             SELECT ue.timestart,ue.timeend
             FROM {user_enrolments} ue
             JOIN {enrol} e ON ue.enrolid = e.id
             WHERE ue.userid = :userid AND e.courseid = :courseid
             ORDER BY ue.timestart DESC
         ", [
             'userid' => $userid,
             'courseid' => $course->id,
         ]);

         // Get the first record's timestart (most recent)
         $enroltime = 0;
         if (!empty($enrolrecords)) {
             $firstrecord = reset($enrolrecords); // Gets first item
             $enroltime = (int)$firstrecord->timestart;
             $enrolendtime=(int)$firstrecord->timeend;
         }
         if(!empty($months))
         {
             if ($enroltime && (int)$enroltime < $sixmonthsago) {
             continue;
             }    
         }
         
         $context = context_course::instance($course->id, IGNORE_MISSING);
         try {
             self::validate_context($context);
         } catch (Exception $e) {
             // current user can not access this course, sorry we can not disclose who is enrolled in this course!
             continue;
         }

         // If viewing details of another user, then we must be able to view participants as well as profile of that user.
         if (!$sameuser && (!course_can_view_participants($context) || !user_can_view_profile($user, $course))) {
             continue;
         }

         if ($returnusercount) {
             list($enrolledsqlselect, $enrolledparams) = get_enrolled_sql($context);
             $enrolledsql = "SELECT COUNT('x') FROM ($enrolledsqlselect) enrolleduserids";
             $enrolledusercount = $DB->count_records_sql($enrolledsql, $enrolledparams);
         }

         $displayname = \core_external\util::format_string(get_course_display_name_for_list($course), $context);
         list($course->summary, $course->summaryformat) =
             \core_external\util::format_text($course->summary, $course->summaryformat, $context, 'course', 'summary', null);
         $course->fullname = \core_external\util::format_string($course->fullname, $context);
         $course->shortname = \core_external\util::format_string($course->shortname, $context);
        

         $progress = null;
         $completed = null;
         $completionhascriteria = false;
         $completionusertracked = false;

         // Return only private information if the user should be able to see it.
         if ($sameuser || completion_can_view_data($userid, $course)) {
             if ($course->enablecompletion) {
                 $completion = new completion_info($course);
                 $completed = $completion->is_course_complete($userid);
                 $completionhascriteria = $completion->has_criteria();
                 $completionusertracked = $completion->is_tracked_user($userid);
                 $progress = \core_completion\progress::get_course_progress_percentage($course, $userid);

                  $activities = $completion->get_activities();
                  $totalactivities = count($activities);
                  $completedactivities = 0;
                  foreach ($activities as $cm) {
                     $data = $completion->get_data($cm, false, $userid);
                     if ($data->completionstate == COMPLETION_COMPLETE || $data->completionstate == COMPLETION_COMPLETE_PASS) {
                         $completedactivities++;
                     }
                 }
             }
         }
        if (!empty($statusfilter)) {
            if ($statusfilter === 'completed') {
                if (empty($completed) && $progress < 100) {
                    continue;
                }
            }
            if ($statusfilter === 'inprogress') {
                if (!empty($completed) || $progress >= 100) {
                    continue;
                }
            }
        }

         $lastaccess = null;
         // Check if last access is a hidden field.
         $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
         $canviewlastaccess = $sameuser || !isset($hiddenfields['lastaccess']);
         if (!$canviewlastaccess) {
             $canviewlastaccess = has_capability('moodle/course:viewhiddenuserfields', $context);
         }

         if ($canviewlastaccess && isset($user->lastcourseaccess[$course->id])) {
             $lastaccess = $user->lastcourseaccess[$course->id];
         }

         $hidden = false;
         if ($sameuser) {
             $hidden = boolval(get_user_preferences('block_myoverview_hidden_course_' . $course->id, 0));
         }

         // Retrieve course overview used files.
         $courselist = new core_course_list_element($course);
         $overviewfiles = array();
         foreach ($courselist->get_course_overviewfiles() as $file) {
             $fileurl = moodle_url::make_webservice_pluginfile_url($file->get_contextid(), $file->get_component(),
                                                                     $file->get_filearea(), null, $file->get_filepath(),
                                                                     $file->get_filename())->out(false);
             $overviewfiles[] = array(
                 'filename' => $file->get_filename(),
                 'fileurl' => $fileurl,
                 'filesize' => $file->get_filesize(),
                 'filepath' => $file->get_filepath(),
                 'mimetype' => $file->get_mimetype(),
                 'timemodified' => $file->get_timemodified(),
             );
         }

         $courseimage = \core_course\external\course_summary_exporter::get_course_image($course);
         if (!$courseimage) {
             $courseimage = $OUTPUT->get_generated_url_for_course($context);
         }
         $hascertificate =self:: course_has_certificate_enabled($course->id);
         $courseresult = [
             'id' => $course->id,
             'shortname' => $course->shortname,
             'fullname' => $course->fullname,
             'displayname' => $displayname,
             'idnumber' => $course->idnumber,
             'visible' => $course->visible,
             'summary' => $course->summary,
             'summaryformat' => $course->summaryformat,
             'format' => $course->format,
             'courseimage' => $courseimage,
             'showgrades' => $course->showgrades,
             'lang' => clean_param($course->lang, PARAM_LANG),
             'enablecompletion' => $course->enablecompletion,
             'completionhascriteria' => $completionhascriteria,
             'completionusertracked' => $completionusertracked,
             'category' => $course->category,
             'progress' => $progress,
             'completed' => $completed,
             'startdate' => $enroltime,
             'enddate' => $enrolendtime,
             'marker' => $course->marker,
             'lastaccess' => $lastaccess,
             'isfavourite' => isset($favouritecourseids[$course->id]),
             'hidden' => $hidden,
             'overviewfiles' => $overviewfiles,
             'showactivitydates' => $course->showactivitydates,
             'showcompletionconditions' => $course->showcompletionconditions,
             'timemodified' => $course->timemodified,
             'certificateenabled'=>$hascertificate,
             'totalactivities' => $totalactivities,
             'completedactivities'=>$completedactivities

         ];
         if ($returnusercount) {
             $courseresult['enrolledusercount'] = $enrolledusercount;
         }
         $result[] = $courseresult;
     }

     return $result;
 }
    public static function course_has_certificate_enabled($courseid) {
    global $DB;

    $sql = "SELECT cm.id
            FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module
            WHERE m.name = :modname
              AND cm.course = :courseid
              AND cm.visible = 1"; // only visible activities

    return $DB->record_exists_sql($sql, [
        'modname' => 'coursecertificate',
        'courseid' => $courseid
    ]);
}

    public static function get_recent_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'        => new external_value(PARAM_INT, 'id of course'),
                    'shortname' => new external_value(PARAM_RAW, 'short name of course'),
                    'fullname'  => new external_value(PARAM_RAW, 'long name of course'),
                    'displayname' => new external_value(PARAM_RAW, 'course display name for lists.', VALUE_OPTIONAL),
                    'enrolledusercount' => new external_value(PARAM_INT, 'Number of enrolled users in this course',
                            VALUE_OPTIONAL),
                    'idnumber'  => new external_value(PARAM_RAW, 'id number of course'),
                    'visible'   => new external_value(PARAM_INT, '1 means visible, 0 means not yet visible course'),
                    'summary'   => new external_value(PARAM_RAW, 'summary', VALUE_OPTIONAL),
                    'summaryformat' => new external_format_value('summary', VALUE_OPTIONAL),
                    'format'    => new external_value(PARAM_PLUGIN, 'course format: weeks, topics, social, site', VALUE_OPTIONAL),
                    'courseimage' => new external_value(PARAM_URL, 'The course image URL', VALUE_OPTIONAL),
                    'showgrades' => new external_value(PARAM_BOOL, 'true if grades are shown, otherwise false', VALUE_OPTIONAL),
                    'lang'      => new external_value(PARAM_LANG, 'forced course language', VALUE_OPTIONAL),
                    'enablecompletion' => new external_value(PARAM_BOOL, 'true if completion is enabled, otherwise false',
                                                                VALUE_OPTIONAL),
                    'completionhascriteria' => new external_value(PARAM_BOOL, 'If completion criteria is set.', VALUE_OPTIONAL),
                    'completionusertracked' => new external_value(PARAM_BOOL, 'If the user is completion tracked.', VALUE_OPTIONAL),
                    'category' => new external_value(PARAM_INT, 'course category id', VALUE_OPTIONAL),
                    'progress' => new external_value(PARAM_FLOAT, 'Progress percentage', VALUE_OPTIONAL),
                    'completed' => new external_value(PARAM_BOOL, 'Whether the course is completed.', VALUE_OPTIONAL),
                    'startdate' => new external_value(PARAM_INT, 'Timestamp when the course start', VALUE_OPTIONAL),
                    'enddate' => new external_value(PARAM_INT, 'Timestamp when the course end', VALUE_OPTIONAL),
                    'marker' => new external_value(PARAM_INT, 'Course section marker.', VALUE_OPTIONAL),
                    'lastaccess' => new external_value(PARAM_INT, 'Last access to the course (timestamp).', VALUE_OPTIONAL),
                    'isfavourite' => new external_value(PARAM_BOOL, 'If the user marked this course a favourite.', VALUE_OPTIONAL),
                    'hidden' => new external_value(PARAM_BOOL, 'If the user hide the course from the dashboard.', VALUE_OPTIONAL),
                    'overviewfiles' => new external_files('Overview files attached to this course.', VALUE_OPTIONAL),
                    'showactivitydates' => new external_value(PARAM_BOOL, 'Whether the activity dates are shown or not'),
                    'showcompletionconditions' => new external_value(PARAM_BOOL, 'Whether the activity completion conditions are shown or not'),
                    'timemodified' => new external_value(PARAM_INT, 'Last time course settings were updated (timestamp).',
                        VALUE_OPTIONAL),
                    'certificateenabled' => new external_value(PARAM_BOOL, 'Whether the course has certificate.', VALUE_OPTIONAL),
                    'totalactivities' => new external_value(PARAM_INT, 'total activities', VALUE_OPTIONAL),
                    'completedactivities' => new external_value(PARAM_INT, 'completed activities count', VALUE_OPTIONAL),
                )
            )
        );
    }

    public static function get_user_certificates_parameters() {
    return new external_function_parameters(
        [
            'userid' => new external_value(PARAM_INT, 'User ID'),
            'searchterm' => new external_value(PARAM_RAW, 'Search keyword for filtering certificates', VALUE_OPTIONAL)
        ]
    );
}
public static function get_user_certificates($userid, $searchterm = '') {
    global $DB, $CFG;
    require_once($CFG->libdir . '/filelib.php');
    // Validate both parameters
    $params = self::validate_parameters(
        self::get_user_certificates_parameters(),
        [
            'userid' => $userid,
            'searchterm' => $searchterm
        ]
    );
    // Overwrite variables with validated values
    $userid     = $params['userid'];
    $searchterm = $params['searchterm'];
    // Fetch raw data
    $records = self::fetch_user_certificates_data($userid, $searchterm);
    $results = [];
    foreach ($records as $rec) {
        // Build download link
        $downloadurl = new moodle_url('/mod/coursecertificate/view.php', [
            'id' => $rec->cmid,
            'download' => 1
        ]);
        // Build preview link
        $previewurl = new moodle_url('/mod/coursecertificate/view.php', [
            'id' => $rec->cmid
        ]);
        $results[] = [
            'resourcetype' => 'Course',
            'resourcetitle' => $rec->certificatename,
            'resourcename' => $rec->coursename,
            'awardeddate' => $rec->timecreated,
            'downloadlink' => $downloadurl->out(false),
            'previewlink' => $previewurl->out(false)
        ];
    }

    return $results;
}
/**
 * Builds and executes the query to fetch user certificates.
 *
 * @param int $userid
 * @param string $searchterm
 * @return array
 */
private static function fetch_user_certificates_data($userid, $searchterm = '') {
    global $DB;

    $sql = "SELECT ci.id AS issueid,
                   ci.timecreated,
                   ct.name AS certificatename,
                   c.id AS courseid,
                   c.fullname AS coursename,
                   cm.id AS cmid
            FROM {tool_certificate_issues} ci
            JOIN {tool_certificate_templates} ct 
                 ON ci.templateid = ct.id
            JOIN {course} c 
                 ON ci.courseid = c.id
            JOIN {course_modules} cm
                 ON cm.course = c.id
            JOIN {modules} m
                 ON m.id = cm.module AND m.name = 'coursecertificate'
            WHERE ci.userid = :userid";

    $queryparams = ['userid' => $userid];

    // Search term filter
    if (!empty($searchterm)) {
        $like1 = $DB->sql_like('LOWER(c.fullname)', ':search1', false);
        $like2 = $DB->sql_like('LOWER(ct.name)', ':search2', false);
        $sql .= " AND ($like1 OR $like2)";
        $queryparams['search1'] = '%' . core_text::strtolower($searchterm) . '%';
        $queryparams['search2'] = '%' . core_text::strtolower($searchterm) . '%';
    }

    return $DB->get_records_sql($sql, $queryparams);
}

public static function get_user_certificates_returns() {
    return new external_multiple_structure(
        new external_single_structure(
            [
                'resourcetype' => new external_value(PARAM_TEXT, 'Type of resource'),
                'resourcetitle' => new external_value(PARAM_TEXT, 'Certificate title'),
                'resourcename'  => new external_value(PARAM_TEXT, 'Course name'),
                'awardeddate' => new external_value(PARAM_TEXT, 'Award date'),
                'downloadlink' => new external_value(PARAM_URL, 'Download link'),
                'previewlink' => new external_value(PARAM_URL, 'Preview link')
            ]
        )
    );
}
}
