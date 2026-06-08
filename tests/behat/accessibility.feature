@local @local_branchupload
Feature: WCAG 2.2 AA accessibility regression
  In order to prevent the carefully-engineered accessibility patterns
  from being silently broken by future markup changes
  As a developer maintaining the plugin
  I need the semantic step indicator, the visually-hidden section
  headings, the row-header cells and the icon-as-decoration pattern
  to be asserted directly against the rendered DOM.

  These scenarios run without JavaScript so they exercise the raw
  server-rendered HTML — the layer that WCAG conformance actually
  depends on.

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

  Scenario: The upload page has a semantic step indicator with the active step marked
    When I visit "/local/branchupload/index.php"
    # WCAG 1.3.1, 4.1.2 — step indicator is a semantic ordered list with an
    # accessible name and an aria-current marker on the active step.
    Then "ol[aria-label='Upload progress']" "css_element" should exist
    And "ol[aria-label='Upload progress'] li[aria-current='step']" "css_element" should exist
    # Each step label carries a visually-hidden status prefix for screen readers.
    And ".local-branchupload-steps .visually-hidden" "css_element" should exist
    # The right-arrow separators are hidden from assistive tech.
    And "ol[aria-label='Upload progress'] li[aria-hidden='true']" "css_element" should exist

  Scenario: The upload form's example link carries a decorative download icon
    When I visit "/local/branchupload/index.php"
    # WCAG 1.1.1 — decorative icon is aria-hidden, leaving the text as the
    # accessible name.
    Then "i.fa-download[aria-hidden='true']" "css_element" should exist
    And "Example CSV file" "link" should exist

  Scenario: The upload page exposes a labelled main form and a help button
    When I visit "/local/branchupload/index.php"
    # WCAG 2.4.6 / 3.3.2 — every field has a visible label.
    Then "CSV file" "field" should exist
    And "CSV delimiter" "field" should exist
    And "Encoding" "field" should exist
    # WCAG 3.2.6 — consistent help affordance (Moodle's help "?" icon).
    And "Help with CSV file" "icon" should exist
