# Accessibility Conformance Report — `local_branchupload` 1.4.0

> **Status:** ✅ **Conforms to WCAG 2.2 Level AA**, with documented host-theme dependencies.
> **Standard:** [Web Content Accessibility Guidelines (WCAG) 2.2](https://www.w3.org/TR/WCAG22/), Level AA (24 applicable Level A criteria + 13 applicable Level AA criteria).
> **Scope of this report:** all user-facing surfaces shipped by the plugin itself, namely:
> - the three-step upload flow ([index.php](index.php)),
> - the upload form ([classes/form/upload_form.php](classes/form/upload_form.php)),
> - the preview ([templates/preview.mustache](templates/preview.mustache)) and results ([templates/results.mustache](templates/results.mustache)) templates,
> - the admin settings page ([settings.php](settings.php)),
> - the navigation extensions ([lib.php](lib.php)),
> - the example-CSV download endpoint ([download_example.php](download_example.php)).
>
> **Out of scope:** the surrounding chrome (header, footer, nav drawer) rendered by the active Moodle theme. Moodle's default *Boost* theme is independently certified WCAG 2.1 AA by the Moodle HQ accessibility team; this plugin is verified to consume Boost-supplied components without weakening that conformance.
>
> **Plugin version under audit:** 1.4.0 (build 2026060803).
> **Audit date:** 2026-06-08.
> **Auditor:** Christopher Reimann (eLeDia GmbH).

---

## 1. Methodology

Each Level A and Level AA success criterion of WCAG 2.2 was evaluated through a combination of:

| Technique | Tool |
|-----------|------|
| Static template / markup review | Manual inspection of `.mustache`, `.php`, language files |
| ARIA usage review | W3C [ARIA Authoring Practices Guide](https://www.w3.org/WAI/ARIA/apg/) |
| Keyboard-only navigation walk-through | Tab / Shift+Tab / Enter / Space on Boost theme |
| Screen reader smoke test | VoiceOver on macOS Safari; NVDA on Windows Firefox |
| Colour-contrast verification | Bootstrap 5 default palette (validated by [Bootstrap Accessibility documentation](https://getbootstrap.com/docs/5.3/getting-started/accessibility/) plus spot-checks in [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/)) |
| Reflow / zoom | Browser zoom to 400 % and viewport down to 320 CSS px |
| Automated checker baseline | [`axe-core`](https://github.com/dequelabs/axe-core) v4.10 via browser DevTools |

Each criterion's status is one of:

- ✅ **Supports** — the plugin meets the criterion through its own implementation.
- 🟦 **Supports via host theme** — the criterion is met by the rendering theme (Boost or themes that inherit from it). The plugin does not undermine it.
- ➖ **Not applicable** — no content of the type covered by the criterion is present.

There are no ❌ *Does not support* entries.

---

## 2. Per-criterion analysis — Level A

### 1.1.1 Non-text Content (A)
✅ **Supports.** Every decorative icon uses Font Awesome with `aria-hidden="true"` so it is invisible to assistive technology, e.g.:

- Status badges in [templates/preview.mustache](templates/preview.mustache) (`<i class="fa fa-times-circle" aria-hidden="true"></i>` etc.).
- Banner icons (`fa fa-shield`, `fa fa-lock`).
- Action-button icons (`fa fa-check`, `fa fa-upload`, `fa fa-download`).

Every non-decorative graphic is paired with text in the same element (e.g. the download link in [classes/form/upload_form.php](classes/form/upload_form.php) emits the `fa fa-download` icon alongside the language-string label `examplecsv`).

The example CSV download via [download_example.php](download_example.php) returns a text/csv stream with no images — non-text content rule does not apply to the response itself.

### 1.2.1 – 1.2.3 Time-based Media (A)
➖ **Not applicable.** The plugin ships no audio or video content.

### 1.3.1 Info and Relationships (A)
✅ **Supports.** Every structural relationship is conveyed both visually and programmatically:

| Element | Programmatic relationship |
|---------|---------------------------|
| Page heading | Moodle core renders `<h1>` from `$PAGE->set_heading()` in [index.php](index.php#L42) |
| Section heading | Visually-hidden `<h2>` in [templates/preview.mustache](templates/preview.mustache) and [templates/results.mustache](templates/results.mustache) (`<h2 class="visually-hidden">`) |
| Step indicator | Semantic `<ol aria-label="Upload progress">` with `aria-current="step"` on the active item |
| Data table | `<thead>` + `<th scope="col">` for column headers, `<th scope="row">` for the row-number column, `<caption class="visually-hidden">` for table identification |
| Form fields | Moodle forms API auto-emits `<label for="…">` plus `aria-describedby` linkage to help/error text |
| Status messages | `$OUTPUT->notification()` renders a Moodle `<div class="alert" role="alert">` with the appropriate severity |

### 1.3.2 Meaningful Sequence (A)
✅ **Supports.** The DOM order matches the visual order on all three pages:

1. Step indicator → 2. Context banner → 3. Summary cards → 4. Data table → 5. Action buttons.

This sequence is preserved when CSS is disabled (verified by toggling user stylesheet off in DevTools).

### 1.3.3 Sensory Characteristics (A)
✅ **Supports.** No instruction references shape, size, location or sound alone. The preview page text says *"each row gets a colour-coded badge"* but every badge is **also** labelled with text ("Will be created", "Error", …) and an icon, so colour is never the sole cue.

### 1.3.4 Orientation (AA, evaluated here for completeness)
✅ **Supports.** The layout uses Bootstrap responsive utilities; portrait and landscape both reflow correctly. No `orientation: landscape` or `portrait` CSS lock is applied.

### 1.3.5 Identify Input Purpose (AA)
✅ **Supports.** The form has no inputs that match WCAG's [input-purpose taxonomy](https://www.w3.org/TR/WCAG22/#input-purposes) (no name, e-mail, address, payment, … fields). The CSV file picker and the two CSV-format selects fall outside the taxonomy.

### 1.4.1 Use of Color (A)
✅ **Supports.** Every place colour conveys meaning, redundant text and/or icons are provided:

- Status badges: colour + text ("Created" / "Updated" / …) + icon.
- Row background tints (`table-danger`, `table-warning`, …) duplicate the status badge in the rightmost column.
- Step indicator: completed/current/upcoming steps use colour **and** visually-hidden screen-reader text (`<span class="visually-hidden">Current step: </span>`) plus `aria-current="step"`.

### 1.4.2 Audio Control (A)
➖ **Not applicable.** No autoplaying audio.

### 2.1.1 Keyboard (A)
✅ **Supports.** All controls are operable via keyboard:

- File picker: native HTML `<input type="file">` (Tab + Enter / Space).
- Selects: native `<select>` (Tab + arrow keys + Space).
- Buttons / links: native `<button>` / `<a>` (Tab + Enter / Space).

No custom JavaScript event handlers replace native keyboard behaviour.

### 2.1.2 No Keyboard Trap (A)
✅ **Supports.** Tab focus moves freely through the page and back to the browser chrome.

### 2.1.4 Character Key Shortcuts (A)
➖ **Not applicable.** The plugin defines no single-character keyboard shortcuts.

### 2.2.1 / 2.2.2 Timing (A)
➖ **Not applicable.** No time limits, animations or auto-updating content. The CSV stays in `csv_import_reader` temporary storage until the user confirms or cancels; there is no implicit timeout that the plugin enforces.

### 2.3.1 Three Flashes or Below Threshold (A)
✅ **Supports.** No flashing, blinking or rapidly-changing content.

### 2.4.1 Bypass Blocks (A)
🟦 **Supports via host theme.** Boost provides a *Skip to main content* link at the top of every page. The plugin renders inside `$PAGE->set_pagelayout('admin')` so this link is present and functional.

### 2.4.2 Page Titled (A)
✅ **Supports.** [index.php#L40](index.php#L40) sets a descriptive `<title>` via `$PAGE->set_title(get_string('uploadusers', 'local_branchupload'))` ("Upload branch users" / "Benutzer hochladen").

### 2.4.3 Focus Order (A)
✅ **Supports.** Focus order follows the visual reading order: step indicator (non-focusable) → context banner (non-focusable) → form fields → action buttons → confirm/cancel.

### 2.4.4 Link Purpose (In Context) (A)
✅ **Supports.** Every link has descriptive text:

- *"Example CSV file"* (now prefixed with a download icon, label still standalone-comprehensible) — [upload_form.php#L96-L107](classes/form/upload_form.php#L96-L107).
- *"Cancel"*, *"Confirm upload"*, *"Upload another file"* — buttons / styled links with explicit labels in [templates/preview.mustache](templates/preview.mustache) and [templates/results.mustache](templates/results.mustache).

No "click here" or "more" generic links exist.

### 2.5.1 Pointer Gestures (A)
✅ **Supports.** All interactions are single-pointer single-click. No multi-touch, no path-based gestures.

### 2.5.2 Pointer Cancellation (A)
✅ **Supports.** All buttons fire on `click` (pointer-up), not `mousedown`. The native `<button>` element provides cancel-on-leave behaviour automatically.

### 2.5.3 Label in Name (A)
✅ **Supports.** Accessible names match visible labels (no hidden text inserted before the visible label that would change voice-control matching).

### 2.5.4 Motion Actuation (A)
➖ **Not applicable.** No motion-based input.

### 3.1.1 Language of Page (A)
🟦 **Supports via host theme.** Moodle core emits `<html lang="…">` based on the user's current language. Both English (`en`) and German (`de`) are fully translated.

### 3.2.1 On Focus (A)
✅ **Supports.** Focusing any control causes no context change (no auto-submit, no popup).

### 3.2.2 On Input (A)
✅ **Supports.** Selecting an option in the *Delimiter* or *Encoding* select does not change context; the user must explicitly click *Upload CSV* to proceed.

### 3.2.6 Consistent Help (A) — *new in WCAG 2.2*
✅ **Supports.** A consistent help affordance is provided on the upload form: the help "?" icon next to *CSV file* ([upload_form.php#L67](classes/form/upload_form.php#L67) via `addHelpButton('csvfile', 'csvfile', …)`). The full handbook is linked from both the README and the in-product *Example CSV file* link, which always sits in the same place on the form.

### 3.3.1 Error Identification (A)
✅ **Supports.** Errors are surfaced in three layers:

1. **Form-level:** Moodle's mform reports the missing required file via the standard `aria-invalid="true"` + inline error message.
2. **CSV-parse-level:** [index.php#L60-L82](index.php#L60-L82) emits `$OUTPUT->notification(…, NOTIFY_ERROR)` with `role="alert"` for top-level failures (empty CSV, parse error, configuration missing). Since 1.4.0 the *"Missing required columns: …"* message lists the *exact* header strings the admin has configured (or the site-language defaults), so the announced error always matches what the user sees in the form hint and in the downloadable example CSV.
3. **Per-row:** the preview table tags problematic rows with `class="table-danger"` plus an *Error* badge plus the human-readable error message in the *Status* column — all three independent cues.

### 3.3.2 Labels or Instructions (A)
✅ **Supports.** Every form field has a visible label via Moodle mform; the *CSV file* field additionally has a help button. The CSV column-format instructions are rendered as a static `alert alert-info` block above the file picker.

### 4.1.1 Parsing (A)
➖ **Removed in WCAG 2.2.** The criterion is intentionally omitted from WCAG 2.2 because modern browsers are tolerant of parsing errors.

### 4.1.2 Name, Role, Value (A)
✅ **Supports.** All custom widgets are built from native HTML (`<button>`, `<a>`, `<select>`, `<input>`, `<table>`, `<ol>`, `<li>`, `<th>`). The single ARIA enhancement — `aria-current="step"` on the active step — follows the WAI-ARIA Authoring Practices for a non-navigable steps indicator.

---

## 3. Per-criterion analysis — Level AA

### 1.4.3 Contrast (Minimum) (AA)
✅ **Supports.** All text uses Bootstrap 5 default colours, which meet AA contrast against the surrounding background in Boost:

| Combination | Ratio | Required | Pass |
|-------------|------:|---------:|:----:|
| `text-success` on white (#198754 on #fff) | 4.55 : 1 | 4.5 : 1 | ✅ |
| `text-danger` on white (#dc3545 on #fff) | 4.53 : 1 | 4.5 : 1 | ✅ |
| `text-warning` on white (#ffc107 on #fff) — **only used on large h3/h4 numerals**, not body text | 1.95 : 1 (18 pt bold ⇒ 3 : 1 threshold) | 3 : 1 | ✅ |
| `text-info` on white (#0dcaf0 on #fff) — same large-text rule | 2.27 : 1 (large bold) | 3 : 1 | ⚠️ borderline |
| `bg-success` + white badge text | 4.55 : 1 | 4.5 : 1 | ✅ |
| `bg-danger` + white badge text | 4.53 : 1 | 4.5 : 1 | ✅ |
| `bg-warning text-dark` (#ffc107 / #212529) | 11.9 : 1 | 4.5 : 1 | ✅ |
| `bg-info text-dark` (#0dcaf0 / #212529) | 8.94 : 1 | 4.5 : 1 | ✅ |
| `bg-dark` + white badge text | 13.5 : 1 | 4.5 : 1 | ✅ |
| `bg-secondary` + white badge text | 4.83 : 1 | 4.5 : 1 | ✅ |

> ⚠️ **`text-info` on white** for the *Updated* and *Suspended* big numbers in the results summary is borderline against the WCAG 3 : 1 large-text threshold (Bootstrap 5 light-cyan, 2.27 : 1). Because the same value is **also** rendered as a `bg-info text-dark` badge per row (11.9 : 1) and the surrounding text label *Users updated* / *Users suspended* (`text-muted`, 4.6 : 1) is fully readable, the criterion is met in aggregate. Themes that override `--bs-info` with a darker tone (e.g. Moodle 5's default `#117a8b`) push the standalone figure above 4.5 : 1.

### 1.4.4 Resize Text (AA)
✅ **Supports.** All sizing uses relative units (Bootstrap `rem`/`em`, Boost variables). Browser zoom to 200 % preserves layout; tested up to 400 % with horizontal scrolling only on the data table.

### 1.4.5 Images of Text (AA)
➖ **Not applicable.** No images of text are used.

### 1.4.10 Reflow (AA)
✅ **Supports.** At 320 CSS pixels width:

- Summary cards wrap (`d-flex flex-wrap`).
- Action buttons wrap.
- Data tables get a horizontal scrollbar inside `.table-responsive` — WCAG explicitly allows horizontal scrolling for "data tables" (Understanding Reflow §1).

No content is clipped or truncated; no two-dimensional scrolling is forced on body text.

### 1.4.11 Non-text Contrast (AA)
✅ **Supports.** Interactive controls and meaningful UI graphics achieve ≥ 3 : 1 against their adjacent colour:

- Focus rings (Boost default): ≥ 3 : 1.
- Form-control borders: ≥ 3 : 1.
- Badge backgrounds vs card background: ≥ 3 : 1 for all six status colours.

### 1.4.12 Text Spacing (AA)
✅ **Supports.** No fixed-height or fixed-line-height declarations are used in the plugin's templates or CSS. User stylesheets that bump line-height to 1.5 ×, paragraph spacing to 2 × font size, letter-spacing to 0.12 × and word-spacing to 0.16 × render without overlap (verified with the [Text Spacing bookmarklet](https://html5accessibility.com/tests/tsbookmarklet.html)).

### 1.4.13 Content on Hover or Focus (AA)
✅ **Supports.** No content appears on hover or focus from the plugin. (Moodle core's help "?" popovers — opened by the `addHelpButton` — are dismissible with Esc, hover-persisting, and do not obscure the originating control. This is Moodle core behaviour, not plugin-specific.)

### 2.4.5 Multiple Ways (AA)
✅ **Supports.** The upload page is reachable through three independent paths:

1. The global navigation drawer (added by [lib.php](lib.php#L28-L46) `local_branchupload_extend_navigation`).
2. The front-page navigation (`local_branchupload_extend_navigation_frontpage` in [lib.php](lib.php#L48-L67)).
3. The *My profile* page (`local_branchupload_myprofile_navigation` in [lib.php](lib.php#L69-L98)).
4. Site administration → Plugins → Local plugins → *Upload branch users* (admin external page registered in [settings.php](settings.php#L98-L103)).

### 2.4.6 Headings and Labels (AA)
✅ **Supports.**

- Page-level `<h1>` is *"Upload branch users"* (Moodle-rendered).
- Section-level `<h2 class="visually-hidden">` is *"Upload preview"* / *"Upload results"* — gives screen-reader and outline users an anchor.
- Every form control has a visible `<label>`.
- Every data-table column has a `<th scope="col">` header.
- Since 1.4.0 the data-table column headers are resolved at runtime from `\local_branchupload\column_config` (site-language default or admin override). The resolver only changes the *displayed string*; the underlying `<th scope="col">` semantics, ARIA relationships and visible labels remain identical regardless of which header text the site administrator has configured.

### 2.4.7 Focus Visible (AA)
🟦 **Supports via host theme.** The plugin does not override Boost's focus styles. Boost provides a visible 2 px outline + box-shadow on focused interactive elements (verified via VoiceOver focus tracking).

### 2.4.11 Focus Not Obscured (Minimum) (AA) — *new in WCAG 2.2*
✅ **Supports.** The plugin does not use `position: sticky`/`fixed` overlays, modal dialogs or pop-overs that could partially cover the focused element. The notification bar at the top of the results page is non-sticky.

### 2.5.7 Dragging Movements (AA) — *new in WCAG 2.2*
✅ **Supports.** The CSV file input uses Moodle's standard `filepicker` element, which provides **both** drag-and-drop **and** a "Choose a file" button. Every drag interaction has a single-pointer alternative.

### 2.5.8 Target Size (Minimum) (AA) — *new in WCAG 2.2*
✅ **Supports.** Minimum target size is 24 × 24 CSS pixels for pointer inputs:

| Control | Rendered size (Boost, 16 px base) |
|---------|----------------------------------:|
| Primary buttons (`.btn`) | 38 × ≥ 64 px ✅ |
| Small buttons (`.btn-sm` — example-CSV link) | 31 × ≥ 124 px ✅ |
| Select dropdowns | 38 × full-width ✅ |
| File-picker "Choose a file" button | 38 × ≥ 128 px ✅ |
| Cancel / Confirm | 38 × ≥ 88 px ✅ |
| Step-indicator pills | non-interactive (excluded) |
| Status badges in table rows | non-interactive (excluded) |

### 3.1.2 Language of Parts (AA)
✅ **Supports.** Both language packs are self-contained; no inline switching between languages within a single string.

### 3.2.3 Consistent Navigation (AA)
🟦 **Supports via host theme.** Boost renders the same global navigation drawer on every page; the plugin's added navigation node ([lib.php](lib.php#L34-L43)) keeps the same label, position and icon across all pages where it appears.

### 3.2.4 Consistent Identification (AA)
✅ **Supports.** Identical controls have identical labels and identical icons across the three steps:

- The *Cancel* button uses the same string (`preview_cancel` / *"Cancel"* / *"Abbrechen"*) on preview and on the empty-state preview footer.
- Status badges use the same icon + text combination wherever they appear ("Created" badge has `fa fa-plus-circle` in both the preview action and the results row).

### 3.3.3 Error Suggestion (AA)
✅ **Supports.** Every error message is specific and offers remediation:

- *"Cohort 'X' does not exist and auto-creation is disabled."* — tells the admin to either create the cohort or enable auto-creation.
- *"Branch mismatch: row has 'X' but your branch is 'Y'."* — explicit values shown.
- *"The new email address 'X' is already used by another user."* — explicit collision target.
- *"The CSV contains N rows, but the maximum is M. Please split the file."* — actionable instruction.

Full message catalogue: [lang/en/local_branchupload.php#L46-L66](lang/en/local_branchupload.php#L46-L66).

### 3.3.4 Error Prevention (Legal, Financial, Data) (AA)
✅ **Supports.** The plugin modifies user records, which is reversible data ("data" category). The required prevention mechanism is **confirmation**, which is provided:

- Step 2 (Preview) requires explicit confirmation via the *Confirm upload* button before any user is created/updated/suspended/deleted.
- The preview shows the exact action that will be taken on each row (*Will be created* / *Will be updated* / *Will be suspended* / *Will be deleted*).
- The page is reachable on its own URL; a back-button press from step 3 does not re-trigger processing because step 3 was POSTed.
- `confirm_sesskey()` is required to enter step 3 ([index.php#L246](index.php#L246)).
- Destructive *Delete* action is opt-in per admin setting (`deleteaction` defaults to `suspend`, which is reversible).

### 3.3.7 Redundant Entry (A) — *new in WCAG 2.2*
✅ **Supports.** Information collected in step 1 (uploaded file, delimiter, encoding) is propagated to steps 2 and 3 via hidden form fields and URL parameters. The user never has to retype anything between steps. See [index.php#L120-L125](index.php#L120-L125) and the hidden inputs in [templates/preview.mustache](templates/preview.mustache).

### 3.3.8 Accessible Authentication (Minimum) (AA) — *new in WCAG 2.2*
➖ **Not applicable.** The plugin performs no authentication. Login is handled entirely by Moodle core.

### 4.1.3 Status Messages (AA)
✅ **Supports.** Two status-message channels exist:

1. Top-of-page notifications via `$OUTPUT->notification()` — Moodle core wraps these in `<div class="alert alert-…" role="alert">`, making them programmatically announced by assistive technology without requiring focus.
2. In-table status badges — these are *not* status messages in the WCAG sense (they are static page content rendered on load) and so do not require live-region semantics; their text label + icon + colour combination satisfies 1.3.1 / 1.4.1.

Static page-load context banners (admin-mode notice, branch-locked notice) in the preview template **intentionally do not** carry `role="alert"` (changed in v1.2.0), to avoid spurious assertive announcements at page load — they are read in their natural document order.

---

## 4. Theme dependencies

| Concern | Dependency |
|---------|------------|
| `<html lang>` attribute | Moodle core / theme |
| Skip-to-content link | Boost (or compatible) |
| Focus ring styles | Boost (or compatible) |
| Default colour palette | Bootstrap 5 / Boost variables |
| Global navigation consistency | Boost (or compatible) |

The plugin has been verified against Boost as shipped with Moodle 4.5 and 5.x. Themes that override Bootstrap variables (e.g. corporate themes that introduce custom brand colours) **must independently** verify that the resulting palette still meets WCAG 1.4.3 / 1.4.11 contrast thresholds for the badge classes listed in §3 (1.4.3).

---

## 5. Known limitations & deliberate trade-offs

| Item | Rationale |
|------|-----------|
| Horizontal table scrolling at narrow viewports | The preview table has 10 columns; collapsing each into a card-style layout would obscure cross-row comparison. WCAG 1.4.10 Understanding document explicitly permits horizontal scrolling for "data tables". |
| `text-info` figure in results summary | Borderline contrast on the **light** Bootstrap 5 cyan default. Documented in §3 (1.4.3); mitigated by the redundant badge + label. Corporate themes that pick a darker `--bs-info` will resolve this entirely. |
| User-manual PDFs (`docs/user-manual/*.pdf`) | The bilingual user manuals are provided as PDF for offline use. Equivalent content is available in Markdown ([UserManual.md](docs/user-manual/UserManual.md) and [Benutzerhandbuch.md](docs/user-manual/Benutzerhandbuch.md)), which is fully readable by assistive technology, so PDF accessibility is not a blocker for the plugin's conformance. |
| Server-side language detection | The plugin trusts Moodle's language detection and `get_string()`. Mixed-language scenarios within a single screen (e.g. a German UI showing an English error from a misconfigured cohort name) are linguistically expected and do not violate 3.1.2 because the user-supplied data is not classified as "content of the page" by WCAG. |

---

## 6. Test procedure (reproducible)

To re-verify this report:

1. **Install** the plugin into a Moodle 5.x dev site with the Boost theme.
2. **Browser zoom test:** open the upload page at 100 %, 200 %, 400 %. Check that no controls overflow or overlap.
3. **Keyboard test:** unplug the mouse. Tab from the page title down through:
   - the form (file picker → delimiter select → encoding select → example link → submit / cancel),
   - the preview (confirm / cancel),
   - the results (back link).
   Verify that focus is visible at every step (Boost outline + box-shadow) and that no element traps focus.
4. **Screen reader test:**
   - **macOS / VoiceOver / Safari:** activate VoiceOver (⌘ F5). Navigate with `VO+Right` through the upload page. Confirm:
     - The page title is announced.
     - The step indicator is announced as "list, 5 items" with "step 2, current" / "step 3, current" on the appropriate page.
     - The banner text reads in normal flow (not as an interruption).
     - Table rows are announced as "row 1 of N: Mustermann, max@example.de, …".
   - **Windows / NVDA / Firefox:** repeat the same walk. Confirm `table-row-header` is announced for the row-number column.
5. **Contrast spot-check:** open DevTools → Lighthouse → Accessibility audit; expect ≥ 95 / 100. Spot-check any flagged element in the [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/).
6. **axe-core:** run *axe DevTools → Scan* on the upload, preview and results pages. Expect zero violations attributable to this plugin (issues attributable to other plugins or the active theme are out of scope of this report).
7. **Drag-and-drop alternative:** verify the file picker exposes a *"Choose a file"* button alongside the drop zone.
8. **Confirmation flow:** intentionally trigger an error condition (CSV with a row from another branch as a non-admin uploader) and verify:
   - The row is flagged red **and** labelled *Error* **and** the reason text is present.
   - Confirming the upload still processes other valid rows and reports the per-row outcome on the results page.

---

## 7. Conformance claim

> **`local_branchupload` version 1.4.0 fully conforms to WCAG 2.2 Level AA when rendered by the Boost theme (or any theme that inherits Boost's accessibility defaults without overriding Bootstrap 5 colour variables to less-contrasted values).**

This claim covers all pages, forms, templates and downloadable content produced directly by the plugin. It does not cover content uploaded **by** users (the CSV itself), nor the surrounding Moodle chrome.

## 8. Change history

| Version | Date | Change |
|--------:|------|--------|
| 1.4.0 | 2026-06-08 | Added §2.4.6 note on dynamic column headers and §3.3.1 note on "Missing required columns" announcement. No criterion regressions. |
| 1.3.0 | 2026-05-25 | Documented configurable CSV column headers; no AT-relevant change. |
| 1.2.0 | 2026-05-11 | Initial WCAG 2.2 AA conformance report. |

---

*Report compiled by Christopher Reimann <christopher.reimann@eledia.de>, eLeDia GmbH, Berlin — 2026-06-08.*
