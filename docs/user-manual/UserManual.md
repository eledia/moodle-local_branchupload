---
title: "Branch Office User Upload — User Manual"
subtitle: "local_branchupload · version 1.4.0"
author: "eLeDia GmbH · Christopher Reimann"
date: "June 2026"
lang: en
---

# Overview

The Moodle plugin **"Branch Office User Upload"** (`local_branchupload`) lets staff in your branch offices upload member lists as CSV files into Moodle — **without** giving them administrator rights. Every user that is uploaded is automatically

- created as a Moodle user (or updated if they already exist),
- assigned to the correct branch office (as a cohort),
- given an organisational unit (stored in a custom profile field),
- e-mailed their login credentials.

The plugin was originally built for **Landkreis Ravensburg**, a German county-level federation of municipalities, but is generic enough for any organisation with a *branch-office* structure — a school district, a corporate group with subsidiaries, a training company with regional locations, a multi-tenant SaaS deployment, …

## Security model at a glance

Each branch may **only manage users that belong to it**:

- The uploader's own branch is determined by a **custom profile field** on their Moodle account (default name: *"Behörde"*).
- The uploader can only create users for **their own** branch.
- Existing users that belong to a **different** branch cannot be modified.
- *Smuggling* a foreign branch's cohort through the `Cohorts` column is also blocked.
- **Site administrators** are exempt from these restrictions and can upload for any branch.

## What's new in 1.4.0?

- **English canonical keys** (`email`, `branch`, `orgunit`, `lastname`, `firstname`, `remove`, `cohorts`, `oldemail`) are used as the internal identifiers. As an administrator the only thing you see is that the plugin-configuration keys are now in English. The default *CSV column headers* you have to put in your file remain locale-correct.
- **Language-dependent defaults.** The default column headers are now resolved at runtime from the configured *site language* (`$CFG->lang`). A German site sees `Behörde / Organisationseinheit / Name / Vorname / Löschen / Kohorten / Alte_Email`; an English site sees `Branch / OrgUnit / LastName / FirstName / Remove / Cohorts / OldEmail` — automatically, with no code changes.
- **Automatic migration.** If you had custom CSV-header overrides configured in 1.3.0, the new `db/upgrade.php` migrates those values from the old German config keys (`col_behoerde`, `col_orgeinheit`, …) to the new English ones (`col_branch`, `col_orgunit`, …) as part of the standard Moodle upgrade. No manual action is required.

---

# For administrators: setting up the plugin

> **Important.** The following steps must be performed **once** by a Moodle administrator before branch managers can use the upload page.

## Installation

1. Copy the `branchupload` folder into your Moodle installation's `local/` directory. On Moodle 5.x this lives under the new `public/` document root:

   ```
   {moodle_root}/public/local/branchupload/
   ```

   On Moodle 4.x:

   ```
   {moodle_root}/local/branchupload/
   ```

2. Sign in as a site administrator and navigate to **Site administration → Notifications**. Moodle will detect the new plugin and run its installation. Alternatively, trigger the install from the command line:

   ```bash
   php admin/cli/upgrade.php --non-interactive
   ```

3. After installation the plugin's configuration page appears. You can always come back to it later at **Site administration → Plugins → Local plugins → Branch office user upload**.

## Create the two custom profile fields

The plugin requires **two custom user profile fields**. Create them *before* you configure the plugin.

**Navigate to:** Site administration → Users → Accounts → User profile fields

### Profile field 1 — Branch office

This field identifies which branch a user (and an uploader) belongs to.

| Setting | Recommended value |
|---------|-------------------|
| **Field type** | Text (or Menu of choices if the branch list is fixed) |
| **Short name** | `branchoffice` |
| **Name** | Behörde *(or your localised equivalent, e.g. "Branch")* |
| **Required** | No (the plugin manages it automatically) |
| **Visible to** | Everyone |

> **Tip for a Menu of choices.** Add one branch identifier per line, e.g.
>
> ```
> GmndAchbrg
> GmndWangen
> GmndIsny
> StadtRV
> ```
>
> The values must match the cohort `idnumber` *exactly* (see next section).

### Profile field 2 — Organisational unit

This field stores the department or organisational unit from the CSV file.

| Setting | Recommended value |
|---------|-------------------|
| **Field type** | Text |
| **Short name** | `orgunit` |
| **Name** | Organisationseinheit *(or "Organisational unit")* |
| **Required** | No |
| **Visible to** | Everyone |

## Create one cohort per branch

Each branch office is represented by a **cohort**. The cohort's **idnumber** must match the value used in the `Branch` (German: `Behörde`) column of the CSV.

**Navigate to:** Site administration → Users → Accounts → Cohorts

For each branch, create a cohort:

| Setting | Example value |
|---------|---------------|
| **Name** | Achberg Municipality |
| **Cohort ID** | `GmndAchbrg` |
| **Context** | System |

Repeat for every branch, e.g.:

| Name | Cohort ID |
|------|-----------|
| Achberg Municipality | `GmndAchbrg` |
| Wangen Municipality | `GmndWangen` |
| Isny Municipality | `GmndIsny` |
| Ravensburg City | `StadtRV` |

> **Alternative.** In the plugin settings you can enable *"Auto-create cohorts"*. Missing cohorts referenced in a CSV will then be created on the fly. We recommend this **only during initial roll-out**, not in steady-state production.

## Configure the plugin settings

**Navigate to:** Site administration → Plugins → Local plugins → Branch office user upload

| Setting | Description | Recommendation |
|---------|-------------|----------------|
| **Branch profile field** | Pick the *"Behörde"* profile field you created above. | `branchoffice` |
| **Organisational unit profile field** | Pick the *"Organisationseinheit"* profile field. | `orgunit` |
| **Auto-create cohorts** | If on, cohorts referenced in the CSV but missing from Moodle are created automatically. | In production: **off** |
| **Delete action** | What `Remove=1` does. "Suspend" disables the account (reversible). "Delete" removes it permanently. | **Suspend** (safer default) |
| **Maximum users per upload** | Per-CSV row cap. `0` disables the limit. | `500` |

### Optional — customise the CSV column headers

The *"CSV column headers"* settings section lets you rename every column header to match the files your branch offices already produce. The plugin's internal identifiers (English, ASCII-only) never change between releases — what changes is just the *string* that has to appear in the first row of the CSV.

| Configuration key | Required? | Default (German site) | Default (English site) |
|-------------------|:---------:|------------------------|------------------------|
| `local_branchupload/col_email`     | yes  | `Email`               | `Email`     |
| `local_branchupload/col_branch`    | yes  | `Behörde`             | `Branch`    |
| `local_branchupload/col_orgunit`   | yes  | `Organisationseinheit`| `OrgUnit`   |
| `local_branchupload/col_lastname`  | yes  | `Name`                | `LastName`  |
| `local_branchupload/col_firstname` | yes  | `Vorname`             | `FirstName` |
| `local_branchupload/col_remove`    | no   | `Löschen`             | `Remove`    |
| `local_branchupload/col_cohorts`   | no   | `Kohorten`            | `Cohorts`   |
| `local_branchupload/col_oldemail`  | no   | `Alte_Email`          | `OldEmail`  |

Column-header matching properties:

- **Case-insensitive** (`Email`, `email`, `EMAIL` all match).
- **Trim-insensitive** (leading/trailing whitespace is ignored).
- An **empty value** in the settings restores the site-language default.

Renamed headers are propagated automatically — no code change required — to:

- the *hint text* above the file picker on the upload form,
- the *downloadable example CSV* on the upload page,
- the column titles in the *preview* and *results* tables,
- the *validation error message* when a required column is missing.

> **Upgrade note (1.3.0 → 1.4.0).** If you had configured custom headers in 1.3.0 under the old German config keys (`col_behoerde`, `col_orgeinheit`, `col_name`, `col_vorname`, `col_loeschen`, `col_kohorten`, `col_alte_email`), `db/upgrade.php` migrates those values to the new English keys on upgrade. No manual action required.

## Grant the upload capability

The plugin ships a single capability: **`local/branchupload:upload`**.

Give it to the people who should be allowed to upload. There are two equivalent ways:

### Option A — via a role (recommended)

1. **Site administration → Users → Permissions → Define roles**.
2. Create a new role (e.g. *"Branch manager"*) or edit an existing one.
3. Under **Filter permissions**, search for `branchupload`.
4. Set `local/branchupload:upload` to **Allow**.
5. Assign that role to the relevant users *at system level*: **Site administration → Users → Permissions → Assign system roles**.

### Option B — directly per user

1. **Site administration → Users → Permissions → Assign system roles**.
2. Pick the role that contains the upload capability.
3. Add the users.

## Set each uploader's own branch value

**Critical.** Every user who should be able to upload must themselves carry a value in the *"Behörde"* profile field. That value determines which branch they may upload users for.

1. Open the uploader's profile.
2. Edit the profile and set the *"Behörde"* field to the corresponding cohort `idnumber` (e.g. `GmndAchbrg`).
3. **Without this value the uploader cannot perform any upload** and will see an explanatory error message.

> **Example.** Ms Müller is the responsible person for Achberg Municipality. Her profile has *Behörde = GmndAchbrg*. She can therefore only upload CSV files where every row's `Branch` column equals `GmndAchbrg`.

---

# For branch managers: uploading users

> **You can hand this section directly to your branch-office staff.**

## Prepare your CSV file

The user list is uploaded as a **CSV file** (a plain text file with one user per line, columns separated by a delimiter). You can produce the file in Excel, LibreOffice Calc, Numbers or any text editor.

### Important rules

- **Delimiter:** semicolon (`;`) is the default. You can pick a different delimiter during upload.
- **Encoding:** UTF-8 — so umlauts and accented characters (ä, ö, ü, é, ñ, …) survive correctly.
- **First row = column headers.** The first row of the file contains the column names (see below).

> **Excel tip.** When saving, choose *"CSV UTF-8 (Comma delimited)"*. On a German Excel installation the actual separator used is the semicolon.

## Required columns

Every CSV file **must** contain these columns (default English headers for an English-language Moodle site):

| Column | Description | Example |
|--------|-------------|---------|
| **Email** | The user's e-mail address. Also used as the Moodle username. | `max.mustermann@example.de` |
| **Branch** | Branch identifier — must match a cohort `idnumber`. | `GmndAchbrg` |
| **OrgUnit** | The user's department or organisational unit. | `Bauverwaltung` |
| **LastName** | Family name. | `Mustermann` |
| **FirstName** | Given name. | `Max` |

> **Note for German Moodle sites.** The default headers on a German site are `Email / Behörde / Organisationseinheit / Name / Vorname` instead. The example CSV you download from the upload page always uses the headers configured for your site, so you don't need to guess.

## Optional columns

These columns *may* appear in addition to the required ones:

| Column | Description | Accepted values |
|--------|-------------|-----------------|
| **Remove** | Mark a user for removal. Empty or missing = no removal. | `1`, `ja`, `yes` or `true`. |
| **Cohorts** | Additional cohort assignments (on top of the user's branch cohort). Multiple cohorts are separated by a pipe character (`|`). | `TrainingA|TrainingB` |
| **OldEmail** | The user's previous e-mail when renaming them. The plugin finds the existing user by the old e-mail and updates them with the new e-mail (from the `Email` column) and the new username. | `previous.address@example.de` |

> **Note about cohorts.** You cannot use the `Cohorts` column to add a user to a *branch* cohort. Branch cohorts (e.g. `GmndAchbrg`) may only be assigned via the `Branch` column. This prevents a branch manager from sneaking their users into another branch via the cohorts column. Site administrators are exempt from this restriction.

### What a complete CSV file looks like

```csv
Email;Branch;OrgUnit;LastName;FirstName;Remove;Cohorts;OldEmail
max.mustermann@example.de;GmndAchbrg;Bauverwaltung;Mustermann;Max;;TrainingA;
erika.neu@example.de;GmndAchbrg;Finanzen;Musterfrau;Erika;;TrainingA|TrainingB;erika.musterfrau@example.de
hans.beispiel@example.de;GmndAchbrg;Ordnungsamt;Beispiel;Hans;1;;
```

**Row-by-row explanation:**

- **Row 1.** Max Mustermann is created as a new user, joined to the `GmndAchbrg` cohort (his branch) and additionally added to `TrainingA`.
- **Row 2.** Erika Musterfrau changed her e-mail address: she is located by her previous address (`erika.musterfrau@example.de`), updated to her new address (`erika.neu@example.de`) — including a new username — and added to two extra cohorts.
- **Row 3.** Hans Beispiel is *suspended* (or *deleted*, depending on the administrator's chosen action) because `Remove=1`.

## Download the example CSV

The upload page provides a *"Download example CSV"* link. The example file is generated **on the fly** from the column headers configured for your site — so if your administrator renamed the columns to e.g. `EmailAddress;Site;Department;…`, the downloaded example uses exactly those header names.

## Step-by-step: performing an upload

### Step 1 — Open the upload page

Sign in to Moodle. You'll find the *"Upload branch users"* link in any of these places:

- the **left navigation drawer**,
- your **profile** page,
- if you're an administrator: **Site administration → Plugins → Local plugins → Upload branch users**.

### Step 2 — Upload the CSV file

1. Click **"Choose a file"** and pick your CSV (or drag-and-drop into the upload area).
2. **CSV delimiter:** pick the delimiter your file uses (default: semicolon).
3. **Encoding:** leave at UTF-8 unless your file uses a different encoding.
4. Click **"Upload CSV"**.

### Step 3 — Review the preview

After upload you see a **preview table** with every row of your file:

- **Above** the table your own branch is shown (e.g. *"Your branch: **GmndAchbrg**"*).
- Each row has a **status badge**:
  - **Green — Will be created**: a new user will be created.
  - **Blue — Will be updated**: the user already exists and will be updated.
  - **Yellow — Will be suspended**: the user will be deactivated (`Remove=1`).
  - **Red — Error**: the row has a problem and will be skipped. The reason is shown next to the badge.
- A **summary line** above the table shows the total row count, plus how many rows are valid and how many have errors.

> **Review the preview carefully!** Only rows marked valid will be processed — rows with errors are silently skipped.

### Step 4 — Confirm the upload

- If everything looks correct, click **"Confirm upload"**.
- If you spot a problem, click **"Cancel"**, fix your CSV and start over.

### Step 5 — Review the results

After processing, a **results overview** shows the counters *Users created*, *Users updated*, *Users suspended*, *Users deleted*, *Rows skipped* and *Errors* — followed by a per-row table with the outcome of each row.

> **What about credentials?** Newly created users receive their login details **automatically by e-mail**. Delivery happens through Moodle's cron and may take a few minutes. New users are prompted to change their password on first login.

---

# Frequently asked questions

### "I can't upload any users — I get an error."

Likely causes:

1. **No capability.** Your administrator must grant you `local/branchupload:upload`.
2. **No branch value.** The `Behörde` field on your own profile is empty. Ask your administrator to set it.
3. **Plugin not configured.** The administrator must select the two profile fields in the plugin settings.

### "Some rows show as errors."

Look at the error message displayed for the row. Common reasons:

- **Invalid e-mail** — missing `@`, typo, …
- **Branch mismatch** — the row's `Branch` column does not equal your own branch.
- **Cohort does not exist** — the cohort `idnumber` is unknown and auto-creation is disabled.
- **Required field empty** — `LastName`, `FirstName`, `Email`, `Branch` or `OrgUnit` is missing.

### "What happens when I upload a file containing an existing user?"

The user is **updated**: first name, last name, organisational unit and cohort memberships are set to the values in the CSV. The **password** is *not* changed.

### "How can I change a user's e-mail address?"

Use the optional **`OldEmail`** column: put the *new* e-mail in the `Email` column and the *current* (about-to-be-replaced) e-mail in the `OldEmail` column. The plugin finds the user by the old e-mail and updates them with the new e-mail and username.

**Example:**

```csv
Email;Branch;OrgUnit;LastName;FirstName;OldEmail
new.address@example.de;GmndAchbrg;Bauverwaltung;Mustermann;Max;previous.address@example.de
```

### "Can I edit users belonging to another branch?"

**No.** You can only edit users that belong to your own branch. Any attempt to update users from another branch is rejected with an explicit error message.

### "What does `Remove=1` actually do — is the user deleted entirely?"

It depends on your administrator's chosen action:

- **Suspend** (the documented default): the account is disabled. The user can no longer sign in, but all their data is preserved and the account can be re-enabled later.
- **Delete:** the account is removed permanently. This cannot be undone.

### "Umlauts in my CSV file appear garbled."

Make sure the file is saved as **UTF-8**:

- **Excel:** save as *"CSV UTF-8 (Comma delimited)"*.
- **LibreOffice:** choose UTF-8 in the export dialog.
- **Upload form:** pick the matching encoding (default UTF-8).

### "When do users actually receive their credentials?"

New users receive an e-mail with their username and password as soon as the next **Moodle cron** run completes — usually within a few minutes of the upload. If credential mails do not arrive, contact your administrator.

### "Can we use English column headers instead of German ones?"

Yes — in two equivalent ways:

1. **Per-header overrides.** In the plugin settings under *"CSV column headers"*, rename every column header individually to any string you like.
2. **Site language.** If your Moodle site language (`$CFG->lang`) is set to English, the defaults are automatically `Branch / OrgUnit / LastName / FirstName / Remove / Cohorts / OldEmail` — no per-setting overrides required.

---

# Error messages reference

| Error message | What it means | What to do |
|---------------|---------------|------------|
| "Missing required columns: …" | The CSV file is missing one or more required column headers. | Check the first row of the file. Required columns are the headers configured in the plugin settings — by default `Email`, `Branch`, `OrgUnit`, `LastName`, `FirstName` (English site) or `Email`, `Behörde`, `Organisationseinheit`, `Name`, `Vorname` (German site). |
| "Branch mismatch" | The row's `Branch` value differs from your own branch. | Fix the value in the `Branch` column, or have the upload performed by the relevant branch. |
| "Cohort … does not exist" | The cohort `idnumber` is unknown and auto-creation is disabled. | Ask your administrator to create the cohort, or to enable auto-creation. |
| "Invalid email address" | The e-mail format is malformed. | Check the address for typos. |
| "Email is required" | The `Email` column is empty in this row. | Provide an e-mail address. |
| "Cannot update user — different branch" | The user belongs to a different branch than yours. | Only the user's own branch can update them. |
| "The new email address … is already used by another user" | The target e-mail is taken by another active user. | Double-check that the new address is correct. |
| "Invalid previous email address" | The format of the `OldEmail` value is malformed. | Check the old e-mail address. |
| "Cohort … represents a branch office and cannot be assigned via the cohorts column" | A branch cohort was specified in the `Cohorts` column. | Branch cohorts may only be set via the `Branch` column. |
| "Profile field not configured" | The administrator has not finished configuring the plugin. | Contact your administrator. |
| "Your user account does not have a branch value set" | The `Behörde` field on your own profile is empty. | Ask your administrator to set the value. |
| "The CSV contains … rows, but the maximum is …" | The file exceeds the per-upload row cap. | Split the file into smaller chunks. |
| "Error parsing CSV file" | The file could not be parsed. | Check the file format, encoding and delimiter. |

---

# Technical notes

## System requirements

- **Moodle** 4.5 (build `2024100700`) or newer — including Moodle 5.x.
- **PHP** 8.1, 8.2, 8.3 or 8.4.
- **Databases** MariaDB / MySQL and PostgreSQL (covered by the CI matrix).

## Data handling

- Uploaded CSV files are **not persisted**. They are processed in memory by Moodle's `csv_import_reader` and discarded after processing.
- New users get auto-generated passwords sent by e-mail. The passwords are stored hashed in Moodle's database — never in plain text.
- Users are prompted to change their password on first login.

## Plugin file structure

```
local/branchupload/
├── version.php                — plugin version (1.4.0) and requirements
├── index.php                  — upload page (three-step flow)
├── download_example.php       — dynamic example-CSV download
├── lib.php                    — navigation hooks
├── settings.php               — admin settings
├── db/
│   ├── access.php             — capability definitions
│   └── upgrade.php            — config-key migration
├── classes/
│   ├── column_config.php      — CSV-header resolver
│   ├── form/upload_form.php   — upload form
│   ├── process.php            — processing engine
│   └── privacy/provider.php   — privacy API (null provider)
├── templates/
│   ├── preview.mustache       — preview table
│   └── results.mustache       — results overview
├── lang/{en,de}/              — language packs (English, German)
├── tests/                     — PHPUnit + Behat
├── docs/user-manual/          — this manual (DE + EN)
├── README.md                  — technical documentation (English)
└── CHANGELOG.md               — version history
```

## Running the tests

```bash
# From the Moodle root, after one-time init:
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit public/local/branchupload/tests/process_test.php
```

For the Behat acceptance-test suite see [README.md](../../README.md#behat).

## Privacy

The plugin is a *null Privacy provider*: it does not store any personal data of its own. All user data flows through the Moodle core APIs (`user_create_user`, `user_update_user`, `delete_user`, `profile_save_data`, `cohort_add_member`), which are themselves Privacy-API compliant.

A full WCAG 2.2 Level AA conformance statement lives in [ACCESSIBILITY.md](../../ACCESSIBILITY.md).

## Licence and contact

This plugin is released under the **GNU GPL v3 or later**.

**Copyright** © 2026 eLeDia GmbH, Berlin — [eledia.de](https://eledia.de).
**Author:** Christopher Reimann — `christopher.reimann@eledia.de`.

Please send questions, bug reports and feature requests to `info@eledia.de`, or open an issue on the plugin's GitHub repository.
