<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Helper class locally used.
 *
 * @package    local_telconfig
 * @copyright 
 * @license    
 */

namespace local_telconfig;

defined('MOODLE_INTERNAL') || die();

class course_data_builder {
    public static function build_course_metadata($course): array {
        global $DB, $CFG;

        try {
           
            // Get course context and teachers (authors)
            $context = \context_course::instance($course->id);
            $teachers = get_role_users(3, $context); // 3 = editingteacher by default
            $authors = array_values(array_map(fn($u) => fullname($u), $teachers));

            // Extract tags
            require_once($CFG->dirroot . '/tag/lib.php');
            $tags = \core_tag_tag::get_item_tags('core', 'course', $course->id);
            $keywords = array_reduce($tags, function ($carry, $tag) {
                return array_merge($carry, self::tokenize_keywords($tag->rawname));
            }, []);


             // Merge in section and resource keywords
            $keywords = array_merge(
                $keywords,
                self::get_section_keywords($course),
                self::get_resource_keywords($course)
            );

            $keywords = array_values(array_unique($keywords));

            // Prepare data
            $data = [
                '_id' => 'M' . $course->id,
                'course_id' => $course->id,
                'authored_date' => date('Y-m-d', $course->startdate),
                'authors' => $authors,
                'catalogue_ids' => ['1'],//[$course->category],
                'description' => format_text($course->summary, FORMAT_HTML),
                'keywords' => array_values($keywords),            
                'location_paths' => [], // category hierarchy if needed
                'publication_date' => date('Y-m-d', $course->startdate),
                'rating' => 0,
                'resource_reference_id' => 0,
                'resource_type' => 'Course',
                'title' => $course->fullname,
             ];

             return $data;

        } catch (\Throwable $e) {
            debugging('Error in course_data_builder: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return []; // Always return an array
        }
    }

    private static function get_section_keywords($course): array {
        $keywords = [];
        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();

        foreach ($sections as $section) {
            if (!empty($section->name)) {
                $keywords = array_merge($keywords, self::tokenize_keywords($section->name));
            }
        }        

        return $keywords;
    }

    private static function get_resource_keywords($course): array {
        global $DB;
        $keywords = [];        
        $coursemodules = $DB->get_records('course_modules', ['course' => $course->id]);

        foreach ($coursemodules as $cm) {
            // Skip if module is marked for deletion
            if (!empty($cm->deletioninprogress)) {
                continue;
            }

            // Get module type (e.g., 'resource', 'quiz', etc.)
            $module = $DB->get_record('modules', ['id' => $cm->module], '*', IGNORE_MISSING);
            if (!$module) {
                continue;
            }

            // Dynamically get the module instance (e.g., from 'resource', 'quiz', etc.)
            $instancetable = $module->name;
            $instance = $DB->get_record($instancetable, ['id' => $cm->instance], '*', IGNORE_MISSING);
            if ($instance && !empty($instance->name)) {
                $keywords = array_merge($keywords, self::tokenize_keywords($instance->name));
            }
        }

        return $keywords;
    }

    private static function tokenize_keywords(string $input): array {
        $input = strtolower(trim($input));
        if (empty($input)) {
            return [];
        }

        $tokens = preg_split('/\s+/', $input); // split on spaces
        $keywords = array_merge([$input], $tokens); // include full phrase and individual words

        return array_unique($keywords); // remove duplicates
    }
}
