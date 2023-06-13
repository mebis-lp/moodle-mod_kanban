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
 * Class for modifying kanban content
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
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use moodle_exception;
use required_capability_exception;
use restricted_context_exception;
use mod_kanban\helper;

/**
 * Class for modifying kanban content
 *
 * @copyright  2023 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class change_kanban_content extends external_api {

    /**
     * Returns description of method parameters for the add_column function.
     *
     * @return external_function_parameters
     */
    public static function add_column_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'course module id', VALUE_REQUIRED),
            'boardid' => new external_value(PARAM_INT, 'board id', VALUE_REQUIRED),
            'data' => new external_single_structure([
                'title' => new external_value(
                    PARAM_TEXT,
                    'title of the new column',
                    VALUE_OPTIONAL,
                    get_string('newcolumn', 'mod_kanban')
                ),
                'aftercol' => new external_value(PARAM_INT, 'insert column after this id', VALUE_OPTIONAL, 0)
            ]),
        ]);
    }

    /**
     * Definition of return values of the get_kanban_content webservice function.
     *
     * @return external_single_structure
     */
    public static function add_column_returns(): external_single_structure {
        return
            new external_single_structure(
                [
                    'success' => new external_value(PARAM_BOOL, 'success'),
                    'update' => new external_value(PARAM_RAW, 'Encoded course update JSON')
                ]
            );
    }

    /**
     * This method adds a new column to the board.
     *
     * @param int $cmid the course module id of the kanban board
     * @param int $boardid the id of the kanban board
     * @param array $data array containing 'aftercol' the column to insert after and 'title'
     * @return bool Whether the request was successful
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function add_column(int $cmid, int $boardid, array $data): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::add_column_parameters(), [
            'cmid' => $cmid,
            'boardid' => $boardid,
            'data' => $data,
        ]);
        $cmid = $params['cmid'];
        $boardid = $params['boardid'];
        $title = empty($params['data']['title']) ? get_string('newcolumn', 'mod_kanban') : $params['data']['title'];
        $aftercol = $params['data']['aftercol'];
        list($course, $cminfo) = get_course_and_cm_from_cmid($cmid);
        $context = context_module::instance($cmid);
        self::validate_context($context);
        require_capability('mod/kanban:managecolumns', $context);
        $kanban = $DB->get_record('kanban', ['id' => $cminfo->instance]);
        $kanbanboard = $DB->get_record('kanban_board', ['kanban_instance' => $kanban->id, 'id' => $boardid], '*', MUST_EXIST);
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

        $aftercol = intval($aftercol);
        $options = '{}';
        $sequence = '';

        $kanbancolumnid = $DB->insert_record('kanban_column', [
            'title' => $title,
            'kanban_board' => $kanbanboard->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'options' => $options,
            'sequence' => $sequence,
        ]);

        $seq = helper::sequence_add_after($kanbanboard->sequence, $aftercol, $kanbancolumnid);

        $update = [];
        $update[] = [
            'name' => 'columns',
            'action' => 'create',
            'fields' => [
                'id' => $kanbancolumnid,
                'title' => $title,
                'options' => $options,
                'sequence' => $sequence,
            ]
        ];
        $update[] = [
            'name' => 'board',
            'action' => 'put',
            'fields' => [
                'id' => $kanbanboard->id,
                'sequence' => $seq,
            ]
        ];

        return [
            'success' => $DB->update_record(
                'kanban_board',
                ['id' => $kanbanboard->id, 'sequence' => $seq, 'timemodified' => time()]
            ),
            'update' => json_encode($update)
        ];
    }

    /**
     * Returns description of method parameters for the add_card function.
     *
     * @return external_function_parameters
     */
    public static function add_card_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'course module id', VALUE_REQUIRED),
            'boardid' => new external_value(PARAM_INT, 'board id', VALUE_REQUIRED),
            'data' => new external_single_structure([
                'title' => new external_value(
                    PARAM_TEXT,
                    'title of the new card',
                    VALUE_OPTIONAL,
                    get_string('newcard', 'mod_kanban')
                ),
                'columnid' => new external_value(PARAM_INT, 'column id', VALUE_REQUIRED),
                'aftercard' => new external_value(PARAM_INT, 'insert card after this id', VALUE_OPTIONAL, 0),
            ]),
        ]);
    }

    /**
     * Definition of return values of the get_kanban_content webservice function.
     *
     * @return external_single_structure
     */
    public static function add_card_returns(): external_single_structure {
        return
            new external_single_structure(
                [
                    'success' => new external_value(PARAM_BOOL, 'success'),
                    'update' => new external_value(PARAM_RAW, 'Encoded course update JSON')
                ]
            );
    }

    /**
     * This method adds a new card to the board.
     *
     * @param int $cmid the course module id of the kanban board
     * @param int $boardid the id of the kanban board
     * @param array $data array containing 'aftercard' the card to insert after, 'title' and the id of the column 'columnid'
     * @return array Whether the request was successful
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function add_card(int $cmid, int $boardid, array $data): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::add_card_parameters(), [
            'cmid' => $cmid,
            'boardid' => $boardid,
            'data' => $data,
        ]);
        $cmid = $params['cmid'];
        $boardid = $params['boardid'];
        $columnid = $params['data']['columnid'];
        $title = empty($params['data']['title']) ? get_string('newcard', 'mod_kanban') : $params['data']['title'];
        $aftercard = $params['data']['aftercard'];
        list($course, $cminfo) = get_course_and_cm_from_cmid($cmid);
        $context = context_module::instance($cmid);
        self::validate_context($context);
        require_capability('mod/kanban:addcard', $context);
        $kanban = $DB->get_record('kanban', ['id' => $cminfo->instance]);
        $kanbanboard = $DB->get_record('kanban_board', ['kanban_instance' => $kanban->id, 'id' => $boardid], '*', MUST_EXIST);
        $kanbancolumn = $DB->get_record('kanban_column', ['id' => $columnid, 'kanban_board' => $boardid], '*', MUST_EXIST);
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

        $options = '{}';

        $kanbancardid = $DB->insert_record('kanban_card', [
            'title' => $title,
            'kanban_board' => $kanbanboard->id,
            'kanban_column' => $kanbancolumn->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'options' => $options,
        ]);

        $seq = helper::sequence_add_after($kanbancolumn->sequence, $aftercard, $kanbancardid);

        $update = [];
        $update[] = [
            'name' => 'cards',
            'action' => 'create',
            'fields' => [
                'id' => $kanbancardid,
                'options' => $options,
                'title' => $title,
                'kanban_column' => $kanbancolumn->id,
                'assignees' => [],
            ]
        ];
        $update[] = [
            'name' => 'columns',
            'action' => 'put',
            'fields' => [
                'id' => $kanbancolumn->id,
                'sequence' => $seq,
            ]
        ];

        return [
            'success' => $DB->update_record(
                'kanban_column',
                ['id' => $kanbancolumn->id, 'sequence' => $seq, 'timemodified' => time()]
            ),
            'update' => json_encode($update)
        ];
    }

    /**
     * Returns description of method parameters for the move_column function.
     *
     * @return external_function_parameters
     */
    public static function move_column_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'course module id', VALUE_REQUIRED),
            'boardid' => new external_value(PARAM_INT, 'board id', VALUE_REQUIRED),
            'data' => new external_single_structure([
                'columnid' => new external_value(PARAM_INT, 'id of the moved column', VALUE_REQUIRED),
                'aftercol' => new external_value(PARAM_INT, 'move column after this id', VALUE_REQUIRED)
            ]),
        ]);
    }

    /**
     * Definition of return values of the move_column function.
     *
     * @return external_single_structure
     */
    public static function move_column_returns(): external_single_structure {
        return
            new external_single_structure(
                [
                    'success' => new external_value(PARAM_BOOL, 'success'),
                    'update' => new external_value(PARAM_RAW, 'Encoded course update JSON')
                ]
            );
    }

    /**
     * This method moves a column within the board.
     *
     * @param int $cmid the course module id of the kanban board
     * @param int $boardid the id of the kanban board
     * @param array $data array containing 'columnid' and 'aftercol' the column to move after
     * @return bool Whether the request was successful
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function move_column(int $cmid, int $boardid, array $data): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::move_column_parameters(), [
            'cmid' => $cmid,
            'boardid' => $boardid,
            'data' => $data,
        ]);
        $cmid = $params['cmid'];
        $boardid = $params['boardid'];
        $aftercol = $params['data']['aftercol'];
        $columnid = $params['data']['columnid'];
        list($course, $cminfo) = get_course_and_cm_from_cmid($cmid);
        $context = context_module::instance($cmid);
        self::validate_context($context);
        require_capability('mod/kanban:managecolumns', $context);
        $kanban = $DB->get_record('kanban', ['id' => $cminfo->instance]);
        $kanbanboard = $DB->get_record('kanban_board', ['kanban_instance' => $kanban->id, 'id' => $boardid], '*', MUST_EXIST);
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

        $seq = helper::sequence_move_after($kanbanboard->sequence, $aftercol, $columnid);

        $update = [];
        $update[] = [
            'name' => 'board',
            'action' => 'put',
            'fields' => [
                'id' => $kanbanboard->id,
                'sequence' => $seq,
            ]
        ];

        return [
            'success' => $DB->update_record(
                'kanban_board',
                ['id' => $kanbanboard->id, 'sequence' => $seq, 'timemodified' => time()]
            ),
            'update' => json_encode($update)
        ];
    }

    /**
     * Returns description of method parameters for the move_card function.
     *
     * @return external_function_parameters
     */
    public static function move_card_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'course module id', VALUE_REQUIRED),
            'boardid' => new external_value(PARAM_INT, 'board id', VALUE_REQUIRED),
            'data' => new external_single_structure([
                'cardid' => new external_value(PARAM_INT, 'id of the moved card', VALUE_REQUIRED),
                'columnid' => new external_value(PARAM_INT, 'id of the target column', VALUE_REQUIRED),
                'aftercard' => new external_value(PARAM_INT, 'move card after this card', VALUE_REQUIRED)
            ]),
        ]);
    }

    /**
     * Definition of return values of the move_card function.
     *
     * @return external_single_structure
     */
    public static function move_card_returns(): external_single_structure {
        return
            new external_single_structure(
                [
                    'success' => new external_value(PARAM_BOOL, 'success'),
                    'update' => new external_value(PARAM_RAW, 'Encoded course update JSON')
                ]
            );
    }

    /**
     * This method moves a card within the board.
     *
     * @param int $cmid the course module id of the kanban board
     * @param int $boardid the id of the kanban board
     * @param array $data array containing 'cardid', 'columnid' and 'aftercard' the column/card to move after
     * @return bool Whether the request was successful
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function move_card(int $cmid, int $boardid, array $data): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::move_card_parameters(), [
            'cmid' => $cmid,
            'boardid' => $boardid,
            'data' => $data,
        ]);
        $cmid = $params['cmid'];
        $boardid = $params['boardid'];
        $aftercard = $params['data']['aftercard'];
        $columnid = $params['data']['columnid'];
        $cardid = $params['data']['cardid'];
        list($course, $cminfo) = get_course_and_cm_from_cmid($cmid);
        $context = context_module::instance($cmid);
        self::validate_context($context);
        require_capability('mod/kanban:moveallcards', $context);
        // ToDo: Check moveassignedcards.
        $kanban = $DB->get_record('kanban', ['id' => $cminfo->instance]);
        $kanbanboard = $DB->get_record('kanban_board', ['kanban_instance' => $kanban->id, 'id' => $boardid], '*', MUST_EXIST);
        $kanbancard = $DB->get_record('kanban_card', ['kanban_board' => $boardid, 'id' => $cardid], '*', MUST_EXIST);
        $kanbancolumn = $DB->get_record(
            'kanban_column',
            ['kanban_board' => $boardid, 'id' => $kanbancard->kanban_column],
            '*',
            MUST_EXIST
        );

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

        if ($kanbancard->kanban_column == $columnid) {
            $seq = helper::sequence_move_after($kanbancolumn->sequence, $aftercard, $cardid);
            $update = [];
            $update[] = [
                'name' => 'columns',
                'action' => 'put',
                'fields' => [
                    'id' => $kanbancolumn->id,
                    'sequence' => $seq,
                ]
            ];
            return [
                'success' => $DB->update_record(
                    'kanban_column',
                    ['id' => $kanbancolumn->id, 'sequence' => $seq, 'timemodified' => time()]
                ),
                'update' => json_encode($update)
            ];
        } else {
            $seq = helper::sequence_remove($kanbancolumn->sequence, $cardid);
            $kanbancolumntarget = $DB->get_record(
                'kanban_column',
                ['kanban_board' => $boardid, 'id' => $columnid],
                '*',
                MUST_EXIST
            );
            $seqtarget = helper::sequence_add_after($kanbancolumntarget->sequence, $aftercard, $cardid);
            $update = [];
            $update[] = [
                'name' => 'columns',
                'action' => 'put',
                'fields' => [
                    'id' => $kanbancolumn->id,
                    'sequence' => $seq,
                ]
            ];
            $update[] = [
                'name' => 'columns',
                'action' => 'put',
                'fields' => [
                    'id' => $columnid,
                    'sequence' => $seqtarget,
                ]
            ];
            $update[] = [
                'name' => 'cards',
                'action' => 'put',
                'fields' => [
                    'id' => $cardid,
                    'kanban_column' => $columnid,
                ]
            ];
            return [
                'success' =>
                    $DB->update_record(
                        'kanban_column',
                        ['id' => $kanbancolumn->id, 'sequence' => $seq, 'timemodified' => time()]
                    ) &&
                    $DB->update_record('kanban_column', ['id' => $columnid, 'sequence' => $seqtarget, 'timemodified' => time()]) &&
                    $DB->update_record('kanban_card', ['id' => $cardid, 'kanban_column' => $columnid, 'timemodified' => time()]),
                'update' => json_encode($update)
            ];
        }
    }

    /**
     * Returns description of method parameters for the delete_card function.
     *
     * @return external_function_parameters
     */
    public static function delete_card_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'course module id', VALUE_REQUIRED),
            'boardid' => new external_value(PARAM_INT, 'board id', VALUE_REQUIRED),
            'data' => new external_single_structure([
                'cardid' => new external_value(PARAM_INT, 'id of the moved card', VALUE_REQUIRED)
            ]),
        ]);
    }

    /**
     * Definition of return values of the delete_card function.
     *
     * @return external_single_structure
     */
    public static function delete_card_returns(): external_single_structure {
        return
            new external_single_structure(
                [
                    'success' => new external_value(PARAM_BOOL, 'success'),
                    'update' => new external_value(PARAM_RAW, 'Encoded course update JSON')
                ]
            );
    }

    /**
     * This method deletes a card from the board.
     *
     * @param int $cmid the course module id of the kanban board
     * @param int $boardid the id of the kanban board
     * @param array $data array containing 'cardid'
     * @return bool Whether the request was successful
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function delete_card(int $cmid, int $boardid, array $data): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::delete_card_parameters(), [
            'cmid' => $cmid,
            'boardid' => $boardid,
            'data' => $data,
        ]);
        $cmid = $params['cmid'];
        $boardid = $params['boardid'];
        $cardid = $params['data']['cardid'];
        list($course, $cminfo) = get_course_and_cm_from_cmid($cmid);
        $context = context_module::instance($cmid);
        self::validate_context($context);
        require_capability('mod/kanban:managecards', $context);
        $kanban = $DB->get_record('kanban', ['id' => $cminfo->instance]);
        $kanbanboard = $DB->get_record('kanban_board', ['kanban_instance' => $kanban->id, 'id' => $boardid], '*', MUST_EXIST);
        $kanbancard = $DB->get_record('kanban_card', ['kanban_board' => $boardid, 'id' => $cardid], '*', MUST_EXIST);
        $kanbancolumn = $DB->get_record(
            'kanban_column',
            ['kanban_board' => $boardid, 'id' => $kanbancard->kanban_column],
            '*',
            MUST_EXIST
        );

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

        $seq = helper::sequence_remove($kanbancolumn->sequence, $cardid);

        $update = [];
        $update[] = [
            'name' => 'cards',
            'action' => 'delete',
            'fields' => [
                'id' => $cardid
            ]
        ];
        $update[] = [
            'name' => 'columns',
            'action' => 'put',
            'fields' => [
                'id' => $kanbancolumn->id,
                'sequence' => $seq,
            ]
        ];

        return [
            'success' =>
                $DB->update_record('kanban_column', ['id' => $kanbancolumn->id, 'sequence' => $seq, 'timemodified' => time()]) &&
                $DB->delete_records('kanban_card', ['id' => $cardid]),
            'update' => json_encode($update)
        ];
    }

    /**
     * Returns description of method parameters for the delete_column function.
     *
     * @return external_function_parameters
     */
    public static function delete_column_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'course module id', VALUE_REQUIRED),
            'boardid' => new external_value(PARAM_INT, 'board id', VALUE_REQUIRED),
            'data' => new external_single_structure([
                'columnid' => new external_value(PARAM_INT, 'id of the column', VALUE_REQUIRED)
            ]),
        ]);
    }

    /**
     * Definition of return values of the delete_column function.
     *
     * @return external_single_structure
     */
    public static function delete_column_returns(): external_single_structure {
        return
            new external_single_structure(
                [
                    'success' => new external_value(PARAM_BOOL, 'success'),
                    'update' => new external_value(PARAM_RAW, 'Encoded course update JSON')
                ]
            );
    }

    /**
     * This method deletes a column from the board.
     *
     * @param int $cmid the course module id of the kanban board
     * @param int $boardid the id of the kanban board
     * @param array $data array containing 'columnid'
     * @return bool Whether the request was successful
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function delete_column(int $cmid, int $boardid, array $data): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::delete_column_parameters(), [
            'cmid' => $cmid,
            'boardid' => $boardid,
            'data' => $data,
        ]);
        $cmid = $params['cmid'];
        $boardid = $params['boardid'];
        $columnid = $params['data']['columnid'];
        list($course, $cminfo) = get_course_and_cm_from_cmid($cmid);
        $context = context_module::instance($cmid);
        self::validate_context($context);
        require_capability('mod/kanban:managecolumns', $context);
        $kanban = $DB->get_record('kanban', ['id' => $cminfo->instance]);
        $kanbanboard = $DB->get_record('kanban_board', ['kanban_instance' => $kanban->id, 'id' => $boardid], '*', MUST_EXIST);
        $kanbancolumn = $DB->get_record('kanban_column', ['kanban_board' => $boardid, 'id' => $columnid], '*', MUST_EXIST);

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

        $seq = helper::sequence_remove($kanbanboard->sequence, $columnid);

        $update = [];
        foreach ($kanbancolumn->sequence as $kanbancardid) {
            $update[] = [
                'name' => 'cards',
                'action' => 'delete',
                'fields' => [
                    'id' => $kanbancardid
                ]
            ];
        }
        $update[] = [
            'name' => 'columns',
            'action' => 'delete',
            'fields' => [
                'id' => $kanbancolumn->id
            ]
        ];
        $update[] = [
            'name' => 'board',
            'action' => 'put',
            'fields' => [
                'id' => $kanbanboard->id,
                'sequence' => $seq,
            ]
        ];

        return [
            'success' =>
                $DB->update_record('kanban_board', ['id' => $boardid, 'sequence' => $seq, 'timemodified' => time()]) &&
                $DB->delete_records('kanban_column', ['id' => $columnid]) &&
                $DB->delete_records('kanban_card', ['kanban_column' => $columnid]),
            'update' => json_encode($update)
        ];
    }

    /**
     * Returns description of method parameters for the assign_user function.
     *
     * @return external_function_parameters
     */
    public static function assign_user_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'course module id', VALUE_REQUIRED),
            'boardid' => new external_value(PARAM_INT, 'board id', VALUE_REQUIRED),
            'data' => new external_single_structure([
                'cardid' => new external_value(PARAM_INT, 'id of the column', VALUE_REQUIRED),
                'userid' => new external_value(PARAM_INT, 'user id', VALUE_OPTIONAL)
            ]),
        ]);
    }

    /**
     * Definition of return values of the assign_user function.
     *
     * @return external_single_structure
     */
    public static function assign_user_returns(): external_single_structure {
        return
            new external_single_structure(
                [
                    'success' => new external_value(PARAM_BOOL, 'success'),
                    'update' => new external_value(PARAM_RAW, 'Encoded course update JSON')
                ]
            );
    }

    /**
     * This method assigns a user to a card.
     *
     * @param int $cmid the course module id of the kanban board
     * @param int $boardid the id of the kanban board
     * @param array $data array containing 'cardid' and 'userid'
     * @return bool Whether the request was successful
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function assign_user(int $cmid, int $boardid, array $data): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::assign_user_parameters(), [
            'cmid' => $cmid,
            'boardid' => $boardid,
            'data' => $data,
        ]);
        $cmid = $params['cmid'];
        $boardid = $params['boardid'];
        $cardid = $params['data']['cardid'];
        $userid = $params['data']['userid'];
        list($course, $cminfo) = get_course_and_cm_from_cmid($cmid);
        $context = context_module::instance($cmid);
        self::validate_context($context);
        if (empty($userid)) {
            require_capability('mod/kanban:assignself', $context);
            $userid = $USER->id;
        } else {
            require_capability('mod/kanban:assignothers', $context);
        }

        $kanban = $DB->get_record('kanban', ['id' => $cminfo->instance]);
        $kanbanboard = $DB->get_record('kanban_board', ['kanban_instance' => $kanban->id, 'id' => $boardid], '*', MUST_EXIST);

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

        $success1 = $DB->insert_record('kanban_assignee', ['kanban_card' => $cardid, 'user' => $userid]);
        $success2 = $DB->update_record('kanban_card', ['id' => $cardid, 'timemodified' => time()]);
        $userids = $DB->get_fieldset_select('kanban_assignee', 'user', 'kanban_card = :cardid', ['cardid' => $cardid]);
        $update = [];
        $update[] = [
            'name' => 'cards',
            'action' => 'put',
            'fields' => [
                'id' => $cardid,
                'assignees' => $userids
            ]
        ];

        return [
            'success' => $success1 && $success2,
            'update' => json_encode($update)
        ];
    }


    /**
     * Returns description of method parameters for the unassign_user function.
     *
     * @return external_function_parameters
     */
    public static function unassign_user_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'course module id', VALUE_REQUIRED),
            'boardid' => new external_value(PARAM_INT, 'board id', VALUE_REQUIRED),
            'data' => new external_single_structure([
                'cardid' => new external_value(PARAM_INT, 'id of the column', VALUE_REQUIRED),
                'userid' => new external_value(PARAM_INT, 'user id', VALUE_REQUIRED)
            ]),
        ]);
    }

    /**
     * Definition of return values of the assign_user function.
     *
     * @return external_single_structure
     */
    public static function unassign_user_returns(): external_single_structure {
        return
            new external_single_structure(
                [
                    'success' => new external_value(PARAM_BOOL, 'success'),
                    'update' => new external_value(PARAM_RAW, 'Encoded course update JSON')
                ]
            );
    }

    /**
     * This method unassigns a user from a card.
     *
     * @param int $cmid the course module id of the kanban board
     * @param int $boardid the id of the kanban board
     * @param array $data array containing 'cardid' and 'userid'
     * @return bool Whether the request was successful
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function unassign_user(int $cmid, int $boardid, array $data): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::unassign_user_parameters(), [
            'cmid' => $cmid,
            'boardid' => $boardid,
            'data' => $data,
        ]);
        $cmid = $params['cmid'];
        $boardid = $params['boardid'];
        $cardid = $params['data']['cardid'];
        $userid = $params['data']['userid'];
        list($course, $cminfo) = get_course_and_cm_from_cmid($cmid);
        $context = context_module::instance($cmid);
        self::validate_context($context);
        if ($userid == $USER->id) {
            require_capability('mod/kanban:assignself', $context);
        } else {
            require_capability('mod/kanban:assignothers', $context);
        }

        $kanban = $DB->get_record('kanban', ['id' => $cminfo->instance]);
        $kanbanboard = $DB->get_record('kanban_board', ['kanban_instance' => $kanban->id, 'id' => $boardid], '*', MUST_EXIST);

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
        $success = $DB->delete_records('kanban_assignee', ['kanban_card' => $cardid, 'user' => $userid]) &&
            $DB->update_record('kanban_card', ['id' => $cardid, 'timemodified' => time()]);
        $userids = $DB->get_fieldset_select('kanban_assignee', 'user', 'kanban_card = :cardid', ['cardid' => $cardid]);

        $update = [];
        $update[] = [
            'name' => 'cards',
            'action' => 'put',
            'fields' => [
                'id' => $cardid,
                'assignees' => $userids,
            ]
        ];

        return [
            'success' => $success,
            'update' => json_encode($update)
        ];
    }
}
