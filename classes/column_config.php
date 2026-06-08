<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Resolves the configured CSV column headers for local_branchupload.
 *
 * Administrators can override every column header via plugin settings
 * (Site administration → Plugins → Local plugins → Branch office user
 * upload). This class is the single source of truth that turns canonical
 * keys (used throughout the processor) into the actual case-sensitive
 * header string that must appear in the uploaded CSV file.
 *
 * @package    local_branchupload
 * @author     Christopher Reimann <christopher.reimann@eledia.de>
 * @copyright  2026 eLeDia GmbH, Berlin {@link https://eledia.de}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_branchupload;

/**
 * Resolves the configured CSV column header for each canonical field.
 *
 * **Canonical keys** are the internal, English, ASCII-only identifiers used
 * throughout the codebase. They never change between releases:
 *
 *     email · branch · orgunit · lastname · firstname · remove · cohorts · oldemail
 *
 * **Default headers** depend on the *site language* (`$CFG->lang`) and are
 * resolved at runtime from the `col_default_<key>` language strings, so a
 * German site still sees `Behörde / Organisationseinheit / Löschen / ...`
 * out of the box, while an English site sees `Branch / OrgUnit / Remove / ...`.
 *
 * **Configured overrides** stored at `local_branchupload/col_<key>` win over
 * the language defaults. An empty value falls back to the language default.
 */
final class column_config {
    /**
     * Canonical keys whose corresponding column MUST be present in the CSV.
     *
     * @var string[]
     */
    public const REQUIRED_KEYS = ['email', 'branch', 'orgunit', 'lastname', 'firstname'];

    /**
     * Canonical keys whose corresponding column MAY be present in the CSV.
     *
     * @var string[]
     */
    public const OPTIONAL_KEYS = ['remove', 'cohorts', 'oldemail'];

    /**
     * Resolved header for each canonical key.
     *
     * @var array<string,string>
     */
    private array $headers;

    /**
     * All canonical keys, in display order (required first, optional last).
     *
     * @return string[]
     */
    public static function canonical_keys(): array {
        return array_merge(self::REQUIRED_KEYS, self::OPTIONAL_KEYS);
    }

    /**
     * Resolve the site-language default header for one canonical key.
     *
     * Reads the `col_default_<key>` language string in the *site* language
     * (`$CFG->lang`, not `current_language()`) so the defaults are consistent
     * across the whole installation regardless of who is currently logged in.
     *
     * @param string $key One of the canonical keys.
     * @return string The localised default header.
     * @throws \coding_exception If the key is unknown.
     */
    public static function default_for_key(string $key): string {
        global $CFG;
        if (!in_array($key, self::canonical_keys(), true)) {
            throw new \coding_exception("Unknown column key: $key");
        }
        $sitelang = !empty($CFG->lang) ? $CFG->lang : 'en';
        return get_string_manager()->get_string(
            'col_default_' . $key,
            'local_branchupload',
            null,
            $sitelang
        );
    }

    /**
     * Resolve the site-language defaults for every canonical key.
     *
     * @return array<string,string> Canonical key => localised default header.
     */
    public static function defaults(): array {
        $defaults = [];
        foreach (self::canonical_keys() as $key) {
            $defaults[$key] = self::default_for_key($key);
        }
        return $defaults;
    }

    /**
     * Construct from current plugin configuration, with optional in-memory overrides.
     *
     * Overrides exist primarily to make unit tests deterministic without having
     * to mutate global config; passing an empty/null value for a key falls
     * back to the configured value, which in turn falls back to the language
     * default for the configured site language.
     *
     * @param array|null $overrides Optional map of canonical-key => header.
     *                              Pass null (the default) to read from `get_config()`.
     */
    public function __construct(?array $overrides = null) {
        $this->headers = [];
        foreach (self::canonical_keys() as $key) {
            if (is_array($overrides) && isset($overrides[$key]) && $overrides[$key] !== '') {
                $this->headers[$key] = (string) $overrides[$key];
                continue;
            }
            $configured = get_config('local_branchupload', 'col_' . $key);
            $this->headers[$key] = ($configured !== false && $configured !== '')
                ? (string) $configured
                : self::default_for_key($key);
        }
    }

    /**
     * Get the configured header for one canonical key.
     *
     * @param string $key One of the canonical keys.
     * @return string The configured header.
     * @throws \coding_exception If the key is unknown.
     */
    public function header(string $key): string {
        if (!array_key_exists($key, $this->headers)) {
            throw new \coding_exception("Unknown column key: $key");
        }
        return $this->headers[$key];
    }

    /**
     * Get all configured headers, canonical key => header.
     *
     * @return array<string,string>
     */
    public function all_headers(): array {
        return $this->headers;
    }

    /**
     * Get the headers for required columns only.
     *
     * @return array<string,string>
     */
    public function required_headers(): array {
        return array_intersect_key($this->headers, array_flip(self::REQUIRED_KEYS));
    }

    /**
     * Get the headers for optional columns only.
     *
     * @return array<string,string>
     */
    public function optional_headers(): array {
        return array_intersect_key($this->headers, array_flip(self::OPTIONAL_KEYS));
    }

    /**
     * Build a canonical-key => CSV-column-index map for an actual CSV header row.
     *
     * Matching is case-insensitive and whitespace-trim-insensitive, exactly
     * like the previous hard-coded validation logic. Columns that are not
     * present in the CSV map to null.
     *
     * @param string[] $csvheaders The CSV's actual header row (first row).
     * @return array<string,int|null> Canonical key => 0-based column index, or null if absent.
     */
    public function build_index(array $csvheaders): array {
        $normalised = [];
        foreach ($csvheaders as $i => $header) {
            $normalised[\core_text::strtolower(trim((string) $header))] = (int) $i;
        }

        $index = [];
        foreach ($this->headers as $key => $configured) {
            $needle = \core_text::strtolower(trim($configured));
            $index[$key] = $normalised[$needle] ?? null;
        }

        return $index;
    }

    /**
     * Return the canonical keys whose configured header is missing from the CSV.
     *
     * @param string[] $csvheaders The CSV's actual header row.
     * @return string[] Subset of {@see self::REQUIRED_KEYS} that are absent.
     */
    public function missing_required(array $csvheaders): array {
        $index = $this->build_index($csvheaders);
        $missing = [];
        foreach (self::REQUIRED_KEYS as $key) {
            if ($index[$key] === null) {
                $missing[] = $key;
            }
        }
        return $missing;
    }

    /**
     * Pretty-printed header list for one column group, e.g. for help text.
     *
     * @param string[] $keys Canonical keys to include.
     * @param string $sep Separator, default ", ".
     * @return string
     */
    public function header_list(array $keys, string $sep = ', '): string {
        $parts = [];
        foreach ($keys as $key) {
            if (isset($this->headers[$key])) {
                $parts[] = $this->headers[$key];
            }
        }
        return implode($sep, $parts);
    }
}
