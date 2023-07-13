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

use mod_kanban\helper;

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

$groupselector = '';
$groupid = 0;

if (!empty($cm->groupmode)) {
    $groupid = groups_get_activity_group($cm, true);
}

$userid = optional_param('user', 0, PARAM_INT);
if (
    $kanban->userboards == MOD_KANBAN_USERBOARDS_ONLY &&
    empty($groupid) &&
    empty($userid)
) {
    $userid = $USER->id;
}

$heading = get_string('courseboard', 'mod_kanban');

if (!empty($groupid)) {
    $heading = get_string('groupboard', 'mod_kanban', groups_get_group_name($groupid));
}

if (!empty($userid)) {
    $boarduser = core_user::get_user($userid);
    $heading = get_string('userboard', 'mod_kanban', fullname($boarduser));
    $groupid = 0;
}

if (empty($boardid)) {
    $board = $DB->get_record(
        'kanban_board',
        ['kanban_instance' => $kanban->id, 'user' => $userid, 'groupid' => $groupid, 'template' => 0],
         '*'
    );
    if (!$board) {
        $boardid = mod_kanban\helper::create_new_board($kanban->id, $userid, $groupid);
        $board = $DB->get_record('kanban_board', ['id' => $boardid], '*');
    } else {
        $boardid = $board->id;
    }
} else {
    $board = $DB->get_record('kanban_board', ['kanban_instance' => $kanban->id, 'id' => $boardid], '*');
    helper::check_permissions_for_user_or_group($board, $context, $cm, helper::MOD_KANBAN_VIEW);
}

if (!empty($cm->groupmode)) {
    $groupselector = groups_print_activity_menu(
        $cm,
        new moodle_url('/mod/kanban/view.php', ['id' => $cm->id]),
        true,
        $kanban->userboards == MOD_KANBAN_USERBOARDS_ONLY
    );
    $allowedgroups = groups_get_activity_allowed_groups($cm);
    if (!$allowedgroups) {
        if ($kanban->userboards !== MOD_KANBAN_NOUSERBOARDS) {
            $groupselector = '';
        } else {
            throw new \moodle_exception('nogroupavailable', 'mod_kanban');
        }
    } else if (count($allowedgroups) < 2) {
        if (!empty($groupid)) {
            $groupselector = '';
        } else {
            $group = array_pop($allowedgroups);
            $groupselector = $OUTPUT->render_from_template(
                'mod_kanban/groupbutton',
                [
                    'cmid' => $cm->id,
                    'groupid' => $group->id,
                    'groupname' => $group->name,
                ]
            );
        }
    }
}

echo $OUTPUT->render_from_template(
    'mod_kanban/container',
    [
        'cmid' => $cm->id,
        'id' => $boardid,
        'userid' => $USER->id,
        'groupselector' => $groupselector,
        'groupmode' => $cm->groupmode != NOGROUPS && !empty($groupselector),
        'userboards' => $kanban->userboards != MOD_KANBAN_NOUSERBOARDS,
        'userboardsonly' => $kanban->userboards == MOD_KANBAN_USERBOARDS_ONLY,
        'showallusers' => has_capability('mod/kanban:viewallboards', $context),
        'ismyuserboard' => $board->user == $USER->id,
        'heading' => $heading,
    ]
);

$PAGE->requires->js_call_amd('mod_kanban/main', 'init', ['mod_kanban_render_container-' . $cm->id, $cm->id, $boardid]);

echo $OUTPUT->footer();
