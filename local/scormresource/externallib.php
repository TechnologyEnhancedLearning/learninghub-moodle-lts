<?php
use core_completion\progress;
use core\http_client;
require_once($CFG->libdir.'/externallib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/mod/scorm/lib.php');
require_once($CFG->dirroot.'/mod/scorm/locallib.php');
require_once(__DIR__.'/../../config.php');
require_once($CFG->libdir . '/filestorage/file_storage.php');
require_once("$CFG->dirroot/mod/scorm/datamodels/scormlib.php");

class insert_scorm_resource extends external_api {
    public static function insert_scorm_resource_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_TEXT, 'Course Id'),
                'section' => new external_value(PARAM_TEXT, 'section'),  
                'scormname' => new external_value(PARAM_TEXT, 'scorm name'),
                'foldername' => new external_value(PARAM_TEXT, 'foldername'),
                'base64Zip' => new external_value(PARAM_RAW, 'Base64-encoded ZIP file content')
                 
            )
        );
    }
   
    public static function insert_scorm_resource($courseids,$section,$scormname,$foldername,$base64Zip) {
        global $DB,$CFG;
        require_once($CFG->libdir . '/filelib.php');
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->libdir . '/formslib.php');
        require_once(__DIR__ . '/../../config.php');
        require_login();
        global $DB, $USER;
        // zip file
        $savedPath = self::saveBase64ToZip($base64Zip, $foldername . '.zip');
        $courseid = $courseids; // Course ID where the SCORM package will be uploaded
        $scormfile =$savedPath;// Path to the SCORM .zip file
        $zip = new ZipArchive;
        if ($zip->open($scormfile) === TRUE) {
            // Check if imsmanifest.xml exists in the ZIP archive
            if ($zip->locateName('imsmanifest.xml', ZipArchive::FL_NODIR) !== false) {
                $zip->close();
            } else {
                $zip->close();
                echo'imsmanifest.xml is missing from SCORM package.';
                return;
            }
        } else {
            echo 'Failed to open SCORM package.';
            return;
        }
        // Get course and context
        try{
            $course = get_course($courseid);
        }
        catch(exception $ex)
        {
            echo 'Course not found';
            return;
        }
        $context = context_course::instance($courseid);
        // Check permissions
        require_capability('mod/scorm:addinstance', $context);
        // Create SCORM instance (if needed)
        $scorm = new stdClass();
        $scorm->course = $courseid;
        $scorm->name = $scormname;
        $scorm->reference=$foldername . '.zip';
        $scorm->intro = 'Intro to SCORM';
        $scorm->maxattempt=0;
        $scorm->introformat = FORMAT_HTML;
        $scorm->timemodified = time();
        // Insert the SCORM instance into the database and get the instance ID
        $scorm->id = $DB->insert_record('scorm', $scorm);
        // Create a new course module record
        $cm = new stdClass();
        $cm->course = $courseid;
        $cm->module = $DB->get_field('modules', 'id', array('name' => 'scorm'));
        $cm->instance = $scorm->id;
        $cm->visible = 1;
        $cm->section = $section; // You can set the section if needed
        // Insert the course module
        $cm->id = add_course_module($cm); 
        $sectionid=course_add_cm_to_section($courseid,$cm->id,$cm->section);
        // Update the record
        $data = new stdClass();
        $data->id = $cm->id; 
        $data->section = $sectionid; 
        //Update the record in the course_modules table
        $DB->update_record('course_modules', $data);
        // Upload the SCORM package to Moodle file storage
        $fs = get_file_storage();
        $context = context_module::instance($cm->id);
        // Add the SCORM .zip package to the file area
        $fileinfo = array(
            'contextid' => $context->id,
            'component' => 'mod_scorm',
            'filearea'  => 'package',
            'itemid'    => 0, // Item ID (could be used to reference a specific instance of the package)
            'filepath'  => '/',
            'filename'  => $file. '.zip'
        );
        $file = $fs->create_file_from_pathname($fileinfo, $scormfile);
        
        $packer = get_file_packer('application/zip');
        if ($file) {
            $extracted_files = $file->extract_to_storage($packer,$context->id, 'mod_scorm', 'content', 0, '/');
        } else {
        }
        $fs = get_file_storage();
        // Locate the extracted directory in Moodle file storage (adjust as needed)
        $contextid = $context->id;  // The course/module context ID
        $component = 'mod_scorm';   // Change this to match your module (e.g., mod_scorm, mod_lti, etc.)
        $filearea = 'content';      // File area for SCORM or Common Cartridge
        $itemid = 0;                // Usually 0 unless specified
        $filename = 'imsmanifest.xml';
        // Get the manifest file
        $file = $fs->get_file($contextid, $component, $filearea, $itemid, '/', $filename);
        $manifest = $fs->get_file($context->id, 'mod_scorm', 'content', 0, '/', 'imsmanifest.xml');
        scorm_parse_scorm($scorm, $manifest);
        $lti_updated[] = array(
                                'scormid' => $scorm->id,
                                'sectionid' => $sectionid
                               );       
        return $lti_updated;
    }
    public static function insert_scorm_resource_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'scormid' => new external_value(PARAM_INT, 'new scorm id'),
                    'sectionid' => new external_value(PARAM_INT, 'new section id')
                )
            )
        );
    }
    public static function saveBase64ToZip($base64Data, $filename, $saveDir = 'uploads/')
    {
        // Make sure directory exists
        if (!file_exists($saveDir)) {
            mkdir($saveDir, 0777, true);
        }
        // Sanitize filename
        $safeFilename = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $filename);
        $zipPath = rtrim($saveDir, '/') . '/' . $safeFilename;
        // Decode base64
        $binaryData = base64_decode($base64Data);
        // Save the file
        if (file_put_contents($zipPath, $binaryData) !== false) {
            return realpath($zipPath); // return full path
        } else {
            return false;
        }
    }

}