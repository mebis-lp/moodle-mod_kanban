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
 * Class for delivering kanban content
 *
 * @package    mod_kanban
 * @copyright  2023 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kanban\external;

// Compatibility with Moodle < 4.2.
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/lib/externallib.php');

use coding_exception;
use context_module;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use moodle_exception;
use required_capability_exception;
use restricted_context_exception;
use mod_kanban\helper;

/**
 * Class for delivering kanban content
 *
 * @copyright  2023 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_kanban_content extends external_api {

    /**
     * Returns description of method parameters for the get_kanban_content webservice function.
     *
     * @return external_function_parameters
     */
    public static function get_kanban_content_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'course module id', VALUE_REQUIRED),
            'boardid' => new external_value(PARAM_INT, 'board id', VALUE_REQUIRED),
            'timestamp' => new external_value(PARAM_INT, 'only get values modified after this timestamp', VALUE_OPTIONAL, 0),
        ]);
    }

    /**
     * Definition of return values of the get_kanban_content webservice function.
     *
     * @return external_single_structure
     */
    public static function get_kanban_content_returns(): external_single_structure {
        return
            new external_single_structure(
                [
                    'board' => new external_single_structure([
                        'id' => new external_value(PARAM_INT, 'board id'),
                        'sequence' => new external_value(PARAM_TEXT, 'order of the columns in the board'),
                        'timestamp' => new external_value(PARAM_INT, 'timestamp'),
                        'cmid' => new external_value(PARAM_INT, 'cmid'),
                        'userid' => new external_value(PARAM_INT, 'current user id'),
                    ]),
                    'columns' => new external_multiple_structure(
                        new external_single_structure(
                            [
                                'id' => new external_value(PARAM_INT, 'column id'),
                                'title' => new external_value(PARAM_TEXT, 'column title'),
                                'sequence' => new external_value(PARAM_TEXT, 'order of the cards in the column'),
                            ],
                            '',
                            VALUE_OPTIONAL
                        )
                    ),
                    'cards' => new external_multiple_structure(
                        new external_single_structure(
                            [
                                'id' => new external_value(PARAM_INT, 'card id'),
                                'title' => new external_value(PARAM_TEXT, 'card title'),
                                'kanban_column' => new external_value(PARAM_INT, 'column'),
                                'assignees' => new external_multiple_structure(
                                        new external_value(PARAM_INT, 'user id'),
                                        VALUE_OPTIONAL
                                ),
                            ],
                            '',
                            VALUE_OPTIONAL
                        )
                    ),
                    'users' => new external_multiple_structure(
                        new external_single_structure(
                            [
                                'id' => new external_value(PARAM_INT, 'user id'),
                                'fullname' => new external_value(PARAM_TEXT, 'user fullname'),
                                'userpicture' => new external_value(PARAM_RAW, 'user picture'),
                            ],
                            '',
                            VALUE_OPTIONAL
                        ),
                        '',
                        VALUE_OPTIONAL
                    ),
                    'capabilities' => new external_multiple_structure(
                        new external_single_structure(
                            [
                                'id' => new external_value(PARAM_TEXT, 'capability name'),
                                'value' => new external_value(PARAM_BOOL, 'capability value'),
                            ],
                            '',
                            VALUE_OPTIONAL
                        ),
                    ),
                ]
            );
    }

    /**
     * This method returns the requested data.
     *
     * @param int $cmid the course module id of the kanban board
     * @param int $boardid the id of the kanban board
     * @param int $timestamp the timestamp of the state present in the frontend
     * @return array The requested content, divided into board, columns and cards
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function get_kanban_content(int $cmid, int $boardid, int $timestamp = 0): array {
        $params = self::validate_parameters(self::get_kanban_content_parameters(), [
            'cmid' => $cmid,
            'boardid' => $boardid,
            'timestamp' => $timestamp
        ]);
        $cmid = $params['cmid'];
        $boardid = $params['boardid'];
        $timestamp = $params['timestamp'];
        return self::execute($cmid, $boardid, $timestamp);
    }

    /**
     * Get kanban content from database.
     *
     * @param int $cmid the course module id of the kanban board
     * @param int $boardid the id of the kanban board
     * @param int $timestamp the timestamp of the state present in the frontend
     * @param bool $asupdate whether to format content as update for StateMananger
     * @return array The requested content, divided into board, columns and cards
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function execute(int $cmid, int $boardid, int $timestamp = 0, bool $asupdate = false): array {
        global $DB, $OUTPUT, $USER;
        list($course, $cminfo) = get_course_and_cm_from_cmid($cmid);
        $context = context_module::instance($cmid);
        self::validate_context($context);
        require_capability('mod/kanban:view', $context);

        // Get the values of some capabilities for output.
        $capabilities = [
            'addcard' => has_capability('mod/kanban:addcard', $context),
            'managecards' => has_capability('mod/kanban:managecards', $context),
            'assignself' => has_capability('mod/kanban:assignself', $context),
            'assignothers' => has_capability('mod/kanban:assignothers', $context),
            'moveassignedcards' => has_capability('mod/kanban:moveassignedcards', $context),
            'moveallcards' => has_capability('mod/kanban:moveallcards', $context),
            'managecolumns' => has_capability('mod/kanban:managecolumns', $context),
            'editallboards' => has_capability('mod/kanban:editallboards', $context),
        ];

        $kanban = $DB->get_record('kanban', ['id' => $cminfo->instance]);
        $kanbanboard = $DB->get_record('kanban_board', ['kanban_instance' => $kanban->id, 'id' => $boardid], '*', MUST_EXIST);
        if (!(empty($kanbanboard->user) && empty($kanbanboard->groupid))) {
            if (!empty($kanbanboard->user) && $kanbanboard->user != $USER->id) {
                require_capability('mod/kanban:viewallboards', $context);
                foreach ($capabilities as $cap => $value) {
                    $capabilities[$cap] &= $value;
                }
            }
            if (!empty($kanbanboard->groupid) && $kanbanboard->groupid != groups_get_activity_group($cminfo)) {
                if ($cminfo->groupmode == SEPARATEGROUPS) {
                    require_capability('mod/kanban:viewallboards', $context);
                    foreach ($capabilities as $cap => $value) {
                        $capabilities[$cap] &= $value;
                    }
                }
            }
        }

        $kanbanboard->timestamp = time();
        $kanbanboard->cmid = $cmid;
        $kanbanboard->userid = $USER->id;

        $kanbancards = [];
        $kanbanassignees = [];
        $kanbanusers = [];

        $sql = 'kanban_board = :board AND timemodified > :timestamp';
        $params['board'] = $boardid;
        $params['timestamp'] = $timestamp;

        $kanbancolumns = $DB->get_records_select('kanban_column', $sql, $params);
        $kanbancards = $DB->get_records_select('kanban_card', $sql, $params);
        $kanbancardids = array_map(function ($v) {
            return $v->id;
        }, $kanbancards);
        if (!empty($kanbancardids)) {
            [$sql, $params] = $DB->get_in_or_equal($kanbancardids);
            $sql = 'kanban_card ' . $sql;
            $kanbanassigneesraw = $DB->get_records_select('kanban_assignee', $sql, $params);
            $kanbanassignees = [];
            $kanbanuserids = [];
            foreach ($kanbanassigneesraw as $assignee) {
                $kanbanassignees[$assignee->kanban_card][] = $assignee->user;
                $kanbanuserids[] = $assignee->user;
            }
            foreach ($kanbancards as $key => $card) {
                if (empty($kanbanassignees[$card->id])) {
                    $kanbanassignees[$card->id] = [];
                }
                $kanbancards[$key]->assignees = $kanbanassignees[$card->id];
            }
            $users = get_enrolled_users($context, '', $kanbanboard->groupid);
            foreach ($users as $user) {
                $kanbanusers[] = [
                    'id' => $user->id,
                    'fullname' => fullname($user),
                    'userpicture' => $OUTPUT->user_picture($user, ['link' => false]),
                ];
            }
        }

        $caps = [];

        foreach ($capabilities as $k => $v) {
            $caps[] = ['id' => $k, 'value' => $v];
        }

        if ($asupdate) {
            
        }
        return [
            'board' => $kanbanboard,
            'columns' => $kanbancolumns,
            'cards' => $kanbancards,
            'users' => $kanbanusers,
            'capabilities' => $caps,
        ];
    }
}
