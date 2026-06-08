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
 * Serves the example CSV file with correct UTF-8 headers.
 *
 * @package    local_branchupload
 * @author     Christopher Reimann <christopher.reimann@eledia.de>
 * @copyright  2026 eLeDia GmbH, Berlin {@link https://eledia.de}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

require_login();

$context = context_system::instance();
require_capability('local/branchupload:upload', $context);

// Build the example CSV on the fly so renamed column headers (configured via
// Site administration → Plugins → Local plugins → Branch office user upload)
// are reflected in the downloaded template.
$columnconfig = new \local_branchupload\column_config();
$headers = array_values($columnconfig->all_headers());

// The example rows are intentionally generic — admins replace them with their
// own data — but they exercise every supported column so the file doubles as
// a self-documenting reference for the optional fields (remove, cohorts,
// oldemail).
$rows = [
    [
        'max.mustermann@example.de',
        'GmndAchbrg',
        'Bauverwaltung',
        'Mustermann',
        'Max',
        '',
        'KohorteSchulungA',
        '',
    ],
    [
        'erika.neu@example.de',
        'GmndAchbrg',
        'Finanzen',
        'Musterfrau',
        'Erika',
        '',
        'KohorteSchulungA|KohorteSchulungB',
        'erika.musterfrau@example.de',
    ],
    [
        'hans.beispiel@example.de',
        'GmndAchbrg',
        'Ordnungsamt',
        'Beispiel',
        'Hans',
        '1',
        '',
        '',
    ],
];

// Release the Moodle session lock before streaming the file so concurrent
// requests for this user are not blocked while the download is in flight.
\core\session\manager::write_close();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="example.csv"');
header('Cache-Control: private, no-store');
header('Pragma: no-cache');

// Write a UTF-8 BOM so spreadsheet applications (notably Excel on Windows)
// detect the encoding correctly when opening the file directly.
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, $headers, ';', '"', '\\');
foreach ($rows as $row) {
    fputcsv($out, $row, ';', '"', '\\');
}
fclose($out);
