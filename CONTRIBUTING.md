# Contributing to `local_branchupload`

Thanks for taking the time to contribute! This document covers everything
you need to get a patch reviewed and merged.

## Ground rules

- **Coding style:** [Moodle coding style](https://moodledev.io/general/development/policies/codingstyle).
  We use `moodle-plugin-ci codechecker`, `phpdoc` and `validate` in CI.
- **Language strings:** keep `lang/en/local_branchupload.php` and
  `lang/de/local_branchupload.php` alphabetically sorted and in sync.
- **No business-behaviour changes** without an accompanying issue or
  release note.
- **No secrets:** never commit credentials, tokens or production data.
- **GPL v3 or later** for all contributions (matches the Moodle plugin
  licence requirement).

## Quick start

```bash
# 1. Clone into a Moodle dev install
git clone https://github.com/eledia/moodle-local_branchupload.git \
  path/to/moodle/local/branchupload

# 2. Install/upgrade the database
php path/to/moodle/admin/cli/upgrade.php --non-interactive

# 3. Install moodle-plugin-ci (one-time)
composer create-project -n --no-dev --prefer-dist \
  moodlehq/moodle-plugin-ci ci ^4
export PATH="$(pwd)/ci/bin:$(pwd)/ci/vendor/bin:$PATH"
```

## Branching & PR workflow

1. Open an issue first if the change is more than a typo fix. Link it
   from your PR.
2. Branch off `main`:
   ```bash
   git switch -c feature/short-descriptive-name
   ```
3. Write tests for new behaviour.
4. Make sure the full CI suite passes locally:
   ```bash
   moodle-plugin-ci phplint
   moodle-plugin-ci codechecker
   moodle-plugin-ci phpdoc
   moodle-plugin-ci validate
   moodle-plugin-ci mustache
   moodle-plugin-ci grunt
   moodle-plugin-ci phpunit
   ```
5. Push and open a PR against `main`.
6. CI must be green before review.

## Commit messages

Follow the [Moodle commit-message convention](https://moodledev.io/general/development/process/git#commit-messages):

```
MDL-XXXXX local_branchupload: <imperative one-line summary>

<optional longer body explaining *why*, not *what*>
```

If there is no MDL ticket (this is a private plugin), use the issue
number from this repository, e.g.:

```
#42 local_branchupload: enforce maxbytes on the upload form
```

## Adding a new language string

1. Add the key + value to **both** `lang/en/local_branchupload.php` and
   `lang/de/local_branchupload.php`, **in alphabetical order**.
2. Use `get_string('your_key', 'local_branchupload')` (PHP) or
   `{{#str}} your_key, local_branchupload {{/str}}` (Mustache).
3. Never wrap user-visible English text in code without a language string.

## Adding a new capability

1. Declare it in `db/access.php`.
2. Add a `branchupload:<your_capability>` string to both language files.
3. Document it in the README capability table.
4. Bump `$plugin->version` in `version.php`.

## Database changes

This plugin currently ships **no install.xml**. If you need to add tables:

1. Create `db/install.xml` using Moodle's XMLDB editor.
2. Add the corresponding `db/upgrade.php` migration block guarded by
   `upgrade_plugin_savepoint(true, YYYYMMDDXX, 'local', 'branchupload')`.
3. Bump `$plugin->version` accordingly.
4. Document the change in [CHANGELOG.md](CHANGELOG.md).

## Releasing

Releases follow [SemVer](https://semver.org):

| Part | When to bump |
|------|--------------|
| MAJOR | Breaking change (CSV column rename, settings removed) |
| MINOR | New backward-compatible feature |
| PATCH | Bug fix / security fix / docs only |

Release steps:

1. Update `$plugin->version` (`YYYYMMDDXX`) and `$plugin->release` in
   [version.php](version.php).
2. Add a section to [CHANGELOG.md](CHANGELOG.md).
3. Open a PR; merge after CI is green.
4. Tag the merged commit: `git tag vX.Y.Z && git push --tags`.
5. The GitHub release will be drafted automatically.

## Code of conduct

Be professional and constructive. We follow the spirit of the
[Contributor Covenant](https://www.contributor-covenant.org/). Disrespectful
behaviour will not be tolerated.

## Questions

- Internal team: ping `#moodle` on the eLeDia chat or
  `christopher.reimann@eledia.de`.
- External contributors: open a GitHub Discussion or issue.
