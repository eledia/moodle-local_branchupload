@local @local_branchupload @javascript @_file_upload
Feature: Removal handling — admin chooses suspend or delete
  In order to make destructive operations deliberate
  As a Moodle administrator
  I need the Remove=1 marker to map to either Suspend or Delete,
  with Suspend (reversible) as the safe default.

  Background:
    Given the branchupload plugin is fully configured
    And the following "cohorts" exist:
      | name        | idnumber   |
      | Gmnd Achbrg | GmndAchbrg |
    And the following "users" exist:
      | username   | firstname | lastname | email                | profile_field_branchoffice |
      | manager1   | Max       | Manager  | mgr1@example.com     | GmndAchbrg                 |
      | toremove   | To        | Remove   | toremove@example.de  | GmndAchbrg                 |
    And the following "system role assigns" exist:
      | user     | role          |
      | manager1 | branchmanager |
    And I log in as "manager1"
    And I visit "/local/branchupload/index.php"

  Scenario: Remove=1 with default action suspends the user (reversible)
    # The "Suspend" action is the documented default.
    When I upload "local/branchupload/tests/fixtures/users_delete.csv" file to "CSV file" filepicker
    And I press "Upload CSV"
    Then I should see "Upload preview"
    And I should see "Will be suspended" in the "toremove@example.de" "table_row"
    When I press "Confirm upload"
    Then I should see "Upload results"
    And I should see "Users suspended"
    And I should see "Suspended" in the "toremove@example.de" "table_row"
    # Verify the user still exists but is suspended.
    Given I log out
    And I log in as "admin"
    When I am on the "Users" page
    Then I should see "toremove@example.de"

  Scenario: Remove=1 with delete action permanently removes the user
    Given the branchupload delete action is set to "delete"
    When I upload "local/branchupload/tests/fixtures/users_delete.csv" file to "CSV file" filepicker
    And I press "Upload CSV"
    Then I should see "Upload preview"
    And I should see "Will be deleted" in the "toremove@example.de" "table_row"
    When I press "Confirm upload"
    Then I should see "Upload results"
    And I should see "Users deleted"
    And I should see "Deleted" in the "toremove@example.de" "table_row"
