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
 * Renderer for use with the course section and all the goodness that falls
 * within it.
 *
 * This renderer should contain methods useful to courses, and categories.
 *
 * @package   moodlecore
 * @copyright 2010 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_nhse\output\core;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use html_writer;
use get_string;

/**
 * The core course renderer
 *
 * Can be retrieved with the following:
 * $renderer = $PAGE->get_renderer('core','course');
 */
class course_renderer extends \core_course_renderer
{
    /**
     * Returns HTML to display course overview files.
     *
     * @param  \core_course_list_element  $course
     *
     * @return string
     */
    public function course_overview_files(\core_course_list_element $course): string {
        global $CFG;

        $contentimages = $contentfiles = '';
        foreach ($course->get_course_overviewfiles() as $file) {
            $isimage = $file->is_valid_image();
            $url = moodle_url::make_file_url("$CFG->wwwroot/pluginfile.php",
                '/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
                $file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
            if ($isimage) {
                $contentimages .= html_writer::tag('div',
                    html_writer::empty_tag('img', ['src' => $url, 'alt' => $course->get_formatted_name()]),
                    ['class' => 'courseimage']);
            } else {
                $image = $this->output->pix_icon(file_file_icon($file), $file->get_filename(), 'moodle');
                $filename = html_writer::tag('span', $image, ['class' => 'fp-icon']).
                            html_writer::tag('span', $file->get_filename(), ['class' => 'fp-filename']);
                $contentfiles .= html_writer::tag('span',
                    html_writer::link($url, $filename),
                    ['class' => 'coursefile fp-filename-icon text-break']);
            }
        }

        return $contentimages . $contentfiles;
    }
}
