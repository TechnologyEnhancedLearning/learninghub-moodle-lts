<?php
// MUST be the first executable line
define('CLI_SCRIPT', true);

// Load Moodle config
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/scorm/lib.php');
require_once($CFG->dirroot . '/mod/scorm/locallib.php');

echo "Starting SCORM Ingestion PoC...\n";

// ------------------ CONFIG ------------------
//$scorm_source_path = 'C:/Dev/Moodle/SMT_01_008.zip'; // Path to SCORM ZIP local
// TODO: Remove the hard coded values
$UNC_path = '\\\\ukslhcontentstore.file.core.windows.net\\resourcesdev\\'; // UNC path to Azure file share
$directory_name = ''; // Directory name in Azure file share
$folder_to_zip = $UNC_path.$directory_name; 

//TODO: This value is store in moodle [mdl_scorm] [reference]
$temp_zip_path = sys_get_temp_dir() . '/scorm_' . uniqid() . '.zip'; 
// Zip the folder
$zip = new ZipArchive();
if ($zip->open($temp_zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    exit("Failed to create ZIP file.\n");
}
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($folder_to_zip, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);
foreach ($files as $file) {
    $file_path = $file->getRealPath();
    $relative_path = substr($file_path, strlen($folder_to_zip) + 1);
    $zip->addFile($file_path, $relative_path);
}
$zip->close();

$scorm_source_path = $temp_zip_path;

// TODO: Remove the hard coded values
$target_course_id = 5; // Course ID in Moodle
$scorm_name = 'PoC SCORM Module Azure Binon';
$scorm_description = 'SCORM package ingested programmatically.';
// --------------------------------------------

// 1. Check if file exists
if (!file_exists($scorm_source_path) || !is_readable($scorm_source_path)) {
    exit("SCORM ZIP not found or unreadable at: $scorm_source_path\n");
}

// 2. Get course and context
global $DB;
$course = $DB->get_record('course', ['id' => $target_course_id], '*', MUST_EXIST);
$context = context_course::instance($course->id);

// 3. Copy file to draft area
$USER = get_admin(); // Required for file ownership
$draftid = file_get_unused_draft_itemid();

$draftfile = [
    'contextid' => context_user::instance($USER->id)->id,
    'component' => 'user',
    'filearea'  => 'draft',
    'itemid'    => $draftid,
    'filepath'  => '/',
    'filename'  => basename($scorm_source_path)
];

$fs = get_file_storage();
$fs->create_file_from_pathname($draftfile, $scorm_source_path);

echo "Copied SCORM ZIP to draft file area.\n";

// 4. Prepare SCORM activity data
$formdata = new stdClass();
$formdata->course = $course->id;
$formdata->name = $scorm_name;
$formdata->intro = $scorm_description;
$formdata->introformat = FORMAT_HTML;
$formdata->section = 0;
$formdata->visible = 1;
$formdata->scormtype = 'local';
$formdata->packagefile = $draftid;
$formdata->width = 100;
$formdata->height = 500;
$formdata->popup = 0;
$formdata->browsemode = 0;
$formdata->skipview = 1;
$formdata->maxgrade = 100;

// Required for `add_moduleinfo`
$formdata->modulename = 'scorm';
$formdata->module = $DB->get_field('modules', 'id', ['name' => 'scorm'], MUST_EXIST);
$formdata->add = 'scorm';
$formdata->cmidnumber = '';
$formdata->groupmode = 0;
$formdata->groupingid = 0;

// 5. Add SCORM module to course
require_once($CFG->dirroot . '/course/modlib.php');
$moduleinfo = add_moduleinfo($formdata, $course, null);

if (!$moduleinfo || empty($moduleinfo->coursemodule)) {
    exit("Failed to create SCORM module.\n");
}

echo "SCORM module '{$scorm_name}' created in course '{$course->fullname}' (ID: {$course->id})\n";
echo "Access at: {$CFG->wwwroot}/mod/scorm/view.php?id={$moduleinfo->coursemodule}\n";

?>
