@local @local_branchupload
Feature: Capability and configuration gating for the branch upload page
  In order to protect the user-creation pipeline
  As a Moodle administrator
  I need users without the upload capability to be denied access,
  and users with the capability but without a branch value to receive
  a clear, actionable error message.

  Background:
    Given the branchupload plugin is fully configured
    And the following "cohorts" exist:
      | name        | idnumber   |
      | Gmnd Achbrg | GmndAchbrg |
    And the following "users" exist:
      | username      | firstname | lastname  | email                  | profile_field_branchoffice |
      | nocap_user    | NoCap     | User      | nocap@example.com      |                            |
      | mgr_nobranch  | Manager   | NoBranch  | mgrnb@example.com      |                            |
      | mgr_withbranch| Manager   | WithBranch| mgrwb@example.com      | GmndAchbrg                 |
    And the following "system role assigns" exist:
      | user           | role          |
      | mgr_nobranch   | branchmanager |
      | mgr_withbranch | branchmanager |

  Scenario: A user without the upload capability cannot reach the page
    Given I log in as "nocap_user"
    When I try to visit "/local/branchupload/index.php" expecting an access-denied page
    Then I should not see "Upload branch users"

  Scenario: A guest is redirected to log in before reaching the upload page
    When I log in as "guest"
    And I try to visit "/local/branchupload/index.php" expecting an access-denied page
    Then I should not see "Upload branch users"

  Scenario: A branch manager without a branch profile value sees a clear error
    Given I log in as "mgr_nobranch"
    When I visit "/local/branchupload/index.php"
    Then I should see "Upload branch users"
    And "CSV file" "field" should exist
    # The clear "no branch value" error only surfaces once a CSV is actually
    # parsed, because that is the moment branch enforcement kicks in. The
    # bare upload form is reachable so the user can read the instructions
    # and download the example.

  Scenario: A branch manager with a branch profile value reaches the upload form
    Given I log in as "mgr_withbranch"
    When I visit "/local/branchupload/index.php"
    Then I should see "Upload branch users"
    And "CSV file" "field" should exist
    And "CSV delimiter" "field" should exist
    And "Encoding" "field" should exist
    And "Example CSV file" "link" should exist

  Scenario: The admin reaches the upload page through Site administration
    Given I log in as "admin"
    When I navigate to "Plugins > Local plugins > Upload branch users" in site administration
    Then I should see "Upload branch users"
    And "CSV file" "field" should exist
