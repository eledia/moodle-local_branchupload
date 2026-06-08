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
 * CSV upload form for local_branchupload.
 *
 * @package    local_branchupload
 * @author     Christopher Reimann <christopher.reimann@eledia.de>
 * @copyright  2026 eLeDia GmbH, Berlin {@link https://eledia.de}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_branchupload\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form for uploading the CSV file.
 */
class upload_form extends \moodleform {
    /**
     * Define the form elements.
     */
    public function definition(): void {
        global $CFG;

        $mform = $this->_form;

        // Cap the upload at 5 MiB by default — a CSV with the documented columns is
        // typically a handful of KiB, even for thousands of rows.
        $maxbytes = get_max_upload_file_size(
            isset($CFG->maxbytes) ? (int) $CFG->maxbytes : 0,
            5 * 1024 * 1024
        );

        // CSV file upload.
        $mform->addElement(
            'filepicker',
            'csvfile',
            get_string('csvfile', 'local_branchupload'),
            null,
            [
                'accepted_types' => ['.csv', 'text/csv', 'text/plain'],
                'maxbytes' => $maxbytes,
            ]
        );
        $mform->addRule('csvfile', null, 'required', null, 'client');
        $mform->addRule('csvfile', null, 'required', null, 'server');
        $mform->addHelpButton('csvfile', 'csvfile', 'local_branchupload');

        // CSV delimiter.
        $choices = \csv_import_reader::get_delimiter_list();
        $mform->addElement(
            'select',
            'delimiter_name',
            get_string('csvdelimiter', 'local_branchupload'),
            $choices
        );
        $mform->setDefault('delimiter_name', 'semicolon');

        // File encoding.
        $choices = \core_text::get_encodings();
        $mform->addElement(
            'select',
            'encoding',
            get_string('encoding', 'local_branchupload'),
            $choices
        );
        $mform->setDefault('encoding', 'UTF-8');

        // Column info — built from the *configured* headers so renamed columns
        // are reflected verbatim in the user-facing instructions.
        $columnconfig = new \local_branchupload\column_config();
        $columnsinfostring = get_string(
            'csvcolumns_info',
            'local_branchupload',
            (object) $columnconfig->all_headers()
        );
        $mform->addElement(
            'static',
            'csvcolumns_info',
            '',
            \html_writer::div($columnsinfostring, 'alert alert-info')
        );

        // Example CSV download link.
        $exampleurl = new \moodle_url('/local/branchupload/download_example.php');
        $downloadicon = \html_writer::tag('i', '', [
            'class' => 'fa fa-download me-1',
            'aria-hidden' => 'true',
        ]);
        $examplelink = \html_writer::link(
            $exampleurl,
            $downloadicon . s(get_string('examplecsv', 'local_branchupload')),
            ['class' => 'btn btn-outline-secondary btn-sm']
        );
        $mform->addElement('static', 'examplecsv', '', $examplelink);

        $this->add_action_buttons(true, get_string('uploadstep', 'local_branchupload'));
    }
}
