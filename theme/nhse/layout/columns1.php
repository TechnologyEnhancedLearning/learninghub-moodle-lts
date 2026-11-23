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
 * A one column layout for the boost theme.
 *
 * @package   theme_boost
 * @copyright 2016 Damyon Wiese
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$bodyattributes = $OUTPUT->body_attributes([]);

// Manually inject the required class into the compiled string.
$required_class = 'nhsuk-frontend-supported';

if (strpos($bodyattributes, 'class="') !== false) {
    // If a class attribute already exists, insert the new class before the closing quote.
    $bodyattributes = str_replace('class="', 'class="' . $required_class . ' ', $bodyattributes);
} else {
    // If no class attribute exists, find the first attribute (e.g., id="...")
    // and insert the class attribute immediately after it.
    // This is a simple append for safety, assuming the body attributes start with id="...".
    $bodyattributes .= ' class="' . $required_class . '"';
}


$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
];

if (empty($PAGE->layout_options['noactivityheader'])) {
    $header = $PAGE->activityheader;
    $renderer = $PAGE->get_renderer('core');
    $templatecontext['headercontent'] = $header->export_for_template($renderer);
}

// FINAL FIX: Load our custom initialization module as an ES Module.
$init_url = new moodle_url($CFG->wwwroot . '/theme/nhse/javascript/nhsuk-init-module.js');

// This forces the necessary type="module" attribute and correctly loads the initializer.
echo '<script src="' . $init_url . '" type="module"></script>'; 

// Example of the final line that must follow:
// echo $OUTPUT->render_from_template('theme_nhse/drawers', $templatecontext);

echo $OUTPUT->render_from_template('theme_nhse/columns1', $templatecontext);

