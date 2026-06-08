# Security Policy

## Reporting a vulnerability

This is an internal eLeDia plugin used in production by **Landkreis
Ravensburg**. If you believe you have found a security vulnerability,
**please do not open a public GitHub issue**.

Instead, report it privately so we can address it before it is publicly
disclosed.

### Preferred channels (in order)

1. **E-mail (encrypted preferred):**
   - `security@eledia.de`
   - PGP key fingerprint available on request.
2. **GitHub private security advisory:**
   - Open a [private security advisory](https://github.com/eledia/moodle-local_branchupload/security/advisories/new)
     on this repository.

### What to include

- A clear description of the issue.
- A minimal reproduction, including the affected Moodle version, PHP
  version and database engine.
- The branch, tag or commit you reproduced against.
- Any logs, stack traces or screenshots (please redact personal data).

### What to expect

| Stage | Target |
|-------|--------|
| Acknowledgement of report | within **2 working days** |
| Initial assessment | within **5 working days** |
| Coordinated fix released | typically within **30 days**, depending on severity |
| CVE assignment (if applicable) | requested via MITRE or GitHub |

We follow coordinated disclosure: please give us a reasonable window to
ship a fix before publishing details.

## Supported versions

Security fixes are backported according to the table below. “Supported”
means we will publish a fix release; “critical fixes only” means we will
backport patches for vulnerabilities of *High* or *Critical* CVSS severity
only; “end-of-life” means no further fixes will be published.

| Version | Status | Notes |
|---------|--------|-------|
| `1.4.x` | ✅ supported | Current release line (English canonical keys, site-language defaults). |
| `1.3.x` | ✅ supported | Configurable column headers. |
| `1.2.x` | ⚠️ critical fixes only | WCAG 2.2 AA baseline. |
| `1.1.x` | ⚠️ critical fixes only | Refactor of the processing engine. |
| `1.0.x` | ❌ end-of-life | Please upgrade. |
| `< 1.0` | ❌ end-of-life | Pre-release. |

Upgrades between any two supported versions are non-destructive: the
`db/upgrade.php` migration (introduced in 1.4.0) handles the rename of
the German admin-setting config keys to their English equivalents
automatically.

## Scope

In-scope for this policy:

- The `local_branchupload` plugin code.
- The bundled GitHub Actions workflow.
- The bundled Mustache templates.

Out of scope:

- Moodle core itself (report to [Moodle Tracker](https://tracker.moodle.org)).
- Theme/Boost rendering bugs.
- Issues caused by third-party plugins.

## Security model

A brief summary of the plugin's security boundaries is maintained in the
[README](README.md#security-model). Anything weaker than what is documented
there is considered a bug; please report it.

## Automated security checks

Every push and every pull request triggers the
[`Moodle Plugin CI`](.github/workflows/moodle-ci.yml) workflow, which runs
four security-relevant gates in addition to the standard Moodle linting,
PHPUnit and Behat suites:

| Gate | Tool | Configuration | Findings go to |
|------|------|---------------|----------------|
| **SAST** | [Semgrep](https://semgrep.dev) | Rulesets `p/php`, `p/security-audit`, `p/owasp-top-ten`, `p/secrets`. Severity *ERROR* and *WARNING*. | GitHub *Security → Code scanning* tab (SARIF upload) |
| **Dependency / IaC / secret scan** | [Trivy](https://trivy.dev) | Filesystem scan (`scan-type: fs`). Severities `CRITICAL,HIGH,MEDIUM`. `ignore-unfixed: true`. | GitHub *Security → Code scanning* tab + job-log table |
| **PHP code metrics** | [PHPDepend](https://pdepend.org) (`pdepend`) | Summary XML + JDepend XML + dependency pyramid + JDepend chart over `classes/`, `lib.php`, `index.php`, `settings.php`, `download_example.php`, `db/`. | Markdown summary in the workflow run + downloadable artefact (`pdepend-metrics`) |
| **Moodle Code Checker** | [PHPCS](https://github.com/squizlabs/PHP_CodeSniffer) (Moodle ruleset) | `moodle-plugin-ci codechecker --max-warnings 0`. | Workflow log; build fails on any warning. |

The SAST and dependency-scan jobs run on every supported branch (`main`,
`dev`) and on every pull request. The PHPDepend metrics job is
advisory — it never fails the build — but produces an at-a-glance
Markdown table in the workflow summary so reviewers can spot complexity
spikes during code review.

### Triaging Semgrep / Trivy findings

1. Open the *Security* tab on the GitHub repository.
2. Filter the *Code scanning* list by tool (`semgrep` or `trivy`).
3. For each finding either:
   - submit a patch under the regular pull-request process, **or**
   - dismiss it with a documented rationale (false positive, accepted
     risk, …) so future audits have an audit trail.
4. Suppression files live in the repository root:
   - `.semgrepignore` — paths excluded from Semgrep.
   - `.trivyignore` — CVE IDs suppressed for Trivy (with expiry comment).

## Third-party dependency hygiene

The plugin itself has **no PHP composer dependencies** of its own — it
relies entirely on Moodle core APIs. The CI workflow does, however,
pull a few build-time tools (moodle-plugin-ci, pdepend, semgrep,
trivy). These are pinned to known-good versions in
[.github/workflows/moodle-ci.yml](.github/workflows/moodle-ci.yml) and
upgraded explicitly through pull requests, not via floating tags.

The two PDF user manuals shipped under `docs/user-manual/` are
generated locally with `pandoc` + `weasyprint` and committed as static
artefacts; no JavaScript runs inside them, no remote assets are fetched
at render time.

## Coordinated disclosure & safe harbour

eLeDia GmbH operates a **good-faith safe-harbour policy** for security
researchers who:

- act within the scope defined above,
- avoid privacy violations, service disruption, destruction of data and
  degradation of user experience,
- give us a reasonable window to remediate before public disclosure,
- only interact with accounts they own or have explicit permission to
  access.

Reporters complying with this policy will not be subject to legal
action from eLeDia for the report itself. We are happy to credit you
in the changelog and security advisory unless you ask to remain
anonymous.