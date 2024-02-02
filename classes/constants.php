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
 * Constant class
 *
 * @package    mod_kanban
 * @copyright   2023-2024 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kanban;

/**
 * Constant class
 *
 * @package    mod_kanban
 * @copyright   2023-2024 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class constants {
    /**
     * Type constant for editing permissions.
     */
    public const MOD_KANBAN_EDIT = 1;
    /**
     * Type constant for viewing permissions.
     */
    public const MOD_KANBAN_VIEW = 2;
    /**
     * Mapping of the type constants to capabilities.
     */
    public const MOD_KANBAN_CAPABILITY = [
        self::MOD_KANBAN_EDIT => 'mod/kanban:editallboards',
        self::MOD_KANBAN_VIEW => 'mod/kanban:viewallboards',
    ];
    /**
     * Setting: User boards disabled
     */
    public const MOD_KANBAN_NOUSERBOARDS = 0;
    /**
     * Setting: User boards and course board
     */
    public const MOD_KANBAN_USERBOARDS_ENABLED = 1;
    /**
     * Setting: User boards only
     */
    public const MOD_KANBAN_USERBOARDS_ONLY = 2;
    /**
     * Item type board
     */
    public const MOD_KANBAN_BOARD = 0;
    /**
     * Item type column
     */
    public const MOD_KANBAN_COLUMN = 1;
    /**
     * Item type card
     */
    public const MOD_KANBAN_CARD = 2;
    /**
     * Item type discussion
     */
    public const MOD_KANBAN_DISCUSSION = 3;
    /**
     * Item type history
     */
    public const MOD_KANBAN_HISTORY = 4;
    /**
     * Mapping of item types to strings
     */
    public const MOD_KANBAN_TYPES = [
        self::MOD_KANBAN_BOARD => 'board',
        self::MOD_KANBAN_COLUMN => 'column',
        self::MOD_KANBAN_CARD => 'card',
        self::MOD_KANBAN_DISCUSSION => 'discussion',
        self::MOD_KANBAN_HISTORY => 'history',
    ];
}
