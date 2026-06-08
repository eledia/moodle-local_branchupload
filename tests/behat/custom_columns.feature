@local @local_branchupload @javascript @_file_upload
Feature: Configurable CSV column headers
  In order to support organisations with their own naming conventions
  As a Moodle administrator
  I need to rename every CSV column header from the plugin settings
  and have the rename respected in the upload form documentation,
  the preview/results tables, the example-CSV download and the
  column validation rules — without touching any plugin code.

  Background:
    Given the branchupload plugin is fully configured
    And the following "cohorts" exist:
      | name        | idnumber   |
      | Gmnd Achbrg | GmndAchbrg |
      | Training A  | TrainingA  |
    And the following "users" exist:
      | username | firstname | lastname | email             | profile_field_branchoffice |
      | manager1 | Max       | Manager  | mgr1@example.com  | GmndAchbrg                 |
    And the following "system role assigns" exist:
      | user     | role          |
      | manager1 | branchmanager |
    # Rename every column to a deliberately distinct vocabulary so the
    # assertions cannot accidentally pass against the site-language defaults.
    And the branchupload column header for "email" is set to "EmailAddress"
    And the branchupload column header for "branch" is set to "Site"
    And the branchupload column header for "orgunit" is set to "Department"
    And the branchupload column header for "lastname" is set to "Surname"
    And the branchupload column header for "firstname" is set to "GivenName"
    And the branchupload column header for "remove" is set to "Delete"
    And the branchupload column header for "cohorts" is set to "ExtraCohorts"
    And the branchupload column header for "oldemail" is set to "PreviousEmail"

  Scenario: The upload-form info text reflects the renamed headers
    Given I log in as "manager1"
    When I visit "/local/branchupload/index.php"
    Then I should see "Required columns: EmailAddress, Site, Department, Surname, GivenName"
    And I should see "Optional columns: Delete"
    And I should see "ExtraCohorts"
    And I should see "PreviousEmail"
    # And the default English names must no longer be advertised.
    And I should not see "Required columns: Email, Branch"

  Scenario: A CSV using the renamed headers is accepted and processed
    Given I log in as "manager1"
    And I visit "/local/branchupload/index.php"
    When I upload "local/branchupload/tests/fixtures/users_custom_columns.csv" file to "CSV file" filemanager
    And I press "Upload CSV"
    Then I should see "Upload preview"
    # The preview table's column titles are now the renamed headers.
    And I should see "EmailAddress" in the "table" "css_element"
    And I should see "Site" in the "table" "css_element"
    And I should see "Department" in the "table" "css_element"
    And I should see "Surname" in the "table" "css_element"
    And I should see "GivenName" in the "table" "css_element"
    # And the rows themselves render the data correctly.
    And I should see "Will be created" in the "custom1@example.de" "table_row"
    And I should see "Will be created" in the "custom2@example.de" "table_row"
    When I press "Confirm upload"
    Then I should see "Upload results"
    And I should see "Created" in the "custom1@example.de" "table_row"
    And I should see "Created" in the "custom2@example.de" "table_row"

  Scenario: A CSV using the default English headers is rejected with a clear error
    Given I log in as "manager1"
    And I visit "/local/branchupload/index.php"
    # Upload the default-headed fixture — which now uses the wrong column names.
    When I upload "local/branchupload/tests/fixtures/users_branch_a.csv" file to "CSV file" filemanager
    And I press "Upload CSV"
    # The error message must surface the *configured* (renamed) headers so the
    # admin knows exactly which columns to add.
    Then I should see "Missing required columns"
    And I should see "Site"
    And I should see "Department"
    And I should see "Surname"
    And I should see "GivenName"
