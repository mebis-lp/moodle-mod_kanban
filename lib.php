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
 * Library for mod_kanban
 *
 * @package     mod_kanban
 * @copyright   2023, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds a new kanban instance
 *
 * @param stdClass $data kanban record
 * @return int
 */
function kanban_add_instance($data) : int {
    global $DB;
    $kanbanid = $DB->insert_record("kanban", $data);
    $boardid = $DB->insert_record("kanban_board", [
        'sequence' => '',
        'user' => 0,
        'groupid' => 0,
        'template' => 0,
        'timecreated' => time(),
        'timemodified' => time(),
        'kanban_instance' => $kanbanid
    ]);
    $columnnames = [
        get_string('todo', 'kanban') => '{}',
        get_string('doing', 'kanban') => '{}',
        get_string('done', 'kanban') => '{"autoclose": true}',
    ];
    $columnids = [];
    foreach ($columnnames as $columnname => $options) {
        $columnids[] = $DB->insert_record('kanban_column', [
            'title' => $columnname,
            'sequence' => '',
            'kanban_board' => $boardid,
            'options' => $options,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }
    $DB->update_record('kanban_board', ['id' => $boardid, 'sequence' => join(',', $columnids)]);
    return $kanbanid;
}

/**
 * Updates a kanban instance
 *
 * @param stdClass $data kanban record
 * @return int
 */
function kanban_update_instance($data) : int {
    global $DB;
    $data->id = $data->instance;
    return $DB->update_record("kanban", $data);
}

/**
 * Deletes a kanban instance and all boards
 *
 * @param integer $id kanban record
 * @return int
 */
function kanban_delete_instance($id) : int {
    global $DB;
    $transaction = $DB->start_delegated_transaction();
    $boards = $DB->get_records_menu('kanban_board', ['instance' => $id], '', 'id, id');
    $DB->delete_records('kanban', ['id' => $id]);

    list($sql, $params) = $DB->get_in_or_equal($boards, SQL_PARAMS_QM);

    $DB->delete_records_select('kanban_board', 'id ' . $sql, $params);
    $DB->delete_records_select('kanban_column', 'kanban_board ' . $sql, $params);
    $DB->delete_records_select('kanban_card', 'kanban_board ' . $sql, $params);

    return $transaction->allow_commit();
}

/**
 * Returns whether a feature is supported by this module.
 * @uses FEATURE_IDNUMBER
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function kanban_supports($feature) {
    switch($feature) {
        case FEATURE_IDNUMBER:
            return true;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_COMMUNICATION;
        default:
            return null;
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return \core\output\inplace_editable | null
 * @throws dml_exception
 */
function kanban_inplace_editable($itemtype, $itemid, $newvalue) {
    global $CFG, $DB, $USER;
    require_once($CFG->libdir. '/externallib.php');
    if ($itemtype == 'card') {
        $kanbancard = $DB->get_record('kanban_card', ['id' => $itemid], '*', MUST_EXIST);
        $kanbanboardid = $kanbancard->kanban_board;
    }
    if ($itemtype == 'column') {
        $kanbancolumn = $DB->get_record('kanban_column', ['id' => $itemid], '*', MUST_EXIST);
        $kanbanboardid = $kanbancolumn->kanban_board;
    }
    $kanbanboard = $DB->get_record('kanban_board', ['id' => $kanbanboardid], '*', MUST_EXIST);

    list ($course, $cminfo) = get_course_and_cm_from_instance($kanbanboard->kanban_instance, 'kanban');
    $context = context_module::instance($cminfo->id);
    external_api::validate_context($context);

    if ($itemtype == 'card') {
        require_capability('mod/kanban:managecards', $context);
    }

    if ($itemtype == 'column') {
        require_capability('mod/kanban:managecolumns', $context);
    }

    if (!(empty($kanbanboard->user) && empty($kanbanboard->groupid))) {
        if (!empty($kanbanboard->user) && $kanbanboard->user != $USER->id) {
            require_capability('mod/kanban:editallboards', $context);

        }
        if (!empty($kanbanboard->groupid) && $kanbanboard->groupid != groups_get_activity_group($cminfo)) {
            if ($cminfo->groupmode == SEPARATEGROUPS) {
                require_capability('mod/kanban:editallboards', $context);
            }
        }
    }

    $newtitle = clean_param($newvalue, PARAM_TEXT);
    $DB->update_record('kanban_' . $itemtype, ['id' => $itemid, 'title' => $newtitle, 'timemodified' => time()]);

    return new \core\output\inplace_editable('mod_kanban', $itemtype, $itemid, true, $newtitle, $newtitle, null, '');
}

/**
 * Delivers the attachment files for cards
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function kanban_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=[]) : ?bool {
    global $DB;
    require_course_login($course, true, $cm);

    // $args consists of:
    // - board id
    // - card id
    // - subfolders / filename

    if (count($args) < 3) {
        return false;
    }

    $boardid = intval($args[0]);

    // Check, whether the user is allowed to access this board.

    require_capability('mod/kanban:view', $context);

    $kanban_board = $DB->get_record('kanban_board', ['id' => $boardid, 'kanban_instance' => $cm->instance], '*', MUST_EXIST);

    mod_kanban\helper::check_permissions_for_user_or_group($kanban_board, $context, cm_info::create($cm));

    $fullpath = "/$context->id/mod_kanban/$filearea/" . implode('/', $args);

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, false, $options);
}
