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
 * Reminder task
 *
 * @package    mod_kanban
 * @copyright  2023 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_kanban\task;

use mod_kanban\helper;

/**
 * Reminder task
 *
 * @package    mod_kanban
 * @copyright  2023 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reminder extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('remindertask', 'mod_kanban');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;
        $kanbancards = $DB->get_records_sql(
            'SELECT ' . $DB->sql_concat('c.id', '"-"', 'a.user') . ' as uniqid,
                    c.id as id, c.title as title, k.name as boardname, c.duedate as duedate, a.user as user, k.id as instance
             FROM {kanban_card} c
             JOIN {kanban_assignee} a ON a.kanban_card = c.id
             JOIN {kanban_board} b ON b.id = c.kanban_board
             JOIN {kanban} k ON b.kanban_instance = k.id
             WHERE c.duedate != 0
                AND c.reminder_sent = 0
                AND c.completed = 0
                AND (c.duedate < now() OR (c.reminderdate !=0 AND c.reminderdate < now()))'
        );
        foreach ($kanbancards as $kanbancard) {
            list($course, $cminfo) = get_course_and_cm_from_instance($kanbancard->instance, 'kanban');
            $user = \core_user::get_user($kanbancard->user);
            fix_current_language($user->lang);
            $kanbancard->duedate = userdate($kanbancard->duedate, get_string('strftimedate', 'langconfig'));
            helper::send_notification($cminfo, 'due', [$kanbancard->user], $kanbancard, null, true);
            $data = new \stdClass;
            $data->id = $kanbancard->id;
            $data->reminder_sent = 1;
            $DB->update_record('kanban_card', $data);
        }
    }
}
