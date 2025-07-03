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
 * Meta enrolment plugin settings and presets.
 *
 * @package    telconfig
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('localtelconfigsettings', get_string('pluginname', 'local_telconfig'));

    // Findwisesettings
    $settings->add(new admin_setting_heading('Findwisesettings',
        get_string('Findwisesettings', 'local_telconfig'), get_string('Findwisesettings_desc', 'local_telconfig')));

    $settings->add(new admin_setting_configtext('local_telconfig/findwiseindexurl',
        get_string('findwiseindexurl', 'local_telconfig'), get_string('findwiseindexurl_desc', 'local_telconfig'), '', PARAM_TEXT));
    $settings->add(new admin_setting_configtext('local_telconfig/findwiseindexmethod',
        get_string('findwiseindexmethod', 'local_telconfig'), get_string('findwiseindexmethod_desc', 'local_telconfig'), '', PARAM_TEXT));
    $settings->add(new admin_setting_configtext('local_telconfig/findwisecollection',
        get_string('findwisecollection', 'local_telconfig'), get_string('findwisecollection_desc', 'local_telconfig'), '', PARAM_TEXT));
    $settings->add(new admin_setting_configpasswordunmask('local_telconfig/findwiseapitoken',
        get_string('findwiseapitoken', 'local_telconfig'), get_string('findwiseapitoken_desc', 'local_telconfig'), '', PARAM_TEXT));

    //Mustache template settings
    $settings->add(new admin_setting_heading('mustachetemplatessettings',
        get_string('mustachetemplatessettings', 'local_telconfig'), get_string('mustachetemplatessettings_desc', 'local_telconfig')));

    $settings->add(new admin_setting_configtext('local_telconfig/mustachetemplatesurl',
        get_string('mustachetemplatesurl', 'local_telconfig'), get_string('mustachetemplatesurl_desc', 'local_telconfig'),  '', PARAM_TEXT));

    // content server settings
    $settings->add(new admin_setting_heading('contentserversettings',
        get_string('contentserversettings', 'local_telconfig'), get_string('contentserversettings_desc', 'local_telconfig')));

    $settings->add(new admin_setting_configtext('local_telconfig/contentserverurl',
        get_string('contentserverurl', 'local_telconfig'), get_string('contentserverurl_desc', 'local_telconfig'),  '', PARAM_TEXT));


    $ADMIN->add('localplugins', $settings);
}
