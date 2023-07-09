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
 * mod_kanban service definition.
 *
 * @package    mod_kanban
 * @copyright  2023 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    'mod_kanban_get_kanban_content_init' => [
        'classname'   => 'mod_kanban\external\get_kanban_content',
        'methodname'  => 'get_kanban_content_init',
        'description' => 'Retrieves the content of the kanban board',
        'type'        => 'read',
        'ajax' => true,
        'capabilities' => 'mod/kanban:view',
    ],
    'mod_kanban_get_kanban_content_update' => [
        'classname'   => 'mod_kanban\external\get_kanban_content',
        'methodname'  => 'get_kanban_content_update',
        'description' => 'Retrieves the content of the kanban board',
        'type'        => 'read',
        'ajax' => true,
        'capabilities' => 'mod/kanban:view',
    ],
    'mod_kanban_get_discussion_update' => [
        'classname'   => 'mod_kanban\external\get_kanban_content',
        'methodname'  => 'get_discussion_update',
        'description' => 'Retrieves the discussion for a card',
        'type'        => 'read',
        'ajax' => true,
        'capabilities' => 'mod/kanban:view',
    ],
    'mod_kanban_change_kanban_content_add_column' => [
        'classname'   => 'mod_kanban\external\change_kanban_content',
        'methodname'  => 'add_column',
        'description' => 'Adds a column to the kanban board',
        'type'        => 'write',
        'ajax' => true,
        'capabilities' => 'mod/kanban:addcolumn',
    ],
    'mod_kanban_change_kanban_content_add_card' => [
        'classname'   => 'mod_kanban\external\change_kanban_content',
        'methodname'  => 'add_card',
        'description' => 'Adds a card to a column of the kanban board',
        'type'        => 'write',
        'ajax' => true,
        'capabilities' => 'mod/kanban:addcard',
    ],
    'mod_kanban_change_kanban_content_move_column' => [
        'classname'   => 'mod_kanban\external\change_kanban_content',
        'methodname'  => 'move_column',
        'description' => 'Moves a column within the kanban board',
        'type'        => 'write',
        'ajax' => true,
        'capabilities' => 'mod/kanban:managecolumns',
    ],
    'mod_kanban_change_kanban_content_move_card' => [
        'classname'   => 'mod_kanban\external\change_kanban_content',
        'methodname'  => 'move_card',
        'description' => 'Moves a card within the kanban board',
        'type'        => 'write',
        'ajax' => true,
        'capabilities' => 'mod/kanban:managecards',
    ],
    'mod_kanban_change_kanban_content_delete_column' => [
        'classname'   => 'mod_kanban\external\change_kanban_content',
        'methodname'  => 'delete_column',
        'description' => 'Deletes a column and all contained cards from the kanban board',
        'type'        => 'write',
        'ajax' => true,
        'capabilities' => 'mod/kanban:managecolumns',
    ],
    'mod_kanban_change_kanban_content_delete_card' => [
        'classname'   => 'mod_kanban\external\change_kanban_content',
        'methodname'  => 'delete_card',
        'description' => 'Deletes a card from the kanban board',
        'type'        => 'write',
        'ajax' => true,
        'capabilities' => 'mod/kanban:managecards',
    ],
    'mod_kanban_change_kanban_content_assign_user' => [
        'classname'   => 'mod_kanban\external\change_kanban_content',
        'methodname'  => 'assign_user',
        'description' => 'Assigns a user to a card',
        'type'        => 'write',
        'ajax' => true,
        'capabilities' => 'mod/kanban:assignself',
    ],
    'mod_kanban_change_kanban_content_unassign_user' => [
        'classname'   => 'mod_kanban\external\change_kanban_content',
        'methodname'  => 'unassign_user',
        'description' => 'Unassigns a user to a card',
        'type'        => 'write',
        'ajax' => true,
        'capabilities' => 'mod/kanban:assignself',
    ],
    'mod_kanban_change_kanban_content_set_column_locked' => [
        'classname'   => 'mod_kanban\external\change_kanban_content',
        'methodname'  => 'set_column_locked',
        'description' => 'Changes the lock state of a column',
        'type'        => 'write',
        'ajax' => true,
        'capabilities' => 'mod/kanban:managecolumns',
    ],
    'mod_kanban_change_kanban_content_set_card_complete' => [
        'classname'   => 'mod_kanban\external\change_kanban_content',
        'methodname'  => 'set_card_complete',
        'description' => 'Changes the completion state of a card',
        'type'        => 'write',
        'ajax' => true,
        'capabilities' => 'mod/kanban:managecards',
    ],
    'mod_kanban_change_kanban_content_set_board_columns_locked' => [
        'classname'   => 'mod_kanban\external\change_kanban_content',
        'methodname'  => 'set_board_columns_locked',
        'description' => 'Changes the lock state of a whole board',
        'type'        => 'write',
        'ajax' => true,
        'capabilities' => 'mod/kanban:manageboard',
    ],
    'mod_kanban_change_kanban_content_add_discussion_message' => [
        'classname'   => 'mod_kanban\external\change_kanban_content',
        'methodname'  => 'add_discussion_message',
        'description' => 'Adds a message to card discussion',
        'type'        => 'write',
        'ajax' => true,
        'capabilities' => 'mod/kanban:managecards',
    ],
    'mod_kanban_change_kanban_content_delete_discussion_message' => [
        'classname'   => 'mod_kanban\external\change_kanban_content',
        'methodname'  => 'delete_discussion_message',
        'description' => 'Deletes a message from card discussion',
        'type'        => 'write',
        'ajax' => true,
        'capabilities' => 'mod/kanban:managecards',
    ],
];
