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
 * English language strings for local_branchupload.
 *
 * Strings are kept in alphabetical order, as required by the Moodle
 * coding style and enforced by moodle-plugin-ci.
 *
 * @package    local_branchupload
 * @author     Christopher Reimann <christopher.reimann@eledia.de>
 * @copyright  2026 eLeDia GmbH, Berlin {@link https://eledia.de}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['branchupload:upload'] = 'Upload branch-office user lists via CSV';
$string['cohort_created'] = 'Cohort "{$a}" created.';
$string['col_default_branch'] = 'Branch';
$string['col_default_cohorts'] = 'Cohorts';
$string['col_default_email'] = 'Email';
$string['col_default_firstname'] = 'FirstName';
$string['col_default_lastname'] = 'LastName';
$string['col_default_oldemail'] = 'OldEmail';
$string['col_default_orgunit'] = 'OrgUnit';
$string['col_default_remove'] = 'Remove';
$string['csvcolumns_info'] = 'Required columns: {$a->email}, {$a->branch}, {$a->orgunit}, {$a->lastname}, {$a->firstname}. Optional columns: {$a->remove} (1 = remove user), {$a->cohorts} (additional cohorts, pipe-separated, e.g. CohortA|CohortB), {$a->oldemail} (previous email address when renaming a user). Column headers can be customised in the plugin settings.';
$string['csvdelimiter'] = 'CSV delimiter';
$string['csvfile'] = 'CSV file';
$string['csvfile_help'] = 'Upload a CSV file with user data. The required and optional column headers are listed in the info box below and can be customised in the plugin settings.';
$string['encoding'] = 'Encoding';
$string['error_branchmismatch'] = 'Branch mismatch: row has "{$a->rowbranch}" but your branch is "{$a->userbranch}".';
$string['error_cohort_is_branch'] = 'Cohort "{$a}" represents a branch office and cannot be assigned via the cohorts column.';
$string['error_crossbranch_update'] = 'Cannot update user "{$a->username}": user belongs to branch "{$a->userbranch}", not your branch "{$a->uploaderbranch}".';
$string['error_csvparse'] = 'Error parsing CSV file. Please check the format and encoding.';
$string['error_email_conflict'] = 'The new email address "{$a}" is already used by another user.';
$string['error_emptycsv'] = 'The CSV file is empty or contains only headers.';
$string['error_invalidemail'] = 'Invalid email address: {$a}';
$string['error_invalidoldemail'] = 'Invalid previous email address: {$a}';
$string['error_maxusers'] = 'The CSV contains {$a->count} rows, but the maximum is {$a->max}. Please split the file.';
$string['error_missingbranch'] = 'Branch is required.';
$string['error_missingcolumns'] = 'Missing required columns: {$a}';
$string['error_missingemail'] = 'Email is required.';
$string['error_missingfirstname'] = 'First name is required.';
$string['error_missinglastname'] = 'Last name is required.';
$string['error_missingorgunit'] = 'Organisational unit is required.';
$string['error_no_branch_value'] = 'Your user account does not have a branch value set. Please contact an administrator.';
$string['error_noconfigured_branchfield'] = 'The branch profile field has not been configured. Please contact an administrator.';
$string['error_noconfigured_orgunitfield'] = 'The organisational unit profile field has not been configured. Please contact an administrator.';
$string['error_unknowncohort'] = 'Cohort "{$a}" does not exist and auto-creation is disabled.';
$string['examplecsv'] = 'Example CSV file';
$string['examplecsv_help'] = 'Download this file as a template for your user upload.';
$string['header_details'] = 'Details';
$string['header_rownumber'] = 'Row number';
$string['header_status'] = 'Status';
$string['pluginname'] = 'Branch office user upload';
$string['preview_admin_mode'] = 'Admin mode — branch restrictions are not applied.';
$string['preview_branch_locked'] = 'Your branch: <strong>{$a}</strong> — all uploaded users will be assigned to this branch.';
$string['preview_cancel'] = 'Cancel';
$string['preview_confirm'] = 'Confirm upload';
$string['preview_summary'] = '{$a->total} rows found: {$a->valid} valid, {$a->errors} errors, {$a->warnings} warnings.';
$string['preview_title'] = 'Upload preview';
$string['previewstep'] = 'Preview';
$string['privacy:metadata'] = 'The branch office user upload plugin does not store any personal data itself. It uses Moodle core APIs (user_create_user, user_update_user, delete_user, profile_save_data, cohort_add_member) to manage users; those subsystems handle their own privacy compliance. Uploaded CSV files are processed in memory via Moodle\'s csv_import_reader temporary storage and are purged after processing.';
$string['result_created'] = 'Created';
$string['result_deleted'] = 'Deleted';
$string['result_error'] = 'Error';
$string['result_skipped'] = 'Skipped';
$string['result_suspended'] = 'Suspended';
$string['result_updated'] = 'Updated';
$string['results_back'] = 'Upload another file';
$string['results_created'] = 'Users created';
$string['results_deleted'] = 'Users deleted';
$string['results_errors'] = 'Errors';
$string['results_skipped'] = 'Rows skipped';
$string['results_summary'] = 'Processing complete.';
$string['results_suspended'] = 'Users suspended';
$string['results_title'] = 'Upload results';
$string['results_updated'] = 'Users updated';
$string['resultstep'] = 'Results';
$string['setting_autocreate'] = 'Auto-create cohorts';
$string['setting_autocreate_desc'] = 'If enabled, cohorts that are referenced in the CSV but do not yet exist will be created automatically. Otherwise, rows with unknown cohorts are rejected.';
$string['setting_branchfield'] = 'Branch profile field';
$string['setting_branchfield_desc'] = 'The custom user profile field that identifies which branch office a user belongs to. Non-admin uploaders can only upload users for their own branch.';
$string['setting_col_branch'] = 'CSV column: Branch';
$string['setting_col_branch_desc'] = 'CSV header for the branch column. Must match a cohort idnumber. Default for the site language: {$a}.';
$string['setting_col_cohorts'] = 'CSV column: Additional cohorts (optional)';
$string['setting_col_cohorts_desc'] = 'Optional CSV header for the additional-cohorts column. Values are pipe-separated cohort idnumbers (e.g. CohortA|CohortB). Default for the site language: {$a}.';
$string['setting_col_email'] = 'CSV column: Email';
$string['setting_col_email_desc'] = 'CSV header for the e-mail address column (also used as the Moodle username). Default for the site language: {$a}.';
$string['setting_col_firstname'] = 'CSV column: First name';
$string['setting_col_firstname_desc'] = 'CSV header for the first-name column. Default for the site language: {$a}.';
$string['setting_col_lastname'] = 'CSV column: Last name';
$string['setting_col_lastname_desc'] = 'CSV header for the last-name column. Default for the site language: {$a}.';
$string['setting_col_oldemail'] = 'CSV column: Previous email (optional)';
$string['setting_col_oldemail_desc'] = 'Optional CSV header used to rename a user. When this column contains a value, the row updates the existing user identified by the previous e-mail and sets their new e-mail/username from the main email column. Default for the site language: {$a}.';
$string['setting_col_orgunit'] = 'CSV column: Organisational unit';
$string['setting_col_orgunit_desc'] = 'CSV header for the organisational-unit column. Default for the site language: {$a}.';
$string['setting_col_remove'] = 'CSV column: Removal flag (optional)';
$string['setting_col_remove_desc'] = 'Optional CSV header used to mark a user for removal. Rows where this column contains 1, ja, yes or true are suspended or deleted according to the Delete action setting. Default for the site language: {$a}.';
$string['setting_columns_heading'] = 'CSV column headers';
$string['setting_columns_heading_desc'] = 'Rename the CSV column headers to match your uploaded files. Header matching is case-insensitive and trim-insensitive. Leaving a field empty restores the default value for the configured site language ($CFG->lang). Changes here affect the upload form info text, the preview/results tables, the example-CSV download and the column validation rules — all in one place.';
$string['setting_deleteaction'] = 'Delete action';
$string['setting_deleteaction_delete'] = 'Permanently delete user account';
$string['setting_deleteaction_desc'] = 'What happens when a row has the removal column set to 1. "Suspend" disables the account, "Delete" permanently removes it.';
$string['setting_deleteaction_suspend'] = 'Suspend user account';
$string['setting_maxusers'] = 'Maximum users per upload';
$string['setting_maxusers_desc'] = 'The maximum number of rows allowed in a single CSV upload. Set to 0 for no limit.';
$string['setting_orgunitfield'] = 'Organisational unit profile field';
$string['setting_orgunitfield_desc'] = 'The custom user profile field where the organisational unit from the CSV is stored.';
$string['status_create'] = 'Will be created';
$string['status_delete'] = 'Will be deleted';
$string['status_ok'] = 'OK';
$string['status_skip'] = 'Skipped';
$string['status_suspend'] = 'Will be suspended';
$string['status_update'] = 'Will be updated';
$string['step_completed'] = 'Completed step';
$string['step_current'] = 'Current step';
$string['step_indicator_label'] = 'Upload progress';
$string['step_upcoming'] = 'Upcoming step';
$string['summary_total'] = 'Total';
$string['summary_warnings'] = 'Warnings';
$string['uploadstep'] = 'Upload CSV';
$string['uploadusers'] = 'Upload branch users';
