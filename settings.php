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
 * Admin settings for local_branchupload.
 *
 * @package    local_branchupload
 * @author     Christopher Reimann <christopher.reimann@eledia.de>
 * @copyright  2026 eLeDia GmbH, Berlin {@link https://eledia.de}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_branchupload_settings',
        get_string('pluginname', 'local_branchupload')
    );

    // Branch profile field — determines which Behörde/branch the uploader belongs to.
    $settings->add(new admin_setting_configselect(
        'local_branchupload/branchfield',
        get_string('setting_branchfield', 'local_branchupload'),
        get_string('setting_branchfield_desc', 'local_branchupload'),
        '',
        function () {
            global $CFG;
            require_once($CFG->dirroot . '/user/profile/lib.php');
            $choices = ['' => get_string('choose')];
            foreach (profile_get_custom_fields() as $field) {
                $choices[$field->shortname] = $field->name;
            }
            return $choices;
        }
    ));

    // Organisationseinheit profile field — where the CSV column value is stored.
    $settings->add(new admin_setting_configselect(
        'local_branchupload/orgunitfield',
        get_string('setting_orgunitfield', 'local_branchupload'),
        get_string('setting_orgunitfield_desc', 'local_branchupload'),
        '',
        function () {
            global $CFG;
            require_once($CFG->dirroot . '/user/profile/lib.php');
            $choices = ['' => get_string('choose')];
            foreach (profile_get_custom_fields() as $field) {
                $choices[$field->shortname] = $field->name;
            }
            return $choices;
        }
    ));

    // Auto-create cohorts if they don't exist.
    $settings->add(new admin_setting_configcheckbox(
        'local_branchupload/autocreate_cohorts',
        get_string('setting_autocreate', 'local_branchupload'),
        get_string('setting_autocreate_desc', 'local_branchupload'),
        0
    ));

    // Delete action — suspend or truly delete.
    $settings->add(new admin_setting_configselect(
        'local_branchupload/deleteaction',
        get_string('setting_deleteaction', 'local_branchupload'),
        get_string('setting_deleteaction_desc', 'local_branchupload'),
        'suspend',
        [
            'suspend' => get_string('setting_deleteaction_suspend', 'local_branchupload'),
            'delete' => get_string('setting_deleteaction_delete', 'local_branchupload'),
        ]
    ));

    // Maximum users per upload.
    $settings->add(new admin_setting_configtext(
        'local_branchupload/maxusers',
        get_string('setting_maxusers', 'local_branchupload'),
        get_string('setting_maxusers_desc', 'local_branchupload'),
        500,
        PARAM_INT
    ));

    // CSV column headers — every header can be renamed to match the
    // uploaded file. Empty values fall back to the site-language default
    // resolved via column_config::default_for_key().
    $settings->add(new admin_setting_heading(
        'local_branchupload/columns_heading',
        get_string('setting_columns_heading', 'local_branchupload'),
        get_string('setting_columns_heading_desc', 'local_branchupload')
    ));

    foreach (\local_branchupload\column_config::canonical_keys() as $colkey) {
        $coldefault = \local_branchupload\column_config::default_for_key($colkey);
        $settings->add(new admin_setting_configtext(
            'local_branchupload/col_' . $colkey,
            get_string('setting_col_' . $colkey, 'local_branchupload'),
            get_string('setting_col_' . $colkey . '_desc', 'local_branchupload', $coldefault),
            $coldefault,
            PARAM_RAW_TRIMMED
        ));
    }

    $ADMIN->add('localplugins', $settings);

    // External page for the upload UI so it appears in admin navigation.
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_branchupload_upload',
        get_string('uploadusers', 'local_branchupload'),
        new moodle_url('/local/branchupload/index.php'),
        'local/branchupload:upload'
    ));
}
