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
 * Helper class
 *
 * @package    mod_kanban
 * @copyright  2023 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_kanban;

/**
 * Helper class
 *
 * @package    mod_kanban
 * @copyright  2023 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /**
     * Adds an item to a string sequence of integer values, divided by commas.
     * @param string $sequence The original sequence
     * @param int $afteritem The item to add after
     * @param int $newitem The item to add
     * @return string The new sequence
     */
    public static function sequence_add_after (string $sequence, int $afteritem, int $newitem): string {
        if (empty($sequence)) {
            $seq = [];
        } else {
            $seq = explode(',', $sequence);
        }

        if ($afteritem == 0) {
            $seq = array_merge([$newitem], $seq);
        } else if (!in_array($afteritem, $seq)) {
            $seq[] = $newitem;
        } else {
            $pos = array_search($afteritem, $seq);
            $seq = array_merge(array_slice($seq, 0, $pos + 1), [$newitem], array_slice($seq, $pos + 1));
        }
        return join(',', $seq);
    }
    /**
     * Removes an item from a string sequence of integer values, divided by commas.
     * @param string $sequence The original sequence
     * @param int $item The item to remove
     * @return string The new sequence
     */
    public static function sequence_remove (string $sequence, int $item): string {
        if (empty($sequence)) {
            return '';
        }
        $seq = explode(',', $sequence);

        $posold = array_search($item, $seq);
        if ($posold >= 0) {
            unset($seq[$posold]);
        }

        return join(',', $seq);
    }
    /**
     * Moves an item inside a string sequence of integer values, divided by commas.
     * @param string $sequence The original sequence
     * @param int $afteritem The item to move after
     * @param int $item The item to move
     * @return string The new sequence
     */
    public static function sequence_move_after (string $sequence, int $afteritem, int $item): string {
        $seq = self::sequence_remove($sequence, $item);
        return self::sequence_add_after($seq, $afteritem, $item);
    }
    /**
     * Removes items in a string sequence of integer values, divided by commas.
     * @param string $sequence The original sequence
     * @param array $replace An array of $key => $value replacing rules ($key is replaced by $value)
     * @return string The new sequence
     */
    public static function sequence_replace (string $sequence, array $replace) {
        if (empty($sequence)) {
            return '';
        }
        $seq = explode(',', $sequence);

        $newseq = [];

        foreach ($seq as $value) {
            $newseq[] = $replace[$value];
        }

        return $newseq;
    }

    /**
     * This function checks permissions if a board is a user or a group board.
     *
     * @param object $kanbanboard The record from the board table
     * @param \context $context The context of the course module
     * @param \cm_info $cminfo The course module info
     */
    public static function check_permissions_for_user_or_group(object $kanbanboard, \context $context, \cm_info $cminfo): void {
        global $USER;
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
    }

    /**
     * Creates a new board in the database. The board can be assigned to a certain user, group or can be marked as a template.
     *
     * @param int $instance id of the kanban instance
     * @param int $user userid, if the board should be user specific (default 0 means no user specific board)
     * @param int $group groupid, if the board should be group specific (default 0 means no group specific board)
     * @param bool $template whether to create a new template board for this kanban instance (defaults to false)
     * @return int the id of the new board
     */
    public static function create_new_board(int $instance, int $user = 0, int $group = 0, bool $template = false): int {
        global $DB;
        $kanban = $DB->get_record('kanban', ['id' => $instance]);
        // Is there a template for this instance?
        $template = $DB->get_record('kanban_board', [
            'kanban_instance' => $instance,
            'user' => 0,
            'groupid' => 0,
            'template' => 1
        ]);
        if ($template) {
            // To be implemented later.
        } else {
            // This could be moved to a side wide template.
            $boardid = $DB->insert_record('kanban_board', [
                'sequence' => '',
                'user' => 0,
                'groupid' => 0,
                'template' => 0,
                'timecreated' => time(),
                'timemodified' => time(),
                'kanban_instance' => $instance
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
        }
        return $boardid;
    }
}
