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
 * Library functions for local_branchupload.
 *
 * @package    local_branchupload
 * @author     Christopher Reimann <christopher.reimann@eledia.de>
 * @copyright  2026 eLeDia GmbH, Berlin {@link https://eledia.de}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extends the global navigation tree by adding a prominent link to the upload page.
 *
 * The node is added as a top-level item with showinflatnavigation enabled,
 * making it visible in the Boost theme's navigation drawer.
 *
 * @param global_navigation $navigation The global navigation tree.
 */
function local_branchupload_extend_navigation(global_navigation $navigation): void {
    if (has_capability('local/branchupload:upload', context_system::instance())) {
        $node = $navigation->add(
            get_string('uploadusers', 'local_branchupload'),
            new moodle_url('/local/branchupload/index.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_branchupload',
            new pix_icon('i/upload', '')
        );
        $node->showinflatnavigation = true;
    }
}

/**
 * Extends the front page navigation for users with upload capability.
 *
 * @param navigation_node $frontpage The front page navigation node.
 * @param stdClass $course The front page course object.
 * @param context_course $context The front page course context.
 */
function local_branchupload_extend_navigation_frontpage(
    navigation_node $frontpage,
    stdClass $course,
    context_course $context
): void {
    if (has_capability('local/branchupload:upload', context_system::instance())) {
        $frontpage->add(
            get_string('uploadusers', 'local_branchupload'),
            new moodle_url('/local/branchupload/index.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_branchupload_frontpage',
            new pix_icon('i/upload', '')
        );
    }
}

/**
 * Adds a link to the user profile navigation for users with upload capability.
 *
 * @param \core_user\output\myprofile\tree $tree The myprofile navigation tree.
 * @param stdClass $user The user whose profile is being viewed.
 * @param bool $iscurrentuser Whether the profile belongs to the current user.
 * @param stdClass|null $course The course context (if any).
 */
function local_branchupload_myprofile_navigation(
    \core_user\output\myprofile\tree $tree,
    stdClass $user,
    bool $iscurrentuser,
    ?stdClass $course
): void {
    if ($iscurrentuser && has_capability('local/branchupload:upload', context_system::instance())) {
        $node = new \core_user\output\myprofile\node(
            'miscellaneous',
            'branchupload',
            get_string('uploadusers', 'local_branchupload'),
            null,
            new moodle_url('/local/branchupload/index.php'),
            null,
            null,
            'local_branchupload'
        );
        $tree->add_node($node);
    }
}
