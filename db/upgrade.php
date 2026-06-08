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
 * Upgrade script for local_branchupload.
 *
 * @package    local_branchupload
 * @author     Christopher Reimann <christopher.reimann@eledia.de>
 * @copyright  2026 eLeDia GmbH, Berlin {@link https://eledia.de}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Run upgrade steps for local_branchupload.
 *
 * @param int $oldversion The previous installed version.
 * @return bool
 */
function xmldb_local_branchupload_upgrade(int $oldversion): bool {

    if ($oldversion < 2026060803) {
        // 1.4.0 — canonical column-config keys were renamed from the original
        // German vocabulary to English. Migrate any admin overrides stored
        // under the old config names so existing installations keep working
        // without manual reconfiguration.
        $renames = [
            'col_behoerde'    => 'col_branch',
            'col_orgeinheit'  => 'col_orgunit',
            'col_name'        => 'col_lastname',
            'col_vorname'     => 'col_firstname',
            'col_loeschen'    => 'col_remove',
            'col_kohorten'    => 'col_cohorts',
            'col_alte_email'  => 'col_oldemail',
            // col_email kept its name; no migration needed.
        ];

        foreach ($renames as $oldname => $newname) {
            $value = get_config('local_branchupload', $oldname);
            if ($value !== false && $value !== '') {
                // Preserve the admin's existing override under the new name.
                set_config($newname, $value, 'local_branchupload');
            }
            // Remove the obsolete config row regardless of whether it had
            // a value, so the settings page does not show a stale entry.
            unset_config($oldname, 'local_branchupload');
        }

        upgrade_plugin_savepoint(true, 2026060803, 'local', 'branchupload');
    }

    return true;
}
