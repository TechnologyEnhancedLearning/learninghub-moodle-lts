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
 * Theme functions.
 *
 * @package   theme_nhsetel
 * @copyright NHS England
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Inject additional SCSS.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_nhsetel_get_extra_scss($theme) {
    // Load the settings from the parent.
    $theme = theme_config::load('boost');

    // Call the parent themes get_extra_scss function.
    return theme_boost_get_extra_scss($theme);
}

/**
 * Returns the main SCSS content.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_nhsetel_get_main_scss_content($theme) {
    global $CFG;
    $scss = '';

    /**
     * Load variable overload for bootstrap
     */
//    $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/fontawesome.scss');
//    $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/bootstrap.scss');
//    $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/moodle.scss');

    // Get main theme file and combine them together.
    $scss .= "\n" . file_get_contents($CFG->dirroot . '/theme/nhsetel/css/nhsetel.min.css');
    //$scss .= "\n" . file_get_contents($CFG->dirroot . '/theme/nhsetel/scss/nhsuk.scss');
    //$scss .= "\n" . file_get_contents($CFG->dirroot . '/theme/nhsetel/scss/nhsetel.scss');

    return $scss;
}