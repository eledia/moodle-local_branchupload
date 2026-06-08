# Changelog

All notable changes to **local_branchupload** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.4.2] – 2026-06-08

### Fixed
- **Behat scenario “Smuggling another branch’s cohort via the cohorts
  column is refused”** was not exercising the smuggle-detection code
  path it was supposed to test. The production guard in
  `process::validate_row()` rejects a non-admin’s attempt to assign a
  branch cohort via the `Cohorts` column by looking up the cohort
  `idnumber` in `process::load_branch_cohort_ids()`, which itself
  builds its allowlist from the *distinct branch profile-field values
  currently appearing in the user table*. The Behat Background only
  created `manager1` (branch `GmndAchbrg`), so the foreign-branch
  cohort `StWeingrtn` was never recognised as a branch cohort and the
  row was silently allowed. Added a tiny carrier user
  `weingrtn_carrier` (branch `StWeingrtn`, no role assignments,
  unrelated email) to the Background so the smuggle-detection logic
  has the data it needs. Mirrors the existing PHPUnit fixture
  `process_test::test_cohorts_cannot_assign_branch_cohort` which uses
  the same pattern with a `wangenuser` carrier.

### Changed
- `version.php` bumped to `2026060805` / release `1.4.2`.

## [1.4.1] – 2026-06-08

### Fixed
- **Behat CI green-up — nine failing scenarios across five feature
  files.** The 1.4.0 Behat suite was tripping over five distinct
  pieces of test-harness friction. The plugin logic itself remained
  correct (the matching PHPUnit suite stayed green throughout), but
  the Gherkin scenarios needed adjusting to reflect how Moodle 5.1's
  Behat extension actually behaves:
  - **Access-denied page no longer flagged as a "fatal exception".**
    Moodle's stock `look_for_exceptions()` AfterStep hook scans every
    page for `<div data-rel='fatalerror'>` — which is precisely what
    Moodle's exception renderer emits for the intentional
    `required_capability_exception` produced by `require_capability()`
    at `index.php:35`. The two access-denial scenarios in
    `access.feature` therefore failed the moment they navigated to
    the protected URL, even though the denial *was* the thing under
    test. Introduced a new custom step `I try to visit "X" expecting
    an access-denied page` (in `behat_local_branchupload.php`) that
    visits the URL via Mink directly, reads the response body, asserts
    the standard "Sorry, but you do not currently have permissions"
    marker, and then navigates to the site home so the AfterStep hook
    runs on a clean DOM.
  - **Delete / suspend scenarios no longer silently mark rows as
    `skipped`.** The Background of `delete_action.feature` created the
    target user with `username = toremove` but `email =
    toremove@example.de`. The production code path uses
    `email_to_username($email)` (which lowercases the email and runs
    it through `PARAM_USERNAME`, preserving `@`) to look up the user
    — so the lookup returned `null`, `process_row()` took the
    `!$existinguser` branch, and the row's status came back as
    `skipped` rather than `suspended`/`deleted`. Aligned the Background
    so the username equals the email; the matching PHPUnit fixtures
    have always used this same convention.
  - **`I am on the "Users" page` is not a recognised core page
    identifier.** Replaced with the direct path `/admin/user.php`
    in the three scenarios that referenced it (`branch_enforcement`,
    `delete_action`, `upload_happy_path`).
  - **`should be visible` requires a JavaScript driver.** The
    `Example CSV file` link scenario in `form_validation.feature`
    was a non-`@javascript` scenario, so the visibility check raised
    a `DriverException`. The redundant assertion was removed; the
    preceding `should exist` covers the keyboard-reachability
    contract.

### Added
- **"Error" / "Warning" label on preview-page status badges.** For
  parity with the results-page status cells (which already showed
  "Created" / "Updated" / "Suspended" / "Deleted" / "Skipped" /
  "Error"), the preview-page error and warning badges now prefix the
  message with the literal "Error:" / "Warning:" label, sourced from
  the new `result_warning` language string (EN: *Warning*, DE:
  *Warnung*). This makes the status scannable for both screen-reader
  users and automated tests, and matches the visual rhythm of the
  results page.

### Changed
- `version.php` bumped to `2026060804` / release `1.4.1`.

## [1.4.0] – 2026-06-08

### Added
- **Language-dependent default column headers.** The CSV column headers
  now use *English* canonical keys (`email`, `branch`, `orgunit`,
  `lastname`, `firstname`, `remove`, `cohorts`, `oldemail`) internally,
  and their default display values are read from the
  `col_default_<key>` language strings in the configured **site
  language** (`$CFG->lang`). A German site keeps the historical
  `Behörde / Organisationseinheit / Löschen / Kohorten / Alte_Email`
  vocabulary out of the box; an English site sees
  `Branch / OrgUnit / Remove / Cohorts / OldEmail`.
- New `\local_branchupload\column_config::canonical_keys()`,
  `default_for_key(string $key)` and `defaults()` helper methods.
- New `db/upgrade.php` migration that renames any existing admin
  overrides of the old German config keys (`col_behoerde`,
  `col_orgeinheit`, `col_name`, `col_vorname`, `col_loeschen`,
  `col_kohorten`, `col_alte_email`) to their new English equivalents
  (`col_branch`, `col_orgunit`, `col_lastname`, `col_firstname`,
  `col_remove`, `col_cohorts`, `col_oldemail`) during the standard
  Moodle upgrade.
- Eight new `col_default_<key>` language strings (EN + DE) that hold
  the per-site-language default header values.
- New PHPUnit test `test_default_for_key_uses_site_language` proves
  the same site shows German defaults under `$CFG->lang = 'de'` and
  English defaults under `$CFG->lang = 'en'`, plus
  `test_column_config_canonical_keys`,
  `test_default_for_key_rejects_unknown_key` and
  `test_column_config_defaults_helper`.
- **Bilingual user manuals + branded PDFs.** The former German-only
  `ANLEITUNG.md` / `ANLEITUNG.pdf` have been replaced by a dedicated
  `docs/user-manual/` folder containing both an English
  ([UserManual.md](docs/user-manual/UserManual.md)) and a German
  ([Benutzerhandbuch.md](docs/user-manual/Benutzerhandbuch.md))
  version, each updated for 1.4.0. Both PDFs are generated by the
  new `docs/user-manual/build-pdf.sh` (pandoc → WeasyPrint pipeline)
  using the new `docs/user-manual/style/eledia.css` stylesheet and
  per-language cover pages — all styled in the eLeDia corporate
  identity (navy `#003366` + blue `#0066b3`).

### Changed
- **BREAKING (configuration keys only).** The plugin-config keys
  storing custom CSV header overrides have been renamed from German
  (`col_behoerde`, `col_orgeinheit`, `col_name`, `col_vorname`,
  `col_loeschen`, `col_kohorten`, `col_alte_email`) to English
  (`col_branch`, `col_orgunit`, `col_lastname`, `col_firstname`,
  `col_remove`, `col_cohorts`, `col_oldemail`). The `db/upgrade.php`
  migration handles this automatically for upgrading sites.
- The row arrays produced by `process::parse_line()`, consumed by
  `process::validate_row()`, `process::process_row()`,
  `process::handle_create()` and `process::handle_update()`, now use
  English canonical keys (`branch`, `orgunit`, `lastname`,
  `firstname`, `remove`, `cohorts`, `oldemail`) instead of the
  previous German ones.
- The five row-validation error language strings have been renamed
  in lockstep: `error_missingbehoerde → error_missingbranch`,
  `error_missingorgeinheit → error_missingorgunit`,
  `error_missingname → error_missinglastname`,
  `error_missingvorname → error_missingfirstname`,
  `error_invalidalteemail → error_invalidoldemail`.
- Settings page now derives both the default value and the
  `setting_col_*_desc` help text from
  `column_config::default_for_key($key)`, with the resolved default
  surfaced via the `{$a}` placeholder. Empty fields now fall back to
  the site-language default instead of a hard-coded German string.
- The `csvcolumns_info` and `col_default_*` strings now use the new
  canonical-key placeholders (`{$a->branch}`, `{$a->orgunit}`,
  `{$a->lastname}`, `{$a->firstname}`, `{$a->remove}`,
  `{$a->cohorts}`, `{$a->oldemail}`).
- Mustache templates `preview.mustache` and `results.mustache` use
  the new English row keys (`{{branch}}`, `{{orgunit}}`, etc.).
- Eight CSV fixtures and the column-rename Behat scenario were
  rewritten against the English defaults so they remain meaningful
  on the English-language Behat site.
- Test method names with German vocabulary
  (`test_loeschen_suspends_user`,
  `test_kohorten_assigns_multiple_cohorts`,
  `test_alte_email_updates_username_and_email`, etc.) renamed to
  their English equivalents (`test_remove_suspends_user`,
  `test_cohorts_assigns_multiple_cohorts`,
  `test_oldemail_updates_username_and_email`, etc.).

### Removed
- The `\local_branchupload\column_config::DEFAULTS` constant. Use
  `canonical_keys()`, `defaults()` or `default_for_key($key)` instead.
- The eight `col_alteemail`, `col_behoerde`, `col_email`,
  `col_kohorten`, `col_loeschen`, `col_name`, `col_orgeinheit`,
  `col_vorname` language strings (replaced by the new
  `col_default_<key>` strings).

## [1.3.0] – 2026-05-25

### Added
- **Configurable CSV column headers.** Every CSV column header is now
  customisable via the new *CSV column headers* section in the plugin
  settings (Site administration → Plugins → Local plugins → Branch office
  user upload). All eight headers (`Email`, `Behörde`, `Organisationseinheit`,
  `Name`, `Vorname`, `Löschen`, `Kohorten`, `Alte_Email`) default to the
  original German vocabulary so existing deployments need no migration.
  Header matching is case- and trim-insensitive.
- New `\local_branchupload\column_config` helper class — the single source of
  truth that resolves canonical keys to configured header strings and
  feeds the processor, the upload form, the preview/results templates and
  the example-CSV download in one place.
- New `process::get_column_headers()` accessor for template code.
- New Behat feature [tests/behat/custom_columns.feature](tests/behat/custom_columns.feature)
  exercising the full rename round-trip (info text, preview, results,
  rejection of old headers).
- New Behat helper step:
  `Given the branchupload column header for "<key>" is set to "<header>"`.
- Seven new PHPUnit tests covering `column_config` defaults / overrides /
  case-insensitive matching / missing-required detection, plus three
  processor-level tests for the rename behaviour end-to-end.
- 16 new language strings (EN + DE) for the column-header settings and
  the new *CSV column headers* settings-section heading.

### Changed
- The *Preview* and *Results* templates render the configured CSV header
  names instead of fixed language strings, so the table column titles
  always match the headers in the uploaded CSV.
- The example-CSV download ([download_example.php](download_example.php))
  is now generated on the fly from the configured headers and the
  `column_config` helper, instead of serving the static `example.csv` file.
  A UTF-8 BOM is now emitted so spreadsheet applications detect the
  encoding correctly when opening the file directly.
- The *Required / Optional columns* hint above the file picker is now
  built from the configured headers via `{$a->...}` placeholders, so
  renames take effect with no language-file edits.
- `process::validate_columns()` reports the *configured* (case-preserved)
  header names in its error message, not the lowercase canonical keys,
  so admins know exactly what to add to their CSV.
- The two `csvfile_help` strings (EN + DE) no longer hard-code the
  German column names — they point readers at the settings instead.

## [1.2.0] – 2026-05-11

### Added
- Full **Behat acceptance-test suite** under [tests/behat/](tests/behat/)
  covering six feature files (access control, happy path, branch
  enforcement, Löschen action, form validation, accessibility regression)
  with six reusable CSV fixtures under [tests/fixtures/](tests/fixtures/).
  The suite uses stock Moodle Behat data generators plus a single helper
  step (`Given the branchupload plugin is fully configured`) so feature
  files read as executable specifications.
- Formal **WCAG 2.2 Level AA** Accessibility Conformance Report
  ([ACCESSIBILITY.md](ACCESSIBILITY.md)) covering every applicable Level A
  and AA success criterion, including the five new WCAG 2.2 criteria
  (2.4.11 Focus Not Obscured, 2.5.7 Dragging Movements,
  2.5.8 Target Size, 3.2.6 Consistent Help, 3.3.7 Redundant Entry).
- New language strings `step_completed`, `step_current`, `step_indicator_label`,
  `step_upcoming` (EN + DE) for the upload-progress indicator.
- Visually-hidden `<h2>` section headings on the preview and results pages
  so screen-reader users get a stable outline.
- Font Awesome download icon on the *Example CSV file* link in the upload
  form (still text-labelled — no information conveyed by the icon alone).

### Changed
- The three-step upload-progress indicator on `index.php`, `preview.mustache`
  and `results.mustache` is now a semantic `<ol aria-label="…">` with
  `aria-current="step"` on the active item, replacing the previous
  non-semantic `<div>`/`<span>` structure. (WCAG 1.3.1, 4.1.2.)
- The row-number cell in the preview and results tables is now
  `<th scope="row" class="fw-normal">` instead of `<td>`, giving
  screen-reader users a proper row header. (WCAG 1.3.1.)
- Static page-load context banners no longer carry `role="alert"`, so
  assistive technology no longer announces them as interruptions.
  Dynamic notifications via `$OUTPUT->notification()` retain their
  alert role (Moodle core). (WCAG 4.1.3.)

## [1.1.0] – 2026-04-27

### Added
- New language strings `summary_total`, `summary_warnings`, `header_status`,
  `header_details`, `header_rownumber` to fully localise the preview and
  results tables.
- Language string for the `local/branchupload:upload` capability
  (`branchupload:upload`).
- Fully-expanded `privacy:metadata` reason text in both languages,
  documenting which core APIs the plugin delegates to.
- PHPUnit coverage for:
  - branch-cohort smuggling protection via `Kohorten` (non-admin and admin),
  - e-mail change via `Alte_Email` (success and conflict),
  - empty CSV body, and
  - invalid e-mail format rejection.
- `.github/workflows/moodle-ci.yml` running the full Moodle Plugin CI suite
  on a PHP 8.2/8.3/8.4 × PostgreSQL/MariaDB matrix against `MOODLE_501_STABLE`.
- `CHANGELOG.md`, `SECURITY.md`, `CONTRIBUTING.md`, `LICENSE`, `.gitignore`.

### Changed
- Both language files are now alphabetically ordered, as required by
  Moodle's coding style and enforced by moodle-plugin-ci.
- `download_example.php` now requires the `local/branchupload:upload`
  capability, releases the Moodle session lock via
  `\core\session\manager::write_close()` before streaming, and emits
  explicit `Content-Type`, `Content-Disposition` and `Cache-Control`
  headers around an `fputcsv()` payload.
- The CSV upload form caps uploads at 5 MiB by default (overridable via
  `$CFG->maxbytes`) and enforces the `required` rule both client- and
  server-side.
- Exceptions raised while processing a row are now funneled through
  `debugging(DEBUG_DEVELOPER)` so admins still see them in dev mode, but
  end users see a generic error string instead of raw exception messages.
- README rewritten as the standalone-repository landing page with
  badges, capability matrix, security model and CI instructions.

### Fixed
- The preview row mapping now includes the `Alte_Email` column value; the
  column was previously always empty in the rendered table.
- `lib.php` now declares `defined('MOODLE_INTERNAL') || die();`.
- `load_branch_cohort_ids()` no longer issues `SELECT DISTINCT` on a TEXT
  column — invalid SQL on MSSQL and Oracle. Values are now fetched via
  the standard recordset API and deduplicated in PHP.

### Security
- Example CSV download is now capability-gated.
- Internal exception details are no longer surfaced to the per-row UI.

## [1.0.0] – 2026-04-13

### Added
- Initial release.
- Three-step CSV upload flow (Upload → Preview → Confirm).
- Branch enforcement for non-admin uploaders with full admin bypass.
- Automatic cohort assignment per `Behörde`; pipe-separated additional
  cohorts via the `Kohorten` column.
- `Löschen` column with admin-configurable *suspend* vs *delete* action.
- Auto-create-cohort setting.
- Privacy API null provider.
- English and German language packs.
- PHPUnit test suite covering the core processing engine.
- Step-by-step German handbook (since 1.4.0 superseded by the
  bilingual [docs/user-manual/](docs/user-manual/) folder).

[1.4.0]: https://github.com/eledia/moodle-local_branchupload/releases/tag/v1.4.0
[1.3.0]: https://github.com/eledia/moodle-local_branchupload/releases/tag/v1.3.0
[1.2.0]: https://github.com/eledia/moodle-local_branchupload/releases/tag/v1.2.0
[1.1.0]: https://github.com/eledia/moodle-local_branchupload/releases/tag/v1.1.0
[1.0.0]: https://github.com/eledia/moodle-local_branchupload/releases/tag/v1.0.0
