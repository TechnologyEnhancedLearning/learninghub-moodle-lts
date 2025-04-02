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