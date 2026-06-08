@local @local_branchupload @javascript @_file_upload
Feature: Branch enforcement — non-admins cannot escape their own branch
  In order to keep tenants strictly separated
  As a Moodle administrator who has delegated user provisioning
  I need to be confident that a branch manager can only create, update
  or suspend users that belong to their own branch — even if the CSV
  tries to smuggle rows or cohorts from another branch.

  Background:
    Given the branchupload plugin is fully configured
    And the following "cohorts" exist:
      | name           | idnumber   |
      | Gmnd Achbrg    | GmndAchbrg |
      | St Weingrtn    | StWeingrtn |
    And the following "users" exist:
      | username | firstname | lastname | email             | profile_field_branchoffice |
      | manager1 | Max       | Manager  | mgr1@example.com  | GmndAchbrg                 |
    And the following "system role assigns" exist:
      | user     | role          |
      | manager1 | branchmanager |

  Scenario: Cross-branch rows are flagged as Error and skipped on confirm
    Given I log in as "manager1"
    And I visit "/local/branchupload/index.php"
    When I upload "local/branchupload/tests/fixtures/users_cross_branch.csv" file to "CSV file" filemanager
    And I press "Upload CSV"
    Then I should see "Upload preview"
    # The own-branch row is fine.
    And I should see "Will be created" in the "ownbranch@example.de" "table_row"
    # The foreign-branch row is flagged Error with an explanatory message.
    And I should see "Error" in the "foreignbranch@example.de" "table_row"
    And I should see "Branch mismatch" in the "foreignbranch@example.de" "table_row"
    When I press "Confirm upload"
    Then I should see "Upload results"
    And I should see "Created" in the "ownbranch@example.de" "table_row"
    And I should see "Error" in the "foreignbranch@example.de" "table_row"
    # Verify the foreign-branch user was not created in the database.
    Given I log out
    And I log in as "admin"
    And I visit "/admin/user.php"
    Then I should see "ownbranch@example.de"
    And I should not see "foreignbranch@example.de"

  Scenario: Smuggling another branch's cohort via the cohorts column is refused
    Given I log in as "manager1"
    And I visit "/local/branchupload/index.php"
    When I upload "local/branchupload/tests/fixtures/users_smuggle_cohort.csv" file to "CSV file" filemanager
    And I press "Upload CSV"
    Then I should see "Upload preview"
    # The smuggling attempt is flagged Error with the explicit reason.
    And I should see "Error" in the "smuggle@example.de" "table_row"
    And I should see "represents a branch office and cannot be assigned via the cohorts column" in the "smuggle@example.de" "table_row"

  Scenario: Admin bypass — admin may upload rows for any branch
    Given I log in as "admin"
    And I visit "/local/branchupload/index.php"
    When I upload "local/branchupload/tests/fixtures/users_cross_branch.csv" file to "CSV file" filemanager
    And I press "Upload CSV"
    Then I should see "Upload preview"
    And I should see "Admin mode"
    And I should not see "Your branch:"
    # Both rows are valid in admin mode, even the foreign-branch one.
    And I should see "Will be created" in the "ownbranch@example.de" "table_row"
    And I should see "Will be created" in the "foreignbranch@example.de" "table_row"
    When I press "Confirm upload"
    Then I should see "Created" in the "ownbranch@example.de" "table_row"
    And I should see "Created" in the "foreignbranch@example.de" "table_row"

  Scenario: Admin bypass — admin may also smuggle cohorts via the cohorts column
    Given I log in as "admin"
    And I visit "/local/branchupload/index.php"
    When I upload "local/branchupload/tests/fixtures/users_smuggle_cohort.csv" file to "CSV file" filemanager
    And I press "Upload CSV"
    Then I should see "Upload preview"
    # Even though the cohorts column contains a branch-cohort idnumber, the
    # admin is allowed to perform this assignment — by design.
    And I should see "Will be created" in the "smuggle@example.de" "table_row"
    And I should not see "Error" in the "smuggle@example.de" "table_row"
