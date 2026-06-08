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
 * Behat step definitions for local_branchupload.
 *
 * Provides a single helper step that performs the boring boilerplate
 * required before any meaningful scenario: it creates the two custom
 * user profile fields (Behörde, Organisationseinheit), configures the
 * plugin to use them, and creates a "branchmanager" role that owns the
 * upload capability. Scenarios then only need to create users and
 * cohorts and assign the role.
 *
 * Everything else is expressed with stock Moodle Behat data generators
 * ("the following 'users' exist", "the following 'cohorts' exist",
 * "the following 'system role assigns' exist", …) so the feature files
 * stay readable as executable specifications.
 *
 * @package    local_branchupload
 * @category   test
 * @author     Christopher Reimann <christopher.reimann@eledia.de>
 * @copyright  2026 eLeDia GmbH, Berlin {@link https://eledia.de}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here — Behat context files are not loaded by Moodle config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Custom Behat steps for the branch-office user-upload plugin.
 */
class behat_local_branchupload extends behat_base {

    /**
     * Set up the two custom user profile fields, configure the plugin,
     * create a "branchmanager" role and grant it the upload capability.
     *
     * This is intentionally a single high-level step so feature files
     * read naturally:
     *
     *     Background:
     *         Given the branchupload plugin is fully configured
     *
     * @Given /^the branchupload plugin is fully configured$/
     */
    public function the_branchupload_plugin_is_fully_configured(): void {
        global $DB;

        // 1. Custom profile-field category.
        $catid = $DB->insert_record('user_info_category', (object) [
            'name' => 'Behat: Branch upload',
            'sortorder' => 1,
        ]);

        // 2. Branch profile field (Behörde).
        $DB->insert_record('user_info_field', (object) [
            'shortname'    => 'branchoffice',
            'name'         => 'Behörde',
            'datatype'     => 'text',
            'categoryid'   => $catid,
            'sortorder'    => 1,
            'required'     => 0,
            'locked'       => 0,
            'visible'      => 2,
            'forceunique'  => 0,
            'signup'       => 0,
            'defaultdata'  => '',
            'defaultdataformat' => 0,
            'param1'       => 30,
            'param2'       => 2048,
        ]);

        // 3. Organisational-unit profile field.
        $DB->insert_record('user_info_field', (object) [
            'shortname'    => 'orgunit',
            'name'         => 'Organisationseinheit',
            'datatype'     => 'text',
            'categoryid'   => $catid,
            'sortorder'    => 2,
            'required'     => 0,
            'locked'       => 0,
            'visible'      => 2,
            'forceunique'  => 0,
            'signup'       => 0,
            'defaultdata'  => '',
            'defaultdataformat' => 0,
            'param1'       => 30,
            'param2'       => 2048,
        ]);

        // 4. Plugin configuration.
        set_config('branchfield',         'branchoffice', 'local_branchupload');
        set_config('orgunitfield',        'orgunit',      'local_branchupload');
        set_config('autocreate_cohorts',  0,              'local_branchupload');
        set_config('deleteaction',        'suspend',      'local_branchupload');
        set_config('maxusers',            0,              'local_branchupload');

        // 5. Branch-manager role + capability grant.
        $roleid = create_role(
            'Branch manager (Behat)',
            'branchmanager',
            'Test role that owns local/branchupload:upload.'
        );
        $systemcontext = context_system::instance();
        assign_capability(
            'local/branchupload:upload',
            CAP_ALLOW,
            $roleid,
            $systemcontext->id,
            true
        );
        // Allow the role to be assigned at system level.
        set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
    }

    /**
     * Switch the plugin's removal action between Suspend and Delete.
     *
     * @Given /^the branchupload delete action is set to "(?P<action_string>suspend|delete)"$/
     * @param string $action either "suspend" or "delete"
     */
    public function the_branchupload_delete_action_is_set_to(string $action): void {
        set_config('deleteaction', $action, 'local_branchupload');
    }

    /**
     * Cap the number of rows a single CSV upload may contain.
     *
     * @Given /^the branchupload maximum upload size is set to (?P<max_int>\d+) rows$/
     * @param int $max maximum number of rows; 0 = no limit
     */
    public function the_branchupload_maximum_upload_size_is_set_to_rows(int $max): void {
        set_config('maxusers', $max, 'local_branchupload');
    }

    /**
     * Enable or disable automatic cohort creation.
     *
     * @Given /^cohort auto-creation is (?P<state_string>enabled|disabled)$/
     * @param string $state either "enabled" or "disabled"
     */
    public function cohort_auto_creation_is(string $state): void {
        set_config('autocreate_cohorts', $state === 'enabled' ? 1 : 0, 'local_branchupload');
    }

    /**
     * Override one CSV column header.
     *
     * Used in feature files that exercise the column-rename feature:
     *
     *     Given the branchupload column header for "branch" is set to "Site"
     *     And the branchupload column header for "email" is set to "EmailAddress"
     *
     * Canonical keys are the eight English identifiers documented on
     * {@see \local_branchupload\column_config::canonical_keys()}: email,
     * branch, orgunit, lastname, firstname, remove, cohorts, oldemail.
     *
     * @Given /^the branchupload column header for "(?P<key_string>(?:[^"]|\\")*)" is set to "(?P<header_string>(?:[^"]|\\")*)"$/
     * @param string $key canonical column key
     * @param string $header the new CSV header string
     */
    public function the_branchupload_column_header_for_is_set_to(string $key, string $header): void {
        if (!in_array($key, \local_branchupload\column_config::canonical_keys(), true)) {
            throw new \InvalidArgumentException("Unknown column key: $key");
        }
        set_config('col_' . $key, $header, 'local_branchupload');
    }

    /**
     * Visit a URL that we expect to refuse access, and assert the standard
     * Moodle "no permissions" message is rendered, without tripping Behat's
     * stock {@see \behat_session_trait::look_for_exceptions()} AfterStep hook.
     *
     * Why this exists: {@see \require_capability()} throws
     * {@see \required_capability_exception}, which Moodle's exception
     * renderer turns into a full HTML page that includes
     * `<div data-rel='fatalerror'>`. Behat's AfterStep hook reads that
     * marker and fails the scenario — even though the denial *is* the
     * thing under test. This step performs the visit and the assertion
     * itself, then navigates to the site homepage so the AfterStep hook
     * runs on a clean DOM.
     *
     * Usage in a feature file:
     *
     *     When I try to visit "/local/branchupload/index.php" expecting an access-denied page
     *
     * @When /^I try to visit "(?P<url_string>(?:[^"]|\\")*)" expecting an access-denied page$/
     * @param string $url Local URL relative to the behat wwwroot.
     */
    public function i_try_to_visit_expecting_an_access_denied_page(string $url): void {
        $session = $this->getSession();

        // Visit the protected URL directly. The server-side capability check
        // fires, the exception renderer emits the standard denial page, and
        // the browser receives that HTML. No PHP exception bubbles up into
        // our context.
        $session->visit($this->locate_path($url));

        // Grab the rendered HTML and look for any of the well-known markers
        // Moodle uses for a capability-denied page. We accept either the
        // English-language phrase ("Sorry, but you do not currently …") or
        // the structural marker ('nopermissions' / 'errormessage') so the
        // assertion is robust across language packs and theme variations.
        $content = $session->getPage()->getContent();
        $expected = [
            'Sorry, but you do not currently have permissions',
            'nopermissions',
            'Access denied',
        ];
        $matched = false;
        foreach ($expected as $needle) {
            if (stripos($content, $needle) !== false) {
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            throw new \Behat\Mink\Exception\ExpectationException(
                'Expected an access-denied page after visiting "' . $url
                . '", but none of the expected markers were present in the response.',
                $session
            );
        }

        // Navigate to a safe page so the AfterStep look_for_exceptions hook
        // does not flag the (intentional) error page we just inspected.
        $session->visit($this->locate_path('/'));
    }
}
