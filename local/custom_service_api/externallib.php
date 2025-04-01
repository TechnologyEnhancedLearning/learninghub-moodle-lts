<?php

use core_completion\progress;
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
                'file' => new external_value(PARAM_TEXT, 'file'),
                'path' => new external_value(PARAM_TEXT, 'path')
                 
            )
        );
    }
    public static function insert_scorm_resource($courseids,$section,$scormname,$file,$path) {
        global $DB,$CFG;
        require_once($CFG->libdir . '/filelib.php');
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->libdir . '/formslib.php');
        
        require_login();
        $courseid = $courseids; // Course ID where the SCORM package will be uploaded
        $scormname = $scormname;// // Name for the SCORM module
        $scormfile =$path . '' . $file. '.zip'; // Path to the SCORM .zip file

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
        $scorm->reference='Test Ref.zip';
        $scorm->intro = 'Intro to SCORM';
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
        $cm->id = add_course_module($cm); //$DB->insert_record('course_modules', $cm);

        $sectionid=course_add_cm_to_section($courseid,$cm->id,$cm->section);

        // Update the record
        $data = new stdClass();
        $data->id = $cm->id;  // The ID of the course module to update
        $data->section = $sectionid;  // The new section value

        // // Update the record in the course_modules table
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
        ;
        if ($file) {
            $extracted_files = $file->extract_to_storage($packer,$context->id, 'mod_scorm', 'content', 0, '/');
            echo "Extraction complete!";
        } else {
            echo "ZIP file not found.";
        }
       
        //new code for reading imsmanifest.xml
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
        

        
        $count = 0;
        $lti_updated = [
            'message'=>'Scorm package added to course',
            ];
                        

                        
               
        return $lti_updated;
    }
    public static function insert_scorm_resource_returns() {
        return new external_single_structure(
                array(
                   
                    'message'=> new external_value(PARAM_TEXT, 'success message'),
                    
                )
            );
    }





    

}