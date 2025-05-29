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
            require_once('../config.php');
            require_once($CFG->dirroot . '../tag/lib.php');
            $tags = \core_tag_tag::get_item_tags('core', 'course', $course->id);
            $keywords = array_map(fn($tag) => $tag->rawname, $tags);

            // Prepare data
            $data = [
                '_id' => 'M' . $course->id,
                'course_id' => $course->id,
                'authored_date' => date('Y-m-d', $course->startdate),
                'authors' => $authors,
                'catalogue_ids' => [$course->category],
                'description' => format_text($course->summary, FORMAT_HTML),
                'keywords' => array_values($keywords),            
                'location_paths' => [], // category hierarchy if needed
                'publication_date' => date('Y-m-d', $course->startdate),
                'rating' => 0,
                'resource_reference_id' => 0,
                'resource_type' => 'Moodle',
                'title' => $course->fullname,
             ];

             return $data;

        } catch (\Throwable $e) {
            debugging('Error in course_data_builder: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return []; // Always return an array
        }
    }
}
