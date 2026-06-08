@local @local_branchupload @javascript @_file_upload
Feature: Happy path — upload, preview, confirm
  In order to delegate user provisioning to branch offices
  As a branch manager
  I need to upload a CSV, see exactly what will happen on every row,
  and only after I confirm should the users actually be created.

  Background:
    Given the branchupload plugin is fully configured
    And the following "cohorts" exist:
      | name        | idnumber   |
      | Gmnd Achbrg | GmndAchbrg |
    And the following "users" exist:
      | username | firstname | lastname | email             | profile_field_branchoffice |
      | manager1 | Max       | Manager  | mgr1@example.com  | GmndAchbrg                 |
    And the following "system role assigns" exist:
      | user     | role          |
      | manager1 | branchmanager |
    And I log in as "manager1"
    And I visit "/local/branchupload/index.php"

  Scenario: Branch manager uploads three new users and confirms
    When I upload "local/branchupload/tests/fixtures/users_branch_a.csv" file to "CSV file" filepicker
    And I press "Upload CSV"
    # We are now on the preview page (step 2).
    Then I should see "Upload preview"
    And I should see "Your branch:"
    And I should see "GmndAchbrg"
    And I should see "max.muster@example.de"
    And I should see "erika.neu@example.de"
    And I should see "hans.beispiel@example.de"
    And I should see "Will be created" in the "max.muster@example.de" "table_row"
    And I should see "Will be created" in the "erika.neu@example.de" "table_row"
    And I should see "Will be created" in the "hans.beispiel@example.de" "table_row"
    # Confirm — this is the only point where any user is actually created.
    When I press "Confirm upload"
    Then I should see "Upload results"
    And I should see "Users created"
    And I should see "Created" in the "max.muster@example.de" "table_row"
    And I should see "Created" in the "erika.neu@example.de" "table_row"
    And I should see "Created" in the "hans.beispiel@example.de" "table_row"

  Scenario: Preview shows a branch-locked banner for non-admin uploaders
    When I upload "local/branchupload/tests/fixtures/users_branch_a.csv" file to "CSV file" filepicker
    And I press "Upload CSV"
    Then I should see "Your branch:"
    And I should see "GmndAchbrg"
    And I should not see "Admin mode"

  Scenario: Cancelling on the preview page does not create any users
    When I upload "local/branchupload/tests/fixtures/users_branch_a.csv" file to "CSV file" filepicker
    And I press "Upload CSV"
    Then I should see "Upload preview"
    When I click on "Cancel" "link"
    Then I should see "Upload branch users"
    And I should not see "Upload results"
    # And no user records should have been created — verifiable via the admin user list.
    Given I log out
    And I log in as "admin"
    When I am on the "Users" page
    Then I should not see "max.muster@example.de"
    And I should not see "erika.neu@example.de"
