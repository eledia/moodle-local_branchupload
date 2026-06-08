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
 * Main page for local_branchupload — branch office user upload.
 *
 * Implements a three-step flow: Upload CSV → Preview → Process & Results.
 *
 * @package    local_branchupload
 * @author     Christopher Reimann <christopher.reimann@eledia.de>
 * @copyright  2026 eLeDia GmbH, Berlin {@link https://eledia.de}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

require_login();
$context = context_system::instance();
require_capability('local/branchupload:upload', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/branchupload/index.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('uploadusers', 'local_branchupload'));
$PAGE->set_heading(get_string('uploadusers', 'local_branchupload'));

$step = optional_param('step', 'upload', PARAM_ALPHA);
$iid = optional_param('iid', 0, PARAM_INT);

// Step 1: Upload form.
if ($step === 'upload') {
    $form = new \local_branchupload\form\upload_form();

    if ($form->is_cancelled()) {
        redirect(new moodle_url('/'));
    }

    if ($data = $form->get_data()) {
        // Load CSV content.
        $iid = csv_import_reader::get_new_iid('local_branchupload');
        $cir = new csv_import_reader($iid, 'local_branchupload');
        $content = $form->get_file_content('csvfile');
        $readcount = $cir->load_csv_content($content, $data->encoding, $data->delimiter_name);

        if ($cir->get_error()) {
            echo $OUTPUT->header();
            echo $OUTPUT->notification(
                get_string('error_csvparse', 'local_branchupload'),
                \core\output\notification::NOTIFY_ERROR
            );
            $form->display();
            echo $OUTPUT->footer();
            die();
        }

        $columns = $cir->get_columns();
        if (empty($columns)) {
            echo $OUTPUT->header();
            echo $OUTPUT->notification(
                get_string('error_emptycsv', 'local_branchupload'),
                \core\output\notification::NOTIFY_ERROR
            );
            $form->display();
            echo $OUTPUT->footer();
            die();
        }

        // Redirect to preview step.
        redirect(new moodle_url('/local/branchupload/index.php', [
            'step' => 'preview',
            'iid' => $iid,
            'delimiter_name' => $data->delimiter_name,
            'encoding' => $data->encoding,
        ]));
    }

    echo $OUTPUT->header();

    // Step indicator — semantic <ol> with aria-current on the active step (mirrors the
    // structure in preview.mustache and results.mustache for WCAG 2.2 AA compliance).
    $stepitems = [
        \html_writer::tag(
            'li',
            \html_writer::tag(
                'span',
                \html_writer::span(
                    get_string('step_current', 'local_branchupload') . ': ',
                    'visually-hidden'
                ) . '1 ' . get_string('uploadstep', 'local_branchupload'),
                ['class' => 'badge bg-primary rounded-pill px-3 py-2']
            ),
            ['aria-current' => 'step']
        ),
        \html_writer::tag('li', '&rarr;', ['aria-hidden' => 'true', 'class' => 'text-muted']),
        \html_writer::tag(
            'li',
            \html_writer::tag(
                'span',
                \html_writer::span(
                    get_string('step_upcoming', 'local_branchupload') . ': ',
                    'visually-hidden'
                ) . '2 ' . get_string('previewstep', 'local_branchupload'),
                ['class' => 'badge bg-secondary rounded-pill px-3 py-2']
            )
        ),
        \html_writer::tag('li', '&rarr;', ['aria-hidden' => 'true', 'class' => 'text-muted']),
        \html_writer::tag(
            'li',
            \html_writer::tag(
                'span',
                \html_writer::span(
                    get_string('step_upcoming', 'local_branchupload') . ': ',
                    'visually-hidden'
                ) . '3 ' . get_string('resultstep', 'local_branchupload'),
                ['class' => 'badge bg-secondary rounded-pill px-3 py-2']
            )
        ),
    ];
    echo \html_writer::tag('ol', implode('', $stepitems), [
        'class' => 'local-branchupload-steps list-unstyled d-flex justify-content-center align-items-center gap-2 mb-4',
        'aria-label' => get_string('step_indicator_label', 'local_branchupload'),
    ]);

    $form->display();
    echo $OUTPUT->footer();
    die();
}

// Step 2: Preview.
if ($step === 'preview') {
    $iid = required_param('iid', PARAM_INT);
    $delimname = required_param('delimiter_name', PARAM_ALPHA);
    $encoding = required_param('encoding', PARAM_ALPHANUMEXT);

    $cir = new csv_import_reader($iid, 'local_branchupload');
    $columns = $cir->get_columns();

    if (empty($columns)) {
        $cir->close();
        $cir->cleanup();
        redirect(
            new moodle_url('/local/branchupload/index.php'),
            get_string('error_emptycsv', 'local_branchupload'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    $processor = new \local_branchupload\process($cir, $columns);

    // Validate configuration.
    $configerrors = $processor->validate_configuration();
    if (!empty($configerrors)) {
        $cir->close();
        $cir->cleanup();
        echo $OUTPUT->header();
        foreach ($configerrors as $error) {
            echo $OUTPUT->notification($error, \core\output\notification::NOTIFY_ERROR);
        }
        echo $OUTPUT->footer();
        die();
    }

    // Validate columns.
    $columnerrors = $processor->validate_columns();
    if (!empty($columnerrors)) {
        $cir->close();
        $cir->cleanup();
        echo $OUTPUT->header();
        foreach ($columnerrors as $error) {
            echo $OUTPUT->notification($error, \core\output\notification::NOTIFY_ERROR);
        }
        echo $OUTPUT->footer();
        die();
    }

    // Check max users.
    $previewdata = $processor->preview();
    $maxusers = $processor->get_max_users();
    if ($maxusers > 0 && $previewdata['summary']['total'] > $maxusers) {
        $cir->close();
        $cir->cleanup();
        echo $OUTPUT->header();
        echo $OUTPUT->notification(
            get_string('error_maxusers', 'local_branchupload', (object) [
                'count' => $previewdata['summary']['total'],
                'max' => $maxusers,
            ]),
            \core\output\notification::NOTIFY_ERROR
        );
        echo $OUTPUT->footer();
        die();
    }

    // Prepare template context.
    $templatecontext = [
        'isadmin' => $processor->is_admin(),
        'uploaderbranch' => $processor->get_uploader_branch() ?? '',
        'summary' => $previewdata['summary'],
        'rows' => [],
        'hasvalidrows' => ($previewdata['summary']['valid'] > 0),
        'formaction' => (new moodle_url('/local/branchupload/index.php'))->out(false),
        'cancelurl' => (new moodle_url('/local/branchupload/index.php'))->out(false),
        'sesskey' => sesskey(),
        'iid' => $iid,
        'delimiter_name' => $delimname,
        'encoding' => $encoding,
        'headers' => $processor->get_column_headers(),
    ];

    // Map rows for template.
    foreach ($previewdata['rows'] as $row) {
        $templaterow = [
            'rownumber' => $row['rownumber'],
            'email' => $row['email'],
            'branch' => $row['branch'],
            'orgunit' => $row['orgunit'],
            'lastname' => $row['lastname'],
            'firstname' => $row['firstname'],
            'remove' => $row['remove'],
            'cohorts' => $row['cohorts'],
            'oldemail' => $row['oldemail'] ?? '',
            'haserrors' => !empty($row['errors']),
            'haswarnings' => !empty($row['warnings']),
            'errors' => array_map(function ($e) {
                return ['message' => $e];
            }, $row['errors'] ?? []),
            'warnings' => array_map(function ($w) {
                return ['message' => $w];
            }, $row['warnings'] ?? []),
        ];

        // Action labels for valid rows.
        if (empty($row['errors'])) {
            $action = $row['action'] ?? '';
            $templaterow['action'] = $action;
            $templaterow['iscreate'] = ($action === 'create');
            $templaterow['isupdate'] = ($action === 'update');
            $templaterow['issuspend'] = ($action === 'suspend');
            $templaterow['isdelete'] = ($action === 'delete');
            $actionstringmap = [
                'create' => 'status_create',
                'update' => 'status_update',
                'suspend' => 'status_suspend',
                'delete' => 'status_delete',
            ];
            $templaterow['actionlabel'] = isset($actionstringmap[$action])
                ? get_string($actionstringmap[$action], 'local_branchupload')
                : '';
        }

        $templatecontext['rows'][] = $templaterow;
    }

    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_branchupload/preview', $templatecontext);
    echo $OUTPUT->footer();
    die();
}

// Step 3: Process.
if ($step === 'process') {
    require_sesskey();

    $iid = required_param('iid', PARAM_INT);
    $delimname = required_param('delimiter_name', PARAM_ALPHA);
    $encoding = required_param('encoding', PARAM_ALPHANUMEXT);

    $cir = new csv_import_reader($iid, 'local_branchupload');
    $columns = $cir->get_columns();

    if (empty($columns)) {
        $cir->close();
        $cir->cleanup();
        redirect(
            new moodle_url('/local/branchupload/index.php'),
            get_string('error_emptycsv', 'local_branchupload'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    $processor = new \local_branchupload\process($cir, $columns);

    // Re-validate before processing.
    $configerrors = $processor->validate_configuration();
    $columnerrors = $processor->validate_columns();
    if (!empty($configerrors) || !empty($columnerrors)) {
        $cir->close();
        $cir->cleanup();
        redirect(
            new moodle_url('/local/branchupload/index.php'),
            implode(' ', array_merge($configerrors, $columnerrors)),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    // Execute processing.
    $results = $processor->execute();

    // Prepare template context.
    $templatecontext = [
        'stats' => $results['stats'],
        'rows' => [],
        'backurl' => (new moodle_url('/local/branchupload/index.php'))->out(false),
        'headers' => $processor->get_column_headers(),
    ];

    foreach ($results['rows'] as $row) {
        $status = $row['status'] ?? 'error';
        $templatecontext['rows'][] = [
            'rownumber' => $row['rownumber'],
            'email' => $row['email'],
            'lastname' => $row['lastname'],
            'firstname' => $row['firstname'],
            'branch' => $row['branch'],
            'statusmessage' => $row['statusmessage'] ?? '',
            'info' => $row['info'] ?? '',
            'iscreated' => ($status === 'created'),
            'isupdated' => ($status === 'updated'),
            'issuspended' => ($status === 'suspended'),
            'isdeleted' => ($status === 'deleted'),
            'isskipped' => ($status === 'skipped'),
            'iserror' => ($status === 'error'),
        ];
    }

    echo $OUTPUT->header();

    // Overall notification.
    $totalprocessed = $results['stats']['created'] + $results['stats']['updated']
        + $results['stats']['suspended'] + $results['stats']['deleted'];
    if ($results['stats']['errors'] === 0) {
        echo $OUTPUT->notification(
            get_string('results_summary', 'local_branchupload'),
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        echo $OUTPUT->notification(
            get_string('results_summary', 'local_branchupload'),
            \core\output\notification::NOTIFY_WARNING
        );
    }

    echo $OUTPUT->render_from_template('local_branchupload/results', $templatecontext);
    echo $OUTPUT->footer();
    die();
}

// Unknown step — redirect back.
redirect(new moodle_url('/local/branchupload/index.php'));
