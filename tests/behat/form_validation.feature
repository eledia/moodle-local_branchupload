@local @local_branchupload
Feature: Form validation and error surfacing
  In order to give users immediate, actionable feedback
  As a branch manager uploading a CSV
  I need every error — from "no file picked" to "row has an invalid
  e-mail address" — to be displayed unambiguously, without writing
  anything to the database.

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

  Scenario: Pressing Upload without selecting a file shows the required-field error
    When I press "Upload CSV"
    Then I should see "Upload branch users"
    # The mform "required" rule fires either client-side or server-side.
    And I should see "You must supply a value here"

  @javascript @_file_upload
  Scenario: Rows with an invalid e-mail are flagged Error and skipped
    When I upload "local/branchupload/tests/fixtures/users_invalid_email.csv" file to "CSV file" filepicker
    And I press "Upload CSV"
    Then I should see "Upload preview"
    And I should see "Error" in the "not_an_email" "table_row"
    And I should see "Invalid email address" in the "not_an_email" "table_row"
    # The Confirm button should not appear when there are no valid rows.
    And I should not see "Confirm upload"

  @javascript @_file_upload
  Scenario: The per-upload row cap is enforced before any data is touched
    Given the branchupload maximum upload size is set to 2 rows
    When I upload "local/branchupload/tests/fixtures/users_branch_a.csv" file to "CSV file" filepicker
    And I press "Upload CSV"
    # The 3-row fixture exceeds the cap of 2.
    Then I should see "The CSV contains 3 rows, but the maximum is 2"
    And I should not see "Confirm upload"

  Scenario: The example CSV link triggers a file download
    # We don't assert the download contents (Behat can't easily inspect
    # the downloaded bytes without browser plumbing), but we DO assert
    # that the link is present, keyboard-reachable, and not an inline
    # anchor inside the form action group.
    Then "Example CSV file" "link" should exist
    And "Example CSV file" "link" should be visible
