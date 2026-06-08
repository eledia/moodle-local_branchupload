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
 * PHPUnit tests for local_branchupload processing engine.
 *
 * @package    local_branchupload
 * @author     Christopher Reimann <christopher.reimann@eledia.de>
 * @copyright  2026 eLeDia GmbH, Berlin {@link https://eledia.de}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_branchupload;

use advanced_testcase;
use csv_import_reader;

/**
 * Tests for the branch upload processing engine.
 *
 * @covers \local_branchupload\process
 * @covers \local_branchupload\column_config
 */
final class process_test extends advanced_testcase {
    /**
     * Set up required libraries before tests.
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->libdir . '/csvlib.class.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');
        require_once($CFG->dirroot . '/cohort/lib.php');
        parent::setUpBeforeClass();
    }

    /**
     * Configure plugin settings and create profile fields for testing.
     *
     * @return array{branchfieldid: int, orgfieldid: int} The created field IDs.
     */
    private function setup_plugin_config(): array {
        global $DB;

        // Create a category for custom profile fields.
        $catid = $DB->insert_record('user_info_category', (object) [
            'name' => 'Test category',
            'sortorder' => 1,
        ]);

        // Create branch profile field.
        $branchfieldid = $DB->insert_record('user_info_field', (object) [
            'shortname' => 'branchoffice',
            'name' => 'Branch Office',
            'datatype' => 'text',
            'categoryid' => $catid,
            'sortorder' => 1,
            'required' => 0,
            'locked' => 0,
            'visible' => 2,
            'forceunique' => 0,
            'signup' => 0,
            'defaultdata' => '',
            'param1' => 30,
            'param2' => 2048,
        ]);

        // Create org unit profile field.
        $orgfieldid = $DB->insert_record('user_info_field', (object) [
            'shortname' => 'orgunit',
            'name' => 'Organisationseinheit',
            'datatype' => 'text',
            'categoryid' => $catid,
            'sortorder' => 2,
            'required' => 0,
            'locked' => 0,
            'visible' => 2,
            'forceunique' => 0,
            'signup' => 0,
            'defaultdata' => '',
            'param1' => 30,
            'param2' => 2048,
        ]);

        // Configure the plugin.
        set_config('branchfield', 'branchoffice', 'local_branchupload');
        set_config('orgunitfield', 'orgunit', 'local_branchupload');
        set_config('autocreate_cohorts', 0, 'local_branchupload');
        set_config('deleteaction', 'suspend', 'local_branchupload');
        set_config('maxusers', 0, 'local_branchupload');

        return ['branchfieldid' => $branchfieldid, 'orgfieldid' => $orgfieldid];
    }

    /**
     * Set the branch profile field value for a user.
     *
     * @param int $userid The user ID.
     * @param string $value The branch value.
     * @param int $fieldid The profile field ID.
     */
    private function set_user_branch(int $userid, string $value, int $fieldid): void {
        global $DB;
        $DB->insert_record('user_info_data', (object) [
            'userid' => $userid,
            'fieldid' => $fieldid,
            'data' => $value,
            'dataformat' => 0,
        ]);
    }

    /**
     * Create a csv_import_reader from CSV content string.
     *
     * @param string $content The CSV content.
     * @param string $delimiter The delimiter name.
     * @return array{cir: csv_import_reader, columns: array} Reader and columns.
     */
    private function create_csv_reader(string $content, string $delimiter = 'semicolon'): array {
        $iid = csv_import_reader::get_new_iid('local_branchupload');
        $cir = new csv_import_reader($iid, 'local_branchupload');
        $cir->load_csv_content($content, 'UTF-8', $delimiter);
        $columns = $cir->get_columns();
        return ['cir' => $cir, 'columns' => $columns];
    }

    /**
     * Create a cohort for testing.
     *
     * @param string $idnumber The cohort idnumber.
     * @param string $name The cohort name.
     * @return \stdClass The cohort record.
     */
    private function create_cohort(string $idnumber, string $name = ''): \stdClass {
        global $DB;
        $cohort = new \stdClass();
        $cohort->contextid = \context_system::instance()->id;
        $cohort->name = $name ?: $idnumber;
        $cohort->idnumber = $idnumber;
        $cohort->description = '';
        $cohort->id = cohort_add_cohort($cohort);
        return $DB->get_record('cohort', ['id' => $cohort->id]);
    }

    /**
     * Test: successful user creation from valid CSV.
     */
    public function test_create_users_from_valid_csv(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('passwordpolicy', 0);

        $fields = $this->setup_plugin_config();
        $this->create_cohort('GmndAchbrg');

        $csv = "Email;Branch;OrgUnit;LastName;FirstName\n"
             . "test1@example.de;GmndAchbrg;Bauverwaltung;Mustermann;Max\n"
             . "test2@example.de;GmndAchbrg;Finanzen;Musterfrau;Erika";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $results = $processor->execute();

        $this->assertEquals(2, $results['stats']['created']);
        $this->assertEquals(0, $results['stats']['errors']);

        // Verify user 1 exists.
        $user1 = $DB->get_record('user', ['username' => 'test1@example.de']);
        $this->assertNotFalse($user1);
        $this->assertEquals('Max', $user1->firstname);
        $this->assertEquals('Mustermann', $user1->lastname);
        $this->assertEquals('test1@example.de', $user1->email);
        $this->assertEquals('manual', $user1->auth);

        // Verify user 2 exists.
        $user2 = $DB->get_record('user', ['username' => 'test2@example.de']);
        $this->assertNotFalse($user2);
        $this->assertEquals('Erika', $user2->firstname);
        $this->assertEquals('Musterfrau', $user2->lastname);

        // Verify profile fields.
        $profile1 = profile_user_record($user1->id, false);
        $this->assertEquals('GmndAchbrg', $profile1->branchoffice);
        $this->assertEquals('Bauverwaltung', $profile1->orgunit);

        // Verify cohort membership.
        $this->assertTrue(cohort_is_member(
            $DB->get_field('cohort', 'id', ['idnumber' => 'GmndAchbrg']),
            $user1->id
        ));
    }

    /**
     * Test: non-admin blocked when the CSV row branch does not match their own branch.
     */
    public function test_branch_enforcement_blocks_mismatch(): void {
        $this->resetAfterTest();
        set_config('passwordpolicy', 0);

        $fields = $this->setup_plugin_config();
        $this->create_cohort('GmndAchbrg');
        $this->create_cohort('GmndWangen');

        // Create a non-admin uploader with branch "GmndAchbrg".
        $uploader = $this->getDataGenerator()->create_user(['username' => 'uploader']);
        $this->set_user_branch($uploader->id, 'GmndAchbrg', $fields['branchfieldid']);
        $this->setUser($uploader);

        // Give the capability.
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/branchupload:upload', CAP_ALLOW, $roleid, \context_system::instance());
        $this->getDataGenerator()->role_assign($roleid, $uploader->id, \context_system::instance()->id);

        // CSV with a different branch.
        $csv = "Email;Branch;OrgUnit;LastName;FirstName\n"
             . "wrong@example.de;GmndWangen;Finanzen;Wrong;Branch";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $previewdata = $processor->preview();

        $this->assertEquals(1, $previewdata['summary']['errors']);
        $this->assertNotEmpty($previewdata['rows'][0]['errors']);
    }

    /**
     * Test: non-admin blocked from updating user in different branch.
     */
    public function test_crossbranch_update_blocked(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('passwordpolicy', 0);

        $fields = $this->setup_plugin_config();
        $this->create_cohort('GmndAchbrg');
        $this->create_cohort('GmndWangen');

        // Create existing user in GmndWangen.
        $existinguser = $this->getDataGenerator()->create_user([
            'username' => 'existing@example.de',
            'email' => 'existing@example.de',
        ]);
        $this->set_user_branch($existinguser->id, 'GmndWangen', $fields['branchfieldid']);

        // Create uploader in GmndAchbrg.
        $uploader = $this->getDataGenerator()->create_user(['username' => 'uploader2']);
        $this->set_user_branch($uploader->id, 'GmndAchbrg', $fields['branchfieldid']);
        $this->setUser($uploader);

        // Try to update the user from a different branch.
        $csv = "Email;Branch;OrgUnit;LastName;FirstName\n"
             . "existing@example.de;GmndAchbrg;Finanzen;Updated;Name";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $previewdata = $processor->preview();

        $this->assertEquals(1, $previewdata['summary']['errors']);
    }

    /**
     * Test: admin bypasses all branch checks.
     */
    public function test_admin_bypasses_branch_checks(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('passwordpolicy', 0);

        $fields = $this->setup_plugin_config();
        $this->create_cohort('GmndAchbrg');
        $this->create_cohort('GmndWangen');

        // Admin can upload for any branch.
        $csv = "Email;Branch;OrgUnit;LastName;FirstName\n"
             . "admin1@example.de;GmndAchbrg;Bauverwaltung;Admin;One\n"
             . "admin2@example.de;GmndWangen;Finanzen;Admin;Two";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $results = $processor->execute();

        $this->assertEquals(2, $results['stats']['created']);
        $this->assertEquals(0, $results['stats']['errors']);
    }

    /**
     * Test: removal column suspends user when setting is 'suspend'.
     */
    public function test_remove_suspends_user(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('passwordpolicy', 0);

        $fields = $this->setup_plugin_config();
        set_config('deleteaction', 'suspend', 'local_branchupload');
        $this->create_cohort('GmndAchbrg');

        // Create existing user.
        $user = $this->getDataGenerator()->create_user([
            'username' => 'suspend@example.de',
            'email' => 'suspend@example.de',
        ]);

        $csv = "Email;Branch;OrgUnit;LastName;FirstName;Remove\n"
             . "suspend@example.de;GmndAchbrg;Bauverwaltung;Test;User;1";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $results = $processor->execute();

        $this->assertEquals(1, $results['stats']['suspended']);
        $this->assertEquals(0, $results['stats']['deleted']);

        // Verify user is suspended.
        $updateduser = $DB->get_record('user', ['id' => $user->id]);
        $this->assertEquals(1, $updateduser->suspended);
    }

    /**
     * Test: removal column deletes user when setting is 'delete'.
     */
    public function test_remove_deletes_user(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('passwordpolicy', 0);

        $fields = $this->setup_plugin_config();
        set_config('deleteaction', 'delete', 'local_branchupload');
        $this->create_cohort('GmndAchbrg');

        // Create existing user.
        $user = $this->getDataGenerator()->create_user([
            'username' => 'todelete@example.de',
            'email' => 'todelete@example.de',
        ]);

        $csv = "Email;Branch;OrgUnit;LastName;FirstName;Remove\n"
             . "todelete@example.de;GmndAchbrg;Bauverwaltung;Delete;Me;ja";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $results = $processor->execute();

        $this->assertEquals(1, $results['stats']['deleted']);

        // Verify user is deleted.
        $deleteduser = $DB->get_record('user', ['id' => $user->id]);
        $this->assertEquals(1, $deleteduser->deleted);
    }

    /**
     * Test: cohorts column assigns multiple additional cohorts.
     */
    public function test_cohorts_assigns_multiple_cohorts(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('passwordpolicy', 0);

        $fields = $this->setup_plugin_config();
        $cohort1 = $this->create_cohort('GmndAchbrg');
        $cohort2 = $this->create_cohort('SchulungA');
        $cohort3 = $this->create_cohort('SchulungB');

        $csv = "Email;Branch;OrgUnit;LastName;FirstName;Cohorts\n"
             . "multi@example.de;GmndAchbrg;Finanzen;Multi;Cohort;SchulungA|SchulungB";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $results = $processor->execute();

        $this->assertEquals(1, $results['stats']['created']);

        $user = $DB->get_record('user', ['username' => 'multi@example.de']);

        // Verify all three cohort memberships.
        $this->assertTrue(cohort_is_member($cohort1->id, $user->id));
        $this->assertTrue(cohort_is_member($cohort2->id, $user->id));
        $this->assertTrue(cohort_is_member($cohort3->id, $user->id));
    }

    /**
     * Test: auto-create cohort when enabled.
     */
    public function test_autocreate_cohorts_enabled(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('passwordpolicy', 0);

        $fields = $this->setup_plugin_config();
        set_config('autocreate_cohorts', 1, 'local_branchupload');

        // Cohort "NewBranch" does not exist yet.
        $csv = "Email;Branch;OrgUnit;LastName;FirstName\n"
             . "auto@example.de;NewBranch;Finanzen;Auto;Create";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $results = $processor->execute();

        $this->assertEquals(1, $results['stats']['created']);

        // Verify cohort was created.
        $cohort = $DB->get_record('cohort', ['idnumber' => 'NewBranch']);
        $this->assertNotFalse($cohort);
        $this->assertEquals('NewBranch', $cohort->name);

        // Verify membership.
        $user = $DB->get_record('user', ['username' => 'auto@example.de']);
        $this->assertTrue(cohort_is_member($cohort->id, $user->id));
    }

    /**
     * Test: auto-create cohort when disabled rejects row.
     */
    public function test_autocreate_cohorts_disabled_rejects(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('passwordpolicy', 0);

        $fields = $this->setup_plugin_config();
        set_config('autocreate_cohorts', 0, 'local_branchupload');

        $csv = "Email;Branch;OrgUnit;LastName;FirstName\n"
             . "reject@example.de;NonExistent;Finanzen;No;Cohort";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $previewdata = $processor->preview();

        $this->assertEquals(1, $previewdata['summary']['errors']);
    }

    /**
     * Test: duplicate email updates existing user instead of creating.
     */
    public function test_duplicate_email_updates_existing(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('passwordpolicy', 0);

        $fields = $this->setup_plugin_config();
        $this->create_cohort('GmndAchbrg');

        // Create existing user.
        $existing = $this->getDataGenerator()->create_user([
            'username' => 'dup@example.de',
            'email' => 'dup@example.de',
            'firstname' => 'Old',
            'lastname' => 'Name',
        ]);

        $csv = "Email;Branch;OrgUnit;LastName;FirstName\n"
             . "dup@example.de;GmndAchbrg;Bauverwaltung;NewName;NewFirst";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $results = $processor->execute();

        $this->assertEquals(1, $results['stats']['updated']);
        $this->assertEquals(0, $results['stats']['created']);

        // Verify updated fields.
        $updated = $DB->get_record('user', ['id' => $existing->id]);
        $this->assertEquals('NewFirst', $updated->firstname);
        $this->assertEquals('NewName', $updated->lastname);
    }

    /**
     * Test: invalid CSV headers are rejected.
     */
    public function test_invalid_headers_rejected(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->setup_plugin_config();

        // Missing required columns (Branch and OrgUnit).
        $csv = "Email;LastName;FirstName\n"
             . "test@example.de;Test;User";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $errors = $processor->validate_columns();

        $this->assertNotEmpty($errors);
        // The error message must surface the configured (case-preserved) header
        // names — 'Branch' and 'OrgUnit' — not the lowercase canonical keys,
        // so admins know exactly what to add to their CSV.
        $this->assertStringContainsString('Branch', $errors[0]);
        $this->assertStringContainsString('OrgUnit', $errors[0]);
    }

    /**
     * Test: max upload quota is enforced.
     */
    public function test_max_upload_quota(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('passwordpolicy', 0);

        $fields = $this->setup_plugin_config();
        set_config('maxusers', 2, 'local_branchupload');
        $this->create_cohort('GmndAchbrg');

        // CSV with 3 rows — exceeds limit of 2.
        $csv = "Email;Branch;OrgUnit;LastName;FirstName\n"
             . "u1@example.de;GmndAchbrg;Finanzen;One;User\n"
             . "u2@example.de;GmndAchbrg;Finanzen;Two;User\n"
             . "u3@example.de;GmndAchbrg;Finanzen;Three;User";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $this->assertEquals(2, $processor->get_max_users());

        $previewdata = $processor->preview();
        $this->assertEquals(3, $previewdata['summary']['total']);
        // The index.php would block this before processing — test the getter.
    }

    /**
     * Test: email is normalised to lowercase username.
     */
    public function test_email_normalised_to_username(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->setup_plugin_config();
        $processor = new process(
            $this->create_csv_reader("Email;Branch;OrgUnit;LastName;FirstName\n")['cir'],
            ['Email', 'Branch', 'OrgUnit', 'LastName', 'FirstName']
        );

        $this->assertEquals('test@example.de', $processor->email_to_username('Test@Example.DE'));
        $this->assertEquals('user@test.com', $processor->email_to_username('  User@Test.COM  '));
    }

    /**
     * Test: configuration validation detects missing settings.
     */
    public function test_configuration_validation(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Don't set up config — leave fields empty.
        set_config('branchfield', '', 'local_branchupload');
        set_config('orgunitfield', '', 'local_branchupload');

        $csv = "Email;Branch;OrgUnit;LastName;FirstName\n"
             . "test@example.de;GmndAchbrg;Finanzen;Test;User";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $errors = $processor->validate_configuration();
        $this->assertNotEmpty($errors);
    }

    /**
     * Test: removal column with value 'ja' triggers delete action.
     */
    public function test_remove_ja_value(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('passwordpolicy', 0);

        $fields = $this->setup_plugin_config();
        set_config('deleteaction', 'suspend', 'local_branchupload');
        $this->create_cohort('GmndAchbrg');

        $user = $this->getDataGenerator()->create_user([
            'username' => 'jatest@example.de',
            'email' => 'jatest@example.de',
        ]);

        $csv = "Email;Branch;OrgUnit;LastName;FirstName;Remove\n"
             . "jatest@example.de;GmndAchbrg;Finanzen;Test;Ja;ja";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $results = $processor->execute();

        $this->assertEquals(1, $results['stats']['suspended']);
        $updateduser = $DB->get_record('user', ['id' => $user->id]);
        $this->assertEquals(1, $updateduser->suspended);
    }

    /**
     * Test: missing required fields in row are caught during preview.
     */
    public function test_missing_required_row_fields(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->setup_plugin_config();
        $this->create_cohort('GmndAchbrg');

        // Row with empty LastName and FirstName.
        $csv = "Email;Branch;OrgUnit;LastName;FirstName\n"
             . "empty@example.de;GmndAchbrg;Finanzen;;";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $previewdata = $processor->preview();

        $this->assertEquals(1, $previewdata['summary']['errors']);
        $this->assertNotEmpty($previewdata['rows'][0]['errors']);
    }

    /**
     * Test: non-admin cannot use the cohorts column to assign a cohort
     * whose idnumber matches another branch (privilege-escalation guard).
     */
    public function test_cohorts_cannot_assign_branch_cohort(): void {
        $this->resetAfterTest();
        set_config('passwordpolicy', 0);

        $fields = $this->setup_plugin_config();
        $this->create_cohort('GmndAchbrg');
        $this->create_cohort('GmndWangen');

        // Establish that GmndWangen is a known branch value.
        $other = $this->getDataGenerator()->create_user(['username' => 'wangenuser']);
        $this->set_user_branch($other->id, 'GmndWangen', $fields['branchfieldid']);

        // Non-admin uploader belonging to GmndAchbrg.
        $uploader = $this->getDataGenerator()->create_user(['username' => 'achbrguploader']);
        $this->set_user_branch($uploader->id, 'GmndAchbrg', $fields['branchfieldid']);
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/branchupload:upload', CAP_ALLOW, $roleid, \context_system::instance());
        $this->getDataGenerator()->role_assign($roleid, $uploader->id, \context_system::instance()->id);
        $this->setUser($uploader);

        $csv = "Email;Branch;OrgUnit;LastName;FirstName;Cohorts\n"
             . "sneaky@example.de;GmndAchbrg;Finanzen;Sneaky;User;GmndWangen";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $previewdata = $processor->preview();

        $this->assertEquals(1, $previewdata['summary']['errors']);
        $this->assertNotEmpty($previewdata['rows'][0]['errors']);
    }

    /**
     * Test: admin is exempt from the branch-cohort protection (can assign any cohort).
     */
    public function test_admin_can_assign_any_cohort_via_cohorts(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('passwordpolicy', 0);

        $fields = $this->setup_plugin_config();
        $branch1 = $this->create_cohort('GmndAchbrg');
        $branch2 = $this->create_cohort('GmndWangen');

        // Mark GmndWangen as a known branch value.
        $other = $this->getDataGenerator()->create_user(['username' => 'wangenuser2']);
        $this->set_user_branch($other->id, 'GmndWangen', $fields['branchfieldid']);

        $csv = "Email;Branch;OrgUnit;LastName;FirstName;Cohorts\n"
             . "admin-cross@example.de;GmndAchbrg;Finanzen;Cross;Branch;GmndWangen";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $results = $processor->execute();

        $this->assertEquals(1, $results['stats']['created']);
        $this->assertEquals(0, $results['stats']['errors']);

        $user = $DB->get_record('user', ['username' => 'admin-cross@example.de']);
        $this->assertTrue(cohort_is_member($branch1->id, $user->id));
        $this->assertTrue(cohort_is_member($branch2->id, $user->id));
    }

    /**
     * Test: oldemail column updates the user and renames their username/email.
     */
    public function test_oldemail_updates_username_and_email(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('passwordpolicy', 0);

        $this->setup_plugin_config();
        $this->create_cohort('GmndAchbrg');

        $existing = $this->getDataGenerator()->create_user([
            'username' => 'old.address@example.de',
            'email' => 'old.address@example.de',
            'firstname' => 'Old',
            'lastname' => 'Address',
        ]);

        $csv = "Email;Branch;OrgUnit;LastName;FirstName;OldEmail\n"
             . "new.address@example.de;GmndAchbrg;Finanzen;Address;New;old.address@example.de";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $results = $processor->execute();

        $this->assertEquals(1, $results['stats']['updated']);
        $this->assertEquals(0, $results['stats']['errors']);

        $updated = $DB->get_record('user', ['id' => $existing->id]);
        $this->assertSame('new.address@example.de', $updated->username);
        $this->assertSame('new.address@example.de', $updated->email);
        $this->assertSame('New', $updated->firstname);
        $this->assertSame('Address', $updated->lastname);
    }

    /**
     * Test: oldemail pointing at an email already used by another active user is rejected.
     */
    public function test_oldemail_rejects_email_conflict(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('passwordpolicy', 0);

        $this->setup_plugin_config();
        $this->create_cohort('GmndAchbrg');

        $this->getDataGenerator()->create_user([
            'username' => 'old.address@example.de',
            'email' => 'old.address@example.de',
        ]);
        $this->getDataGenerator()->create_user([
            'username' => 'taken@example.de',
            'email' => 'taken@example.de',
        ]);

        $csv = "Email;Branch;OrgUnit;LastName;FirstName;OldEmail\n"
             . "taken@example.de;GmndAchbrg;Finanzen;Foo;Bar;old.address@example.de";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $previewdata = $processor->preview();

        $this->assertEquals(1, $previewdata['summary']['errors']);
    }

    /**
     * Test: empty CSV body (headers only) processes cleanly with no users created.
     */
    public function test_empty_csv_body_produces_no_changes(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('passwordpolicy', 0);

        $this->setup_plugin_config();
        $this->create_cohort('GmndAchbrg');

        $csv = "Email;Branch;OrgUnit;LastName;FirstName\n";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $results = $processor->execute();

        $this->assertSame(0, $results['stats']['created']);
        $this->assertSame(0, $results['stats']['updated']);
        $this->assertSame(0, $results['stats']['errors']);
        $this->assertEmpty($results['rows']);
    }

    /**
     * Test: invalid email format triggers a row error and skips the user.
     */
    public function test_invalid_email_format_rejected(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('passwordpolicy', 0);

        $this->setup_plugin_config();
        $this->create_cohort('GmndAchbrg');

        $csv = "Email;Branch;OrgUnit;LastName;FirstName\n"
             . "not-an-email;GmndAchbrg;Finanzen;Bad;Email";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $previewdata = $processor->preview();

        $this->assertEquals(1, $previewdata['summary']['errors']);
        $this->assertNotEmpty($previewdata['rows'][0]['errors']);
    }

    // Configurable CSV column headers — added in 1.3.0, rewired in 1.4.0
    // to use English canonical keys + site-language defaults.

    /**
     * Test: canonical_keys() lists required keys first, optional keys last,
     * with the documented English vocabulary.
     */
    public function test_column_config_canonical_keys(): void {
        $this->assertSame(
            ['email', 'branch', 'orgunit', 'lastname', 'firstname'],
            column_config::REQUIRED_KEYS
        );
        $this->assertSame(
            ['remove', 'cohorts', 'oldemail'],
            column_config::OPTIONAL_KEYS
        );
        $this->assertSame(
            ['email', 'branch', 'orgunit', 'lastname', 'firstname',
             'remove', 'cohorts', 'oldemail'],
            column_config::canonical_keys()
        );
    }

    /**
     * Test: default_for_key() reads the col_default_<key> language string
     * from the *site* language, so a German site sees German defaults while
     * an English site sees English defaults.
     */
    public function test_default_for_key_uses_site_language(): void {
        global $CFG;
        $this->resetAfterTest();

        // English site language → English defaults (the documented vocabulary).
        // English is always available, so these assertions always run.
        $CFG->lang = 'en';
        get_string_manager()->reset_caches();
        $this->assertSame('Email', column_config::default_for_key('email'));
        $this->assertSame('Branch', column_config::default_for_key('branch'));
        $this->assertSame('OrgUnit', column_config::default_for_key('orgunit'));
        $this->assertSame('LastName', column_config::default_for_key('lastname'));
        $this->assertSame('FirstName', column_config::default_for_key('firstname'));
        $this->assertSame('Remove', column_config::default_for_key('remove'));
        $this->assertSame('Cohorts', column_config::default_for_key('cohorts'));
        $this->assertSame('OldEmail', column_config::default_for_key('oldemail'));

        // German site language → the historical German defaults that existing
        // customer CSVs already use. Moodle's string manager refuses to load
        // *any* language file — including the plugin's own bundled
        // 'lang/de/local_branchupload.php' — unless the base German Moodle
        // language pack is installed at $CFG->langotherroot/de/langconfig.php
        // (see core_string_manager_standard::populate_parent_languages()).
        // That's always true on a German production site, but not necessarily
        // on CI runners that only ship English. Skip cleanly when it isn't;
        // the bundled 'lang/de/local_branchupload.php' is still covered by the
        // moodle-plugin-ci lang-file lint job.
        if (!get_string_manager()->translation_exists('de')) {
            $this->markTestSkipped(
                'German Moodle language pack not installed; the string manager '
                . 'cannot resolve lang/de/local_branchupload.php without it.'
            );
        }

        $CFG->lang = 'de';
        get_string_manager()->reset_caches();
        $this->assertSame('Email', column_config::default_for_key('email'));
        $this->assertSame('Behörde', column_config::default_for_key('branch'));
        $this->assertSame('Organisationseinheit', column_config::default_for_key('orgunit'));
        $this->assertSame('Name', column_config::default_for_key('lastname'));
        $this->assertSame('Vorname', column_config::default_for_key('firstname'));
        $this->assertSame('Löschen', column_config::default_for_key('remove'));
        $this->assertSame('Kohorten', column_config::default_for_key('cohorts'));
        $this->assertSame('Alte_Email', column_config::default_for_key('oldemail'));
    }

    /**
     * Test: default_for_key() raises a coding_exception for unknown canonical keys.
     */
    public function test_default_for_key_rejects_unknown_key(): void {
        $this->resetAfterTest();
        $this->expectException(\coding_exception::class);
        column_config::default_for_key('does_not_exist');
    }

    /**
     * Test: defaults() returns the full canonical-key → site-language-default map.
     */
    public function test_column_config_defaults_helper(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->lang = 'en';

        $this->assertSame(
            [
                'email'     => 'Email',
                'branch'    => 'Branch',
                'orgunit'   => 'OrgUnit',
                'lastname'  => 'LastName',
                'firstname' => 'FirstName',
                'remove'    => 'Remove',
                'cohorts'   => 'Cohorts',
                'oldemail'  => 'OldEmail',
            ],
            column_config::defaults()
        );
    }

    /**
     * Test: column_config returns the documented site-language defaults when
     * no config is set.
     */
    public function test_column_config_defaults(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->lang = 'en';

        // Make sure no config overrides are present.
        foreach (column_config::canonical_keys() as $key) {
            unset_config('col_' . $key, 'local_branchupload');
        }

        $cc = new column_config();

        $this->assertSame('Email', $cc->header('email'));
        $this->assertSame('Branch', $cc->header('branch'));
        $this->assertSame('OrgUnit', $cc->header('orgunit'));
        $this->assertSame('LastName', $cc->header('lastname'));
        $this->assertSame('FirstName', $cc->header('firstname'));
        $this->assertSame('Remove', $cc->header('remove'));
        $this->assertSame('Cohorts', $cc->header('cohorts'));
        $this->assertSame('OldEmail', $cc->header('oldemail'));

        $this->assertSame(
            ['email', 'branch', 'orgunit', 'lastname', 'firstname'],
            array_keys($cc->required_headers())
        );
        $this->assertSame(
            ['remove', 'cohorts', 'oldemail'],
            array_keys($cc->optional_headers())
        );
    }

    /**
     * Test: column_config picks up overrides from plugin config.
     */
    public function test_column_config_reads_overrides_from_config(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->lang = 'en';

        set_config('col_email', 'EMAIL_ADDRESS', 'local_branchupload');
        set_config('col_branch', 'Site', 'local_branchupload');
        set_config('col_orgunit', 'Department', 'local_branchupload');
        set_config('col_remove', 'Delete', 'local_branchupload');

        $cc = new column_config();

        $this->assertSame('EMAIL_ADDRESS', $cc->header('email'));
        $this->assertSame('Site', $cc->header('branch'));
        $this->assertSame('Department', $cc->header('orgunit'));
        $this->assertSame('Delete', $cc->header('remove'));
        // Untouched defaults still resolve to the site-language English defaults.
        $this->assertSame('LastName', $cc->header('lastname'));
        $this->assertSame('FirstName', $cc->header('firstname'));
        $this->assertSame('Cohorts', $cc->header('cohorts'));
        $this->assertSame('OldEmail', $cc->header('oldemail'));
    }

    /**
     * Test: column_config matches CSV headers case-insensitively and trim-insensitively.
     */
    public function test_column_config_build_index_is_case_and_trim_insensitive(): void {
        $this->resetAfterTest();

        $cc = new column_config([
            'email' => 'Email',
            'branch' => 'Site',
            'orgunit' => 'Department',
            'lastname' => 'Surname',
            'firstname' => 'GivenName',
            'remove' => 'Delete',
            'cohorts' => 'ExtraCohorts',
            'oldemail' => 'PreviousEmail',
        ]);

        // CSV with weird casing and trailing spaces — must still resolve.
        $csvheaders = ['  email ', 'SITE', 'department', 'surname', 'GIVENNAME'];
        $index = $cc->build_index($csvheaders);

        $this->assertSame(0, $index['email']);
        $this->assertSame(1, $index['branch']);
        $this->assertSame(2, $index['orgunit']);
        $this->assertSame(3, $index['lastname']);
        $this->assertSame(4, $index['firstname']);
        // Optional columns absent — must map to null.
        $this->assertNull($index['remove']);
        $this->assertNull($index['cohorts']);
        $this->assertNull($index['oldemail']);
    }

    /**
     * Test: column_config flags only required columns as missing.
     */
    public function test_column_config_missing_required(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->lang = 'en';

        $cc = new column_config();

        // All required headers present — nothing missing.
        $this->assertSame(
            [],
            $cc->missing_required([
                'Email', 'Branch', 'OrgUnit', 'LastName', 'FirstName',
            ])
        );

        // Two required headers missing.
        $this->assertSame(
            ['branch', 'orgunit'],
            $cc->missing_required(['Email', 'LastName', 'FirstName'])
        );

        // Optional headers absent — still not flagged.
        $this->assertSame(
            [],
            $cc->missing_required([
                'Email', 'Branch', 'OrgUnit', 'LastName', 'FirstName',
            ])
        );
    }

    /**
     * Test: the processor honours custom column headers end-to-end.
     *
     * Renames every header to a distinct vocabulary (deliberately *different*
     * from the new English defaults so the test cannot accidentally pass
     * against the defaults), uploads a CSV with the new headers and verifies
     * the user is created with the correct profile fields and cohort
     * membership.
     */
    public function test_processor_honours_custom_column_headers(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('passwordpolicy', 0);

        $this->setup_plugin_config();
        $this->create_cohort('GmndAchbrg');
        $this->create_cohort('TrainingA');

        // Rename every CSV column header via admin config to values that
        // are deliberately distinct from the site-language English defaults.
        set_config('col_email', 'EmailAddress', 'local_branchupload');
        set_config('col_branch', 'Site', 'local_branchupload');
        set_config('col_orgunit', 'Department', 'local_branchupload');
        set_config('col_lastname', 'Surname', 'local_branchupload');
        set_config('col_firstname', 'GivenName', 'local_branchupload');
        set_config('col_remove', 'Delete', 'local_branchupload');
        set_config('col_cohorts', 'ExtraCohorts', 'local_branchupload');
        set_config('col_oldemail', 'PreviousEmail', 'local_branchupload');

        $csv = "EmailAddress;Site;Department;Surname;GivenName;Delete;ExtraCohorts;PreviousEmail\n"
             . "custom@example.de;GmndAchbrg;Finance;Doe;Jane;;TrainingA;";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        // The validate_columns() call must accept the renamed headers.
        $this->assertSame([], $processor->validate_columns());

        $results = $processor->execute();
        $this->assertSame(1, $results['stats']['created']);
        $this->assertSame(0, $results['stats']['errors']);

        $user = $DB->get_record('user', ['username' => 'custom@example.de']);
        $this->assertNotFalse($user);
        $this->assertSame('Jane', $user->firstname);
        $this->assertSame('Doe', $user->lastname);

        $profile = profile_user_record($user->id, false);
        $this->assertSame('GmndAchbrg', $profile->branchoffice);
        $this->assertSame('Finance', $profile->orgunit);

        // Branch + extra cohort memberships are both honoured.
        $this->assertTrue(cohort_is_member(
            $DB->get_field('cohort', 'id', ['idnumber' => 'GmndAchbrg']),
            $user->id
        ));
        $this->assertTrue(cohort_is_member(
            $DB->get_field('cohort', 'id', ['idnumber' => 'TrainingA']),
            $user->id
        ));
    }

    /**
     * Test: after a rename, CSVs with the default English headers are
     * rejected with an error message that names the *new* expected headers.
     */
    public function test_processor_rejects_default_headers_after_rename(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->lang = 'en';

        $this->setup_plugin_config();

        // Rename Branch → Site and OrgUnit → Department. Both targets are
        // deliberately disjoint from the English defaults so the
        // "not in error message" assertion below is unambiguous.
        set_config('col_branch', 'Site', 'local_branchupload');
        set_config('col_orgunit', 'Department', 'local_branchupload');

        // CSV still uses the default English headers — must now be rejected.
        $csv = "Email;Branch;OrgUnit;LastName;FirstName\n"
             . "x@example.de;GmndAchbrg;Finanzen;Doe;Jane";

        $reader = $this->create_csv_reader($csv);
        $processor = new process($reader['cir'], $reader['columns']);

        $errors = $processor->validate_columns();

        $this->assertNotEmpty($errors);
        // The error message tells the admin exactly which new header names to use.
        $this->assertStringContainsString('Site', $errors[0]);
        $this->assertStringContainsString('Department', $errors[0]);
        // And it does NOT mention the now-renamed default English names as missing.
        $this->assertStringNotContainsString('Branch', $errors[0]);
        $this->assertStringNotContainsString('OrgUnit', $errors[0]);
    }

    /**
     * Test: get_column_headers() exposes the resolved headers to template code.
     */
    public function test_processor_get_column_headers(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->lang = 'en';

        $this->setup_plugin_config();
        set_config('col_email', 'EmailAddress', 'local_branchupload');
        set_config('col_branch', 'Site', 'local_branchupload');

        $reader = $this->create_csv_reader("EmailAddress;Site;OrgUnit;LastName;FirstName\n");
        $processor = new process($reader['cir'], $reader['columns']);

        $headers = $processor->get_column_headers();

        $this->assertSame('EmailAddress', $headers['email']);
        $this->assertSame('Site', $headers['branch']);
        $this->assertSame('OrgUnit', $headers['orgunit']);
        $this->assertSame('LastName', $headers['lastname']);
        $this->assertSame('FirstName', $headers['firstname']);
        $this->assertSame('Remove', $headers['remove']);
        $this->assertSame('Cohorts', $headers['cohorts']);
        $this->assertSame('OldEmail', $headers['oldemail']);
    }
}
