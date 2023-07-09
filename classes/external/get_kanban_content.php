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

use block_recentlyaccesseditems\external;
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
use mod_kanban\updateformatter;
use mod_kanban\helper;
use stdClass;

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
    public static function get_kanban_content_init_parameters(): external_function_parameters {
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
    public static function get_kanban_content_init_returns(): external_single_structure {
        return
            new external_single_structure(
                [
                    'common' => new external_single_structure([
                        'id' => new external_value(PARAM_INT, 'cmid'),
                        'timestamp' => new external_value(PARAM_INT, 'timestamp'),
                        'userid' => new external_value(PARAM_INT, 'current user id'),
                        'lang' => new external_value(PARAM_TEXT, 'language for the ui'),
                    ]),
                    'board' => new external_single_structure([
                        'id' => new external_value(PARAM_INT, 'board id'),
                        'sequence' => new external_value(PARAM_TEXT, 'order of the columns in the board'),
                        'timemodified' => new external_value(PARAM_INT, 'timemodified'),
                        'locked' => new external_value(PARAM_INT, 'lock state'),
                        'user' => new external_value(PARAM_INT, 'userboard for userid', VALUE_OPTIONAL, 0),
                        'groupid' => new external_value(PARAM_INT, 'groupboard for groupid', VALUE_OPTIONAL, 0),
                    ]),
                    'columns' => new external_multiple_structure(
                        new external_single_structure(
                            [
                                'id' => new external_value(PARAM_INT, 'column id'),
                                'title' => new external_value(PARAM_TEXT, 'column title'),
                                'sequence' => new external_value(PARAM_TEXT, 'order of the cards in the column'),
                                'locked' => new external_value(PARAM_BOOL, 'lock state of the column'),
                                'options' => new external_value(PARAM_TEXT, 'options for the column'),
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
                                'duedate' => new external_value(PARAM_INT, 'due date'),
                                'assignees' => new external_multiple_structure(
                                        new external_value(PARAM_INT, 'user id'),
                                        VALUE_OPTIONAL
                                ),
                                'selfassigned' => new external_value(
                                    PARAM_BOOL,
                                    'is current user assigned to the card?',
                                    VALUE_OPTIONAL,
                                    false
                                ),
                                'completed' => new external_value(
                                    PARAM_BOOL,
                                    'is card completed?',
                                    VALUE_OPTIONAL,
                                    false
                                ),
                                'hasdescription' => new external_value(
                                    PARAM_BOOL,
                                    'has a description?',
                                    VALUE_OPTIONAL,
                                    false
                                ),
                                'description' => new external_value(
                                    PARAM_RAW,
                                    'description',
                                    VALUE_OPTIONAL,
                                    ''
                                ),
                                'hasattachment' => new external_value(
                                    PARAM_BOOL,
                                    'has an attachment?',
                                    VALUE_OPTIONAL,
                                    false
                                ),
                                'attachments' => new external_multiple_structure(
                                    new external_single_structure([
                                        'url' => new external_value(PARAM_URL, 'attachment url', VALUE_REQUIRED),
                                        'name' => new external_value(PARAM_TEXT, 'filename', VALUE_REQUIRED)
                                    ]),
                                    'attachments',
                                    VALUE_OPTIONAL,
                                    []
                                ),
                                'discussion' => new external_value(
                                    PARAM_BOOL,
                                    'has a discussion?',
                                    VALUE_OPTIONAL,
                                    false
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
                    'discussions' => new external_multiple_structure(
                        new external_single_structure(
                            [
                                'id' => new external_value(PARAM_INT, 'id'),
                                'timecreated' => new external_value(PARAM_INT, 'timecreated'),
                                'user' => new external_value(PARAM_INT, 'userid'),
                                'kanban_card' => new external_value(PARAM_INT, 'card id'),
                                'content' => new external_value(PARAM_TEXT, 'discussion message'),
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
    public static function get_kanban_content_init(int $cmid, int $boardid, int $timestamp = 0): array {
        $params = self::validate_parameters(self::get_kanban_content_init_parameters(), [
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
    public static function get_kanban_content_update(int $cmid, int $boardid, int $timestamp = 0): array {
        $params = self::validate_parameters(self::get_kanban_content_init_parameters(), [
            'cmid' => $cmid,
            'boardid' => $boardid,
            'timestamp' => $timestamp
        ]);
        $cmid = $params['cmid'];
        $boardid = $params['boardid'];
        $timestamp = $params['timestamp'];
        return self::execute($cmid, $boardid, $timestamp, true);
    }

    /**
     * Same as for get_kanban_content_init().
     * @return external_function_parameters
     */
    public static function get_kanban_content_update_parameters(): external_function_parameters {
        return self::get_kanban_content_init_parameters();
    }

    /**
     * Definition of return values of the get_kanban_content_update webservice function.
     *
     * @return external_single_structure
     */
    public static function get_kanban_content_update_returns(): external_single_structure {
        return new external_single_structure(
            [
                'update' => new external_value(PARAM_RAW, 'update JSON'),
            ]
        );
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
        $fs = get_file_storage();
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
            'manageboard' => has_capability('mod/kanban:manageboard', $context)
        ];

        $common = $DB->get_record('kanban', ['id' => $cminfo->instance]);

        $params['board'] = $boardid;
        $params['timestamp'] = $timestamp;

        $kanbanboard = $DB->get_record('kanban_board', ['id' => $boardid]);
        if (!(empty($kanbanboard->user) && empty($kanbanboard->groupid))) {
            $restrictcaps = false;
            if (!empty($kanbanboard->user) && $kanbanboard->user != $USER->id) {
                require_capability('mod/kanban:viewallboards', $context);
                $restrictcaps = true;
            }
            if (!empty($kanbanboard->groupid)) {
                $members = groups_get_members($kanbanboard->groupid, 'u.id');
                $members = array_map(function ($v) {
                    return intval($v->id);
                }, $members);
                $ismember = in_array($USER->id, $members);
                if ($cminfo->groupmode == SEPARATEGROUPS && !$ismember) {
                    require_capability('mod/kanban:viewallboards', $context);
                    $restrictcaps = true;
                }
                if ($cminfo->groupmode == VISIBLEGROUPS && !$ismember) {
                    $restrictcaps = true;
                }
            }
            if ($restrictcaps) {
                $editcap = has_capability('mod/kanban:editallboards', $context);
                foreach ($capabilities as $cap => $value) {
                    $capabilities[$cap] &= $editcap;
                }
            }
        }

        $common = new stdClass;
        $common->timestamp = time();
        $common->id = $cmid;
        $common->userid = $USER->id;
        $common->lang = current_language();

        $kanbancards = [];
        $kanbanassignees = [];
        $kanbanusers = [];

        $sql = 'kanban_board = :board AND timemodified > :timestamp';

        $kanbancolumns = $DB->get_records_select('kanban_column', $sql, $params);
        $kanbancards = $DB->get_records_select('kanban_card', $sql, $params);
        $kanbancardids = array_map(function ($v) {
            return $v->id;
        }, $kanbancards);
        if (!empty($kanbancardids)) {
            $users = get_enrolled_users($context, '');
            foreach ($users as $user) {
                $kanbanusers[$user->id] = [
                    'id' => $user->id,
                    'fullname' => fullname($user),
                    'userpicture' => $OUTPUT->user_picture($user, ['link' => false]),
                ];
            }
            [$sql, $params] = $DB->get_in_or_equal($kanbancardids);
            $sql = 'kanban_card ' . $sql;
            $kanbanassigneesraw = $DB->get_records_select('kanban_assignee', $sql, $params);
            $kanbanassignees = [];
            $kanbanuserids = [];
            foreach ($kanbanassigneesraw as $assignee) {
                if (!empty($kanbanusers[$assignee->user])) {
                    $kanbanassignees[$assignee->kanban_card][] = $assignee->user;
                    $kanbanuserids[] = $assignee->user;
                }
            }
            foreach ($kanbancards as $key => $card) {
                if (empty($kanbanassignees[$card->id])) {
                    $kanbanassignees[$card->id] = [];
                }
                $kanbancards[$key]->assignees = $kanbanassignees[$card->id];
                $kanbancards[$key]->selfassigned = in_array($USER->id, $kanbancards[$key]->assignees);
                $kanbancards[$key]->hasdescription = !empty($kanbancards[$key]->description);
                $kanbancards[$key]->discussions = [];
                $kanbancards[$key]->description = file_rewrite_pluginfile_urls(
                    $kanbancards[$key]->description,
                    'pluginfile.php',
                    $context->id,
                    'mod_kanban',
                    'attachments',
                    $card->id
                );
                $kanbancards[$key]->attachments = helper::get_attachments($context->id, $card->id);
                $kanbancards[$key]->hasattachment = count($kanbancards[$key]->attachments) > 0;
            }
        }

        $caps = [];

        foreach ($capabilities as $k => $v) {
            $caps[] = ['id' => $k, 'value' => $v];
        }

        if ($asupdate) {
            $formatter = new updateformatter();
            $formatter->put('common', (array)$common);
            if (intval($kanbanboard->timemodified) > $timestamp) {
                $formatter->put('board', (array)$kanbanboard);
            }
            foreach ($kanbancolumns as $column) {
                $formatter->put('columns', (array)$column);
            }
            foreach ($kanbancards as $card) {
                $formatter->put('cards', (array)$card);
            }
            foreach ($kanbanuserids as $userid) {
                $formatter->put('users', (array)$kanbanusers[$userid]);
            }
            return [
                'update' => $formatter->format()
            ];
        }

        return [
            'common' => $common,
            'board' => $kanbanboard,
            'columns' => $kanbancolumns,
            'cards' => $kanbancards,
            'users' => $kanbanusers,
            'capabilities' => $caps,
            'discussions' => [],
        ];
    }

    /**
     * Parameters for get_kanban_discussion_update().
     *
     * @return external_function_parameters
     */
    public static function get_discussion_update_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'course module id', VALUE_REQUIRED),
            'boardid' => new external_value(PARAM_INT, 'board id', VALUE_REQUIRED),
            'cardid' => new external_value(PARAM_INT, 'card id', VALUE_REQUIRED),
            'timestamp' => new external_value(PARAM_INT, 'only get values modified after this timestamp', VALUE_OPTIONAL, 0),
        ]);
    }

    /**
     * Definition of return values of the get_kanban_content_update webservice function.
     *
     * @return external_single_structure
     */
    public static function get_discussion_update_returns(): external_single_structure {
        return new external_single_structure(
            [
                'update' => new external_value(PARAM_RAW, 'update JSON'),
            ]
        );
    }

    /**
     * Get card discussion from database.
     *
     * @param int $cmid the course module id of the kanban board
     * @param int $boardid the id of the kanban board
     * @param int $cardid the id of the card
     * @param int $timestamp the timestamp of the discussion present in the frontend
     * @return array The requested content
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function get_discussion_update(int $cmid, int $boardid, int $cardid, int $timestamp = 0): array {
        global $DB, $USER;
        list($course, $cminfo) = get_course_and_cm_from_cmid($cmid);
        $context = context_module::instance($cmid);
        self::validate_context($context);
        require_capability('mod/kanban:view', $context);

        $kanban = $DB->get_record('kanban', ['id' => $cminfo->instance]);
        $kanbanboard = $DB->get_record('kanban_board', ['kanban_instance' => $kanban->id, 'id' => $boardid], '*', MUST_EXIST);

        helper::check_permissions_for_user_or_group($kanbanboard, $context, $cminfo, helper::MOD_KANBAN_VIEW);

        $sql = 'kanban_card = :cardid AND timecreated > :timestamp';
        $params['cardid'] = $cardid;
        $params['timestamp'] = $timestamp;

        $discussions = $DB->get_records_select('kanban_discussion', $sql, $params);

        $formatter = new updateformatter();
        foreach ($discussions as $discussion) {
            $discussion->candelete = $discussion->user == $USER->id || has_capability('mod/kanban:manageboard', $context);
            $formatter->discussionput("discussions[$cardid]", (array)$discussion);
        }
        return [
            'update' => $formatter->format()
        ];
    }

}
