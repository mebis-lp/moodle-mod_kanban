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
 * Backup steps for mod_kanban
 *
 * @package     mod_kanban
 * @copyright   2023, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_kanban_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines the XML structure for kanban backups
     *
     * @return backup_nested_element
     */
    protected function define_structure(): backup_nested_element {
        $userinfo = $this->get_setting_value('userinfo');

        $kanban = new backup_nested_element(
            'kanban',
            ['id'],
            ['course', 'name', 'intro', 'introformat', 'userboards', 'history', 'completioncreate', 'completioncomplete']
        );
        $kanban->set_source_table('kanban', ['id' => backup::VAR_ACTIVITYID]);
        $kanban->annotate_files('mod_kanban', 'intro', null);

        $tags = new backup_nested_element('cardtags');
        $tag = new backup_nested_element('tag', ['id'], ['itemid', 'rawname']);

        $boards = new backup_nested_element('boards');
        $board = new backup_nested_element(
            'kanban_board',
            ['id'],
            ['sequence', 'timecreated', 'timemodified', 'userid', 'groupid', 'template', 'kanban_instance', 'options', 'locked']
        );

        $columns = new backup_nested_element('columns');
        $column = new backup_nested_element(
            'kanban_column',
            ['id'],
            ['title', 'sequence', 'timecreated', 'timemodified', 'kanban_board', 'options', 'locked']
        );

        $cards = new backup_nested_element('cards');
        $card = new backup_nested_element(
            'kanban_card',
            ['id'],
            [
                'title',
                'timecreated',
                'timemodified',
                'kanban_board',
                'kanban_column',
                'options',
                'duedate',
                'reminderdate',
                'completed',
                'description',
                'descriptionformat',
                'linkedactivity',
                'originalid',
                'discussion',
                'reminder_sent',
                'createdby'
            ]
        );
        $card->annotate_files('mod_kanban', 'attachments', 'id');
        $card->annotate_ids('kanban_card_id', 'originalid');

        $assignees = new backup_nested_element('assignees');
        $assignee = new backup_nested_element(
            'kanban_assignee',
            ['id'],
            ['kanban_card', 'userid']
        );

        $discussions = new backup_nested_element('discussions');
        $discussion = new backup_nested_element(
            'kanban_discussion_comment',
            ['id'],
            ['kanban_card', 'userid', 'timecreated', 'content']
        );

        $historyitems = new backup_nested_element('historyitems');
        $historyitem = new backup_nested_element(
            'kanban_history',
            ['id'],
            [
                'userid',
                'kanban_board',
                'kanban_column',
                'kanban_card',
                'action',
                'parameters',
                'timestamp',
                'affected_userid',
                'type'
            ]
        );

        $kanban->add_child($boards);
        $kanban->add_child($tags);
        $tags->add_child($tag);
        $boards->add_child($board);
        $board->add_child($columns);
        $columns->add_child($column);
        $column->add_child($cards);
        $cards->add_child($card);
        $card->add_child($assignees);
        $assignees->add_child($assignee);
        $card->add_child($discussions);
        $discussions->add_child($discussion);
        $board->add_child($historyitems);
        $historyitems->add_child($historyitem);

        if ($userinfo) {
            $board->set_source_table('kanban_board', ['kanban_instance' => backup::VAR_PARENTID]);
            $board->annotate_ids('userid', 'userid');
            $board->annotate_ids('groupid', 'groupid');
            $assignee->set_source_table('kanban_assignee', ['kanban_card' => backup::VAR_PARENTID]);
            $assignee->annotate_ids('userid', 'userid');
            $assignee->annotate_ids('kanban_card_id', 'kanban_card');
            $card->annotate_ids('userid', 'createdby');
            $discussion->set_source_table('kanban_discussion_comment', ['kanban_card' => backup::VAR_PARENTID]);
            $discussion->annotate_ids('userid', 'userid');
            $discussion->annotate_ids('kanban_card_id', 'kanban_card');
            $historyitem->set_source_table('kanban_history', ['kanban_board' => backup::VAR_PARENTID]);
            $historyitem->annotate_ids('userid', 'userid');
            $historyitem->annotate_ids('userid', 'affected_userid');
            $historyitem->annotate_ids('kanban_card_id', 'kanban_card');
            $historyitem->annotate_ids('kanban_column_id', 'kanban_column');
            $historyitem->annotate_ids('kanban_board_id', 'kanban_board');
            if (core_tag_tag::is_enabled('mod_kanban', 'kanban_card')) {
                $tag->set_source_sql('SELECT t.id, ti.itemid, t.rawname
                                        FROM {tag} t
                                        JOIN {tag_instance} ti ON ti.tagid = t.id
                                       WHERE ti.itemtype = ?
                                         AND ti.component = ?
                                         AND ti.contextid = ?', [
                    backup_helper::is_sqlparam('kanban_card'),
                    backup_helper::is_sqlparam('mod_kanban'),
                    backup::VAR_CONTEXTID]);
            }
        } else {
            $board->set_source_sql('
            SELECT *
              FROM {kanban_board}
             WHERE kanban_instance = ? AND userid = 0 AND groupid = 0 AND template = 1',
                [backup::VAR_PARENTID]);
            if (core_tag_tag::is_enabled('mod_kanban', 'kanban_card')) {
                $tag->set_source_sql('SELECT t.id, ti.itemid, t.rawname
                                        FROM {tag} t
                                        JOIN {tag_instance} ti ON ti.tagid = t.id
                                        JOIN {kanban_card} c ON ti.itemid = c.id
                                        JOIN {kanban_board} b ON c.kanban_board = b.id
                                            AND kanban_instance = ?
                                            AND userid = 0
                                            AND groupid = 0
                                            AND template = 1
                                        WHERE ti.itemtype = ?
                                            AND ti.component = ?
                                            AND ti.contextid = ?', [
                    backup_helper::is_sqlparam('kanban_card'),
                    backup_helper::is_sqlparam('mod_kanban'),
                    backup::VAR_CONTEXTID]);
            }
        }
        $column->set_source_table('kanban_column', ['kanban_board' => backup::VAR_PARENTID]);
        $card->set_source_table('kanban_card', ['kanban_column' => backup::VAR_PARENTID]);

        $board->annotate_ids('kanban_id', 'kanban_instance');
        $column->annotate_ids('kanban_board_id', 'kanban_board');
        $card->annotate_ids('kanban_board_id', 'kanban_board');
        $card->annotate_ids('kanban_column_id', 'kanban_column');

        return $this->prepare_activity_structure($kanban);
    }
}
