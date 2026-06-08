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
 * CSV processing engine for local_branchupload.
 *
 * Handles CSV validation, branch enforcement, user creation/update/suspend/delete.
 *
 * @package    local_branchupload
 * @author     Christopher Reimann <christopher.reimann@eledia.de>
 * @copyright  2026 eLeDia GmbH, Berlin {@link https://eledia.de}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_branchupload;

/**
 * Processes CSV uploads for branch office user management.
 */
class process {
    /**
     * Ensure required libraries are loaded.
     */
    private static function require_libs(): void {
        global $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');
        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/cohort/lib.php');
    }

    /** @var \csv_import_reader The CSV reader instance. */
    private \csv_import_reader $cir;

    /** @var string[] The CSV column headers (original case). */
    private array $columns;

    /** @var column_config Resolves canonical keys to configured CSV header strings. */
    private column_config $columnconfig;

    /** @var string The branch profile field shortname. */
    private string $branchfield;

    /** @var string The org unit profile field shortname. */
    private string $orgunitfield;

    /** @var bool Whether to auto-create missing cohorts. */
    private bool $autocreatecohorts;

    /** @var string Delete action: 'suspend' or 'delete'. */
    private string $deleteaction;

    /** @var int Maximum users per upload (0 = unlimited). */
    private int $maxusers;

    /** @var string|null The uploader's branch value (null for admins in admin mode). */
    private ?string $uploaderbranch;

    /** @var bool Whether the uploader is a site admin. */
    private bool $isadmin;

    /** @var array Cached cohort lookups keyed by idnumber. */
    private array $cohortcache = [];

    /** @var array Known branch cohort idnumbers (values used in the branch profile field). */
    private array $branchcohorts = [];

    /**
     * Constructor.
     *
     * @param \csv_import_reader $cir The CSV import reader with loaded data.
     * @param string[] $columns The parsed column headers.
     * @param column_config|null $columnconfig Optional pre-built column config (injection seam for tests).
     */
    public function __construct(
        \csv_import_reader $cir,
        array $columns,
        ?column_config $columnconfig = null
    ) {
        global $USER;

        self::require_libs();

        $this->cir = $cir;
        $this->columns = $columns;
        $this->columnconfig = $columnconfig ?? new column_config();

        $this->branchfield = get_config('local_branchupload', 'branchfield');
        $this->orgunitfield = get_config('local_branchupload', 'orgunitfield');
        $this->autocreatecohorts = (bool) get_config('local_branchupload', 'autocreate_cohorts');
        $this->deleteaction = get_config('local_branchupload', 'deleteaction') ?: 'suspend';
        $this->maxusers = (int) get_config('local_branchupload', 'maxusers');

        $this->isadmin = is_siteadmin();

        // Determine the uploader's branch.
        if (!$this->isadmin && !empty($this->branchfield)) {
            $profiledata = profile_user_record($USER->id, false);
            $this->uploaderbranch = $profiledata->{$this->branchfield} ?? null;
        } else {
            $this->uploaderbranch = null;
        }

        // Build list of known branch cohort idnumbers to protect against misuse via Kohorten column.
        if (!$this->isadmin && !empty($this->branchfield)) {
            $this->branchcohorts = $this->load_branch_cohort_ids();
        }
    }

    /**
     * Validate that the plugin is correctly configured.
     *
     * @return string[] Array of error strings. Empty if configuration is valid.
     */
    public function validate_configuration(): array {
        $errors = [];

        if (empty($this->branchfield)) {
            $errors[] = get_string('error_noconfigured_branchfield', 'local_branchupload');
        }
        if (empty($this->orgunitfield)) {
            $errors[] = get_string('error_noconfigured_orgunitfield', 'local_branchupload');
        }
        if (!$this->isadmin && empty($this->uploaderbranch)) {
            $errors[] = get_string('error_no_branch_value', 'local_branchupload');
        }

        return $errors;
    }

    /**
     * Validate that required CSV columns are present.
     *
     * The required column names are taken from {@see column_config}, so an
     * administrator who renamed (for example) the "Behörde" header to
     * "Branch_Office" will see "Branch_Office" in the error message.
     *
     * @return string[] Array of error strings. Empty if columns are valid.
     */
    public function validate_columns(): array {
        $errors = [];
        $missingkeys = $this->columnconfig->missing_required($this->columns);
        if (!empty($missingkeys)) {
            $missingheaders = [];
            foreach ($missingkeys as $key) {
                $missingheaders[] = $this->columnconfig->header($key);
            }
            $errors[] = get_string(
                'error_missingcolumns',
                'local_branchupload',
                implode(', ', $missingheaders)
            );
        }

        return $errors;
    }

    /**
     * Build a column index mapping canonical key => column position.
     *
     * @return array<string,int|null> Canonical key => 0-based column index, or null if absent.
     */
    private function build_column_index(): array {
        return $this->columnconfig->build_index($this->columns);
    }

    /**
     * Preview all CSV rows without making changes.
     *
     * @return array{rows: array, summary: array} Preview data for template rendering.
     */
    public function preview(): array {
        global $DB;

        $colindex = $this->build_column_index();
        $rows = [];
        $summary = ['total' => 0, 'valid' => 0, 'errors' => 0, 'warnings' => 0];

        $this->cir->init();
        while ($line = $this->cir->next()) {
            $summary['total']++;
            $row = $this->parse_line($line, $colindex);
            $row['rownumber'] = $summary['total'];
            $row = $this->validate_row($row);

            if (!empty($row['errors'])) {
                $summary['errors']++;
                $row['haserrors'] = true;
            } else if (!empty($row['warnings'])) {
                $summary['warnings']++;
                $row['haswarnings'] = true;
            } else {
                $summary['valid']++;
            }

            $rows[] = $row;
        }

        $this->cir->close();

        return ['rows' => $rows, 'summary' => $summary];
    }

    /**
     * Process all CSV rows — create, update, suspend, or delete users.
     *
     * @return array{rows: array, stats: array} Results data for template rendering.
     */
    public function execute(): array {
        global $DB;

        $colindex = $this->build_column_index();
        $results = [];
        $stats = [
            'created' => 0,
            'updated' => 0,
            'suspended' => 0,
            'deleted' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $this->cir->init();
        $rownumber = 0;
        while ($line = $this->cir->next()) {
            $rownumber++;
            $row = $this->parse_line($line, $colindex);
            $row['rownumber'] = $rownumber;
            $row = $this->validate_row($row);

            if (!empty($row['errors'])) {
                $stats['errors']++;
                $row['status'] = 'error';
                $row['statusmessage'] = implode('; ', $row['errors']);
                $results[] = $row;
                continue;
            }

            try {
                $result = $this->process_row($row);
                $stats[$result['action']]++;
                $row['status'] = $result['action'];
                $row['statusmessage'] = $result['message'];
                if (!empty($result['info'])) {
                    $row['info'] = $result['info'];
                }
            } catch (\Throwable $e) {
                $stats['errors']++;
                $row['status'] = 'error';
                // Surface a generic error in the UI; log full details for admins/dev mode.
                $row['statusmessage'] = get_string('result_error', 'local_branchupload');
                debugging(
                    'local_branchupload: error processing row ' . $rownumber . ': ' . $e->getMessage(),
                    DEBUG_DEVELOPER
                );
            }

            $results[] = $row;
        }

        $this->cir->close();
        $this->cir->cleanup();

        return ['rows' => $results, 'stats' => $stats];
    }

    /**
     * Parse a single CSV line into an associative row array.
     *
     * Reads each value via the canonical-key => column-index map produced by
     * {@see column_config::build_index()}, so configured (renamed) column
     * headers work transparently.
     *
     * @param array $line The raw CSV line values.
     * @param array<string,int|null> $colindex Canonical key => 0-based column index, or null.
     * @return array Parsed row with named keys.
     */
    private function parse_line(array $line, array $colindex): array {
        $value = function (string $key) use ($colindex, $line): string {
            $i = $colindex[$key] ?? null;
            return $i !== null ? trim((string) ($line[$i] ?? '')) : '';
        };

        return [
            'email'     => $value('email'),
            'branch'    => $value('branch'),
            'orgunit'   => $value('orgunit'),
            'lastname'  => $value('lastname'),
            'firstname' => $value('firstname'),
            'remove'    => $value('remove'),
            'cohorts'   => $value('cohorts'),
            'oldemail'  => $value('oldemail'),
            'errors'    => [],
            'warnings'  => [],
        ];
    }

    /**
     * Validate a parsed row.
     *
     * @param array $row The parsed row.
     * @return array The row with errors/warnings populated.
     */
    private function validate_row(array $row): array {
        global $DB;

        // Required field checks.
        if (empty($row['email'])) {
            $row['errors'][] = get_string('error_missingemail', 'local_branchupload');
        } else if (!validate_email($row['email'])) {
            $row['errors'][] = get_string('error_invalidemail', 'local_branchupload', $row['email']);
        }

        if (empty($row['lastname'])) {
            $row['errors'][] = get_string('error_missinglastname', 'local_branchupload');
        }
        if (empty($row['firstname'])) {
            $row['errors'][] = get_string('error_missingfirstname', 'local_branchupload');
        }
        if (empty($row['branch'])) {
            $row['errors'][] = get_string('error_missingbranch', 'local_branchupload');
        }
        if (empty($row['orgunit'])) {
            $row['errors'][] = get_string('error_missingorgunit', 'local_branchupload');
        }

        // Validate oldemail if provided (for email change).
        if (!empty($row['oldemail'])) {
            if (!validate_email($row['oldemail'])) {
                $row['errors'][] = get_string('error_invalidoldemail', 'local_branchupload', $row['oldemail']);
            }
        }

        // Branch enforcement for non-admins.
        if (!$this->isadmin && !empty($row['branch']) && !empty($this->uploaderbranch)) {
            if ($row['branch'] !== $this->uploaderbranch) {
                $row['errors'][] = get_string('error_branchmismatch', 'local_branchupload', (object) [
                    'rowbranch' => $row['branch'],
                    'userbranch' => $this->uploaderbranch,
                ]);
            }
        }

        // Cohort existence check for branch.
        if (!empty($row['branch'])) {
            $this->validate_cohort($row['branch'], $row);
        }

        // Cohort existence check for additional cohorts.
        if (!empty($row['cohorts'])) {
            $cohortids = array_filter(array_map('trim', explode('|', $row['cohorts'])));
            foreach ($cohortids as $cohortid) {
                // Block non-admins from assigning branch cohorts via the cohorts column.
                if (!$this->isadmin && in_array($cohortid, $this->branchcohorts)) {
                    $row['errors'][] = get_string('error_cohort_is_branch', 'local_branchupload', $cohortid);
                    continue;
                }
                $this->validate_cohort($cohortid, $row);
            }
        }

        // Determine the lookup username — use oldemail if provided (email change scenario).
        $lookupusername = !empty($row['oldemail'])
            ? $this->email_to_username($row['oldemail'])
            : $this->email_to_username($row['email']);

        // Cross-branch update check for non-admins.
        if (!$this->isadmin && !empty($row['email']) && !empty($this->uploaderbranch) && empty($row['errors'])) {
            $existinguser = $DB->get_record('user', ['username' => $lookupusername, 'deleted' => 0]);
            if ($existinguser) {
                $existingprofile = profile_user_record($existinguser->id, false);
                $existingbranch = $existingprofile->{$this->branchfield} ?? '';
                if (!empty($existingbranch) && $existingbranch !== $this->uploaderbranch) {
                    $row['errors'][] = get_string('error_crossbranch_update', 'local_branchupload', (object) [
                        'username' => $lookupusername,
                        'userbranch' => $existingbranch,
                        'uploaderbranch' => $this->uploaderbranch,
                    ]);
                }
                $row['existing'] = true;
            }
        }

        // Check for email conflict when changing email.
        if (!empty($row['oldemail']) && empty($row['errors'])) {
            $newusername = $this->email_to_username($row['email']);
            $conflictuser = $DB->get_record('user', ['username' => $newusername, 'deleted' => 0]);
            if ($conflictuser) {
                // Only a conflict if it's a different user than the one we're updating.
                $existinguser = $DB->get_record('user', ['username' => $lookupusername, 'deleted' => 0]);
                if (!$existinguser || $conflictuser->id !== $existinguser->id) {
                    $row['errors'][] = get_string('error_email_conflict', 'local_branchupload', $row['email']);
                }
            }
        }

        // Determine action for preview display.
        if (empty($row['errors'])) {
            $isdelete = $this->is_delete_row($row);
            if ($isdelete) {
                $row['action'] = ($this->deleteaction === 'delete') ? 'delete' : 'suspend';
            } else {
                $exists = !empty($row['existing']) || $this->user_exists($lookupusername);
                $row['action'] = $exists ? 'update' : 'create';
            }
        }

        return $row;
    }

    /**
     * Validate that a cohort exists or can be auto-created.
     *
     * @param string $idnumber The cohort idnumber to check.
     * @param array $row The row (modified by reference to add errors).
     */
    private function validate_cohort(string $idnumber, array &$row): void {
        if (isset($this->cohortcache[$idnumber])) {
            return;
        }

        global $DB;
        $cohort = $DB->get_record('cohort', ['idnumber' => $idnumber]);
        if ($cohort) {
            $this->cohortcache[$idnumber] = $cohort;
        } else if ($this->autocreatecohorts) {
            // Will be auto-created during processing.
            $this->cohortcache[$idnumber] = null;
        } else {
            $row['errors'][] = get_string('error_unknowncohort', 'local_branchupload', $idnumber);
        }
    }

    /**
     * Process a single validated row — create, update, suspend, or delete the user.
     *
     * @param array $row The validated row data.
     * @return array{action: string, message: string, info?: string} The result.
     */
    private function process_row(array $row): array {
        global $DB;

        // Use oldemail for lookup if provided (email change scenario).
        $lookupusername = !empty($row['oldemail'])
            ? $this->email_to_username($row['oldemail'])
            : $this->email_to_username($row['email']);
        $isdelete = $this->is_delete_row($row);

        // Look up existing user.
        $existinguser = $DB->get_record('user', ['username' => $lookupusername, 'deleted' => 0]);

        // Handle delete/suspend.
        if ($isdelete) {
            if (!$existinguser) {
                return ['action' => 'skipped', 'message' => get_string('result_skipped', 'local_branchupload')];
            }
            return $this->handle_delete($existinguser);
        }

        // Handle create or update.
        if ($existinguser) {
            return $this->handle_update($existinguser, $row);
        } else {
            $newusername = $this->email_to_username($row['email']);
            return $this->handle_create($row, $newusername);
        }
    }

    /**
     * Create a new user from row data.
     *
     * @param array $row The parsed row.
     * @param string $username The normalised username.
     * @return array{action: string, message: string, info?: string}
     */
    private function handle_create(array $row, string $username): array {
        $user = new \stdClass();
        $user->username = $username;
        $user->email = $row['email'];
        $user->firstname = $row['firstname'];
        $user->lastname = $row['lastname'];
        $user->auth = 'manual';
        $user->confirmed = 1;
        $user->mnethostid = $GLOBALS['CFG']->mnet_localhost_id;

        // Create the user (no password — will be sent via cron).
        $user->id = user_create_user($user, false);

        // Save the branch profile field.
        $profileuser = new \stdClass();
        $profileuser->id = $user->id;
        $branchfieldkey = 'profile_field_' . $this->branchfield;
        $profileuser->$branchfieldkey = $row['branch'];

        // Save the org unit profile field.
        $orgfieldkey = 'profile_field_' . $this->orgunitfield;
        $profileuser->$orgfieldkey = $row['orgunit'];

        profile_save_data($profileuser);

        // Assign to branch cohort.
        $info = [];
        $this->ensure_cohort_membership($row['branch'], $user->id, $info);

        // Assign to additional cohorts.
        if (!empty($row['cohorts'])) {
            $cohortids = array_filter(array_map('trim', explode('|', $row['cohorts'])));
            foreach ($cohortids as $cohortidnumber) {
                $this->ensure_cohort_membership($cohortidnumber, $user->id, $info);
            }
        }

        // Trigger password email via cron.
        set_user_preference('create_password', 1, $user);

        $infomsg = !empty($info) ? implode(' ', $info) : '';
        return [
            'action' => 'created',
            'message' => get_string('result_created', 'local_branchupload'),
            'info' => $infomsg,
        ];
    }

    /**
     * Update an existing user from row data.
     *
     * @param \stdClass $existinguser The existing user record.
     * @param array $row The parsed row.
     * @return array{action: string, message: string, info?: string}
     */
    private function handle_update(\stdClass $existinguser, array $row): array {
        $user = new \stdClass();
        $user->id = $existinguser->id;
        $user->firstname = $row['firstname'];
        $user->lastname = $row['lastname'];
        $user->email = $row['email'];

        // Update username when email changes.
        $newusername = $this->email_to_username($row['email']);
        if ($existinguser->username !== $newusername) {
            $user->username = $newusername;
        }

        // Unsuspend if previously suspended.
        if ($existinguser->suspended) {
            $user->suspended = 0;
        }

        user_update_user($user, false);

        // Update profile fields.
        $profileuser = new \stdClass();
        $profileuser->id = $existinguser->id;
        $branchfieldkey = 'profile_field_' . $this->branchfield;
        $profileuser->$branchfieldkey = $row['branch'];
        $orgfieldkey = 'profile_field_' . $this->orgunitfield;
        $profileuser->$orgfieldkey = $row['orgunit'];

        profile_save_data($profileuser);

        // Ensure cohort memberships.
        $info = [];
        $this->ensure_cohort_membership($row['branch'], $existinguser->id, $info);

        if (!empty($row['cohorts'])) {
            $cohortids = array_filter(array_map('trim', explode('|', $row['cohorts'])));
            foreach ($cohortids as $cohortidnumber) {
                $this->ensure_cohort_membership($cohortidnumber, $existinguser->id, $info);
            }
        }

        $infomsg = !empty($info) ? implode(' ', $info) : '';
        return [
            'action' => 'updated',
            'message' => get_string('result_updated', 'local_branchupload'),
            'info' => $infomsg,
        ];
    }

    /**
     * Suspend or delete a user.
     *
     * @param \stdClass $existinguser The user to remove.
     * @return array{action: string, message: string}
     */
    private function handle_delete(\stdClass $existinguser): array {
        if ($this->deleteaction === 'delete') {
            delete_user($existinguser);
            return [
                'action' => 'deleted',
                'message' => get_string('result_deleted', 'local_branchupload'),
            ];
        } else {
            $user = new \stdClass();
            $user->id = $existinguser->id;
            $user->suspended = 1;
            user_update_user($user, false);
            return [
                'action' => 'suspended',
                'message' => get_string('result_suspended', 'local_branchupload'),
            ];
        }
    }

    /**
     * Ensure a user is a member of a cohort, creating the cohort if needed.
     *
     * @param string $idnumber The cohort idnumber.
     * @param int $userid The user ID.
     * @param array $info Info messages array (modified by reference).
     */
    private function ensure_cohort_membership(string $idnumber, int $userid, array &$info): void {
        global $DB;

        $cohort = $this->cohortcache[$idnumber] ?? $DB->get_record('cohort', ['idnumber' => $idnumber]);

        if (!$cohort && $this->autocreatecohorts) {
            $newcohort = new \stdClass();
            $newcohort->contextid = \context_system::instance()->id;
            $newcohort->name = $idnumber;
            $newcohort->idnumber = $idnumber;
            $newcohort->description = '';
            $newcohort->id = cohort_add_cohort($newcohort);
            $cohort = $newcohort;
            $this->cohortcache[$idnumber] = $cohort;
            $info[] = get_string('cohort_created', 'local_branchupload', $idnumber);
        }

        if ($cohort) {
            cohort_add_member($cohort->id, $userid);
            $this->cohortcache[$idnumber] = $cohort;
        }
    }

    /**
     * Load all distinct branch values used in the branch profile field.
     *
     * These are treated as protected cohort idnumbers that non-admins
     * must not assign via the Kohorten column.
     *
     * Note: the underlying user_info_data.data column is TEXT, so we cannot
     * portably use SELECT DISTINCT on it (invalid on MSSQL/Oracle). We fetch
     * the raw values and deduplicate in PHP — the value space is small
     * (one row per user) so this is acceptable.
     *
     * @return string[] Array of unique non-empty branch value strings.
     */
    private function load_branch_cohort_ids(): array {
        global $DB;

        if (empty($this->branchfield)) {
            return [];
        }

        // Look up the profile field ID.
        $fieldid = $DB->get_field('user_info_field', 'id', ['shortname' => $this->branchfield]);
        if (!$fieldid) {
            return [];
        }

        // Fetch all values for this field and deduplicate in PHP — see note above.
        $rs = $DB->get_recordset('user_info_data', ['fieldid' => $fieldid], '', 'id, data');
        $values = [];
        foreach ($rs as $record) {
            $value = trim((string) $record->data);
            if ($value !== '') {
                $values[$value] = true;
            }
        }
        $rs->close();

        return array_keys($values);
    }

    /**
     * Convert an email address to a Moodle username.
     *
     * @param string $email The email address.
     * @return string The normalised username.
     */
    public function email_to_username(string $email): string {
        return clean_param(\core_text::strtolower(trim($email)), PARAM_USERNAME);
    }

    /**
     * Check if a username already exists.
     *
     * @param string $username The username to check.
     * @return bool True if the user exists.
     */
    private function user_exists(string $username): bool {
        global $DB;
        return $DB->record_exists('user', ['username' => $username, 'deleted' => 0]);
    }

    /**
     * Check whether a row indicates deletion/suspension.
     *
     * @param array $row The parsed row.
     * @return bool True if the row indicates the user should be removed.
     */
    private function is_delete_row(array $row): bool {
        $val = \core_text::strtolower(trim($row['remove']));
        return in_array($val, ['1', 'ja', 'yes', 'true']);
    }

    /**
     * Get the uploader's branch value.
     *
     * @return string|null The branch value or null for admins.
     */
    public function get_uploader_branch(): ?string {
        return $this->uploaderbranch;
    }

    /**
     * Whether the current user is a site admin.
     *
     * @return bool
     */
    public function is_admin(): bool {
        return $this->isadmin;
    }

    /**
     * Get the maximum users per upload setting.
     *
     * @return int 0 means unlimited.
     */
    public function get_max_users(): int {
        return $this->maxusers;
    }

    /**
     * Get the resolved CSV column header for every canonical key.
     *
     * Use this in Mustache template contexts so the table column headers
     * displayed to the user match the headers in their uploaded CSV file.
     *
     * @return array<string,string> Canonical key => configured header.
     */
    public function get_column_headers(): array {
        return $this->columnconfig->all_headers();
    }
}
