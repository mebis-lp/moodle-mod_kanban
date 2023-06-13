<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * View a kanban instance
 *
 * @package     mod_kanban
 * @copyright   2023, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);
$boardid = optional_param('boardid', 0, PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'kanban');

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/kanban:view', $context);

$kanban = $DB->get_record('kanban', ['id' => $cm->instance], '*', MUST_EXIST);

$PAGE->set_url(new moodle_url('/mod/kanban/view.php', ['id' => $id]));
$PAGE->set_title(get_string('pluginname', 'mod_kanban') . ' ' . $kanban->name);
$PAGE->set_heading($kanban->name);

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

echo $OUTPUT->header();

if (!empty($cm->groupmode)) {
    $OUTPUT->box(groups_print_activity_menu(
        $cm,
        new moodle_url('/mod/kanban/view.php', ['id' => $cm->id]),
        true
    ));
}

if (empty($boardid)) {
    $board = $DB->get_record(
        'kanban_board',
        ['kanban_instance' => $kanban->id, 'user' => 0, 'groupid' => 0, 'template' => 0],
         '*',
         MUST_EXIST
    );
    $boardid = $board->id;
}

echo $OUTPUT->render_from_template('mod_kanban/container', ['cmid' => $cm->id, 'id' => $boardid]);

$PAGE->requires->js_call_amd('mod_kanban/main', 'init', ['mod_kanban_render_container-' . $cm->id, $cm->id, $boardid]);

echo $OUTPUT->footer();
