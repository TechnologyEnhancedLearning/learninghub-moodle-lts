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
 * @package   theme_nhsetel
 * @copyright NHS England
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new theme_boost_admin_settingspage_tabs('themesettingnhsetel', get_string('configtitle', 'theme_nhsetel'));
    $page = new admin_settingpage('theme_nhsetel_general', get_string('generalsettings', 'theme_nhsetel'));

     // Theme customisable property: Add the .NET Application Base URL setting to the 'General' tab ---
    $page->add(new admin_setting_configtext(
        'theme_nhsetel/dotnet_base_url', // Unique identifier for this setting
        get_string('dotnet_base_url_setting', 'theme_nhsetel'), // Display title
        get_string('dotnet_base_url_setting_desc', 'theme_nhsetel'), // Description for the setting
        '', // Default value (empty string)
        PARAM_URL // Moodle will validate the input as a URL
    ));

     // Theme customisable property: Add the API Base URL setting ---
    $page->add(new admin_setting_configtext(
        'theme_nhsetel/api_base_url', // Unique identifier for this setting
        get_string('api_base_url_setting', 'theme_nhsetel'), // Display title
        get_string('api_base_url_setting_desc', 'theme_nhsetel'), // Description
        '', // Default value (empty string)
        PARAM_URL // Moodle will validate this as a URL
    ));

         // Theme customisable property: Add the Admin URL setting ---
    $page->add(new admin_setting_configtext(
        'theme_nhsetel/admin_url', // Unique identifier for this setting
        get_string('admin_url_setting', 'theme_nhsetel'), // Display title
        get_string('admin_url_setting_desc', 'theme_nhsetel'), // Description
        '', // Default value (empty string)
        PARAM_URL // Moodle will validate this as a URL
    ));


    // Unaddable blocks.
    // Blocks to be excluded when this theme is enabled in the "Add a block" list: Administration, Navigation, Courses and
    // Section links.
    $default = 'navigation,settings,course_list,section_links';
    $setting = new admin_setting_configtext('theme_nhsetel/unaddableblocks',
        get_string('unaddableblocks', 'theme_nhsetel'), get_string('unaddableblocks_desc', 'theme_nhsetel'), $default, PARAM_TEXT);
    $page->add($setting);

    // Preset.
    $name = 'theme_nhsetel/preset';
    $title = get_string('preset', 'theme_nhsetel');
    $description = get_string('preset_desc', 'theme_nhsetel');
    $default = 'default.scss';

    $context = context_system::instance();
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'theme_nhsetel', 'preset', 0, 'itemid, filepath, filename', false);

    $choices = [];
    foreach ($files as $file) {
        $choices[$file->get_filename()] = $file->get_filename();
    }
    // These are the built in presets.
    $choices['default.scss'] = 'default.scss';
    $choices['plain.scss'] = 'plain.scss';

    $setting = new admin_setting_configthemepreset($name, $title, $description, $default, $choices, 'nhsetel');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Preset files setting.
    $name = 'theme_nhsetel/presetfiles';
    $title = get_string('presetfiles','theme_nhsetel');
    $description = get_string('presetfiles_desc', 'theme_nhsetel');

    $setting = new admin_setting_configstoredfile($name, $title, $description, 'preset', 0,
        array('maxfiles' => 20, 'accepted_types' => array('.scss')));
    $page->add($setting);

    // Background image setting.
    $name = 'theme_nhsetel/backgroundimage';
    $title = get_string('backgroundimage', 'theme_nhsetel');
    $description = get_string('backgroundimage_desc', 'theme_nhsetel');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'backgroundimage');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Login Background image setting.
    $name = 'theme_nhsetel/loginbackgroundimage';
    $title = get_string('loginbackgroundimage', 'theme_nhsetel');
    $description = get_string('loginbackgroundimage_desc', 'theme_nhsetel');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'loginbackgroundimage');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Variable $body-color.
    // We use an empty default value because the default colour should come from the preset.
    $name = 'theme_nhsetel/brandcolor';
    $title = get_string('brandcolor', 'theme_nhsetel');
    $description = get_string('brandcolor_desc', 'theme_nhsetel');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Must add the page after definiting all the settings!
    $settings->add($page);
     

      // SCORM Settings Tab
    $scormpage = new admin_settingpage('theme_nhsetel_scorm', get_string('scormsettings', 'theme_nhsetel'));
    $name = 'theme_nhsetel/scormfullscreenbutton';
    $title = get_string('scormfullscreenbutton', 'theme_nhsetel');
    $description = get_string('scormfullscreenbutton_desc', 'theme_nhsetel');
    $default = 1;
    $fullscreenbuttonsetting = new admin_setting_configcheckbox($name, $title, $description, $default);
    $scormpage->add($fullscreenbuttonsetting);
    $settings->add($scormpage);

    // Navigation Settings Tab
    $navpage = new admin_settingpage('theme_nhsetel_nav', get_string('navsettings', 'theme_nhsetel'));

    // --- My courses Link Settings ---
    $name = 'theme_nhsetel/mycourses_toggle';
    $title = get_string('mycourses_toggle', 'theme_nhsetel');
    $description = get_string('mycourses_toggle_desc', 'theme_nhsetel');
    $default = 0;
    $navpage->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $name = 'theme_nhsetel/mycourses_text';
    $title = get_string('mycourses_text', 'theme_nhsetel');
    $description = get_string('mycourses_text_desc', 'theme_nhsetel');
    $default = '';
    $navpage->add(new admin_setting_configtext($name, $title, $description, $default));

    // --- Calendar Link Settings ---
    $name = 'theme_nhsetel/calendar_toggle';
    $title = get_string('calendar_toggle', 'theme_nhsetel');
    $description = get_string('calendar_toggle_desc', 'theme_nhsetel');
    $default = 0;
    $navpage->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $name = 'theme_nhsetel/calendar_text';
    $title = get_string('calendar_text', 'theme_nhsetel');
    $description = get_string('calendar_text_desc', 'theme_nhsetel');
    $default = '';
    $navpage->add(new admin_setting_configtext($name, $title, $description, $default));

    // Add the page to the settings
    $settings->add($navpage);

    // Advanced settings.
    $page = new admin_settingpage('theme_nhsetel_advanced', get_string('advancedsettings', 'theme_nhsetel'));

    // Raw SCSS to include before the content.
    $setting = new admin_setting_scsscode('theme_nhsetel/scsspre',
        get_string('rawscsspre', 'theme_nhsetel'), get_string('rawscsspre_desc', 'theme_nhsetel'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Raw SCSS to include after the content.
    $setting = new admin_setting_scsscode('theme_nhsetel/scss', get_string('rawscss', 'theme_nhsetel'),
        get_string('rawscss_desc', 'theme_nhsetel'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Custom Login Settings
    $name = 'theme_nhsetel/login_page_toggle';
    $title = get_string('login_page_toggle_title', 'theme_nhsetel');
    $description = get_string('login_page_toggle_desc', 'theme_nhsetel');
    $default = 0;
    $choices = [
        'no',
        'yes',
    ];
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Force hide OAuth button icon
    $name = 'theme_nhsetel/oauth_login_button_icon';
    $title = get_string('login_page_oauth_button_icon_title', 'theme_nhsetel');
    $description = get_string('login_page_oauth_button_icon_desc', 'theme_nhsetel');
    $default = 0;
    $choices = [
        0 => 'disable icon',
        1 => 'enable icon'
    ];

    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Login header text settings
    $name = 'theme_nhsetel/login_header_text';
    $title = get_string('login_header_title', 'theme_nhsetel');
    $description = get_string('login_header_description', 'theme_nhsetel');
    $default = get_string('login_header_text_default', 'theme_nhsetel');
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $page->add($setting);

    // Login box title
    $name = 'theme_nhsetel/login_expand_text';
    $title = get_string('login_expand_title', 'theme_nhsetel');
    $description = get_string('login_expand_description', 'theme_nhsetel');
    $default = get_string('login_expand_text_default', 'theme_nhsetel');
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $page->add($setting);

    $settings->add($page);
}
