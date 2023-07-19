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
 * Class to handle updating the board
 *
 * @package    mod_kanban
 * @copyright  2023 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_kanban;

use context_module;
use context_system;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/kanban/lib.php');

/**
 * Class to handle updating the board. It also sends notifications, but does not check permissions.
 *
 * @package    mod_kanban
 * @copyright  2023 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class boardmanager {
    /**
     * Course module id
     *
     * @var int
     */
    private int $cmid;

    /**
     * The kanban instance record.
     *
     * @var object
     */
    private object $kanban;

    /**
     * The current board
     *
     * @var object
     */
    private object $board;

    /**
     * Shared update formatter collecting all updates.
     *
     * @var updateformatter
     */
    private updateformatter $formatter;

    /**
     * Course module info
     *
     * @var object
     */
    private object $cminfo;

    /**
     * Course
     *
     * @var object
     */
    private object $course;

    /**
     * Constructor
     *
     * @param int $cmid Course module id (0 if course module is not created yet)
     * @param int $boardid Board id (if 0, no board is loaded at this time)
     */
    public function __construct(int $cmid = 0, int $boardid = 0) {
        $this->cmid = $cmid;
        if ($cmid) {
            list($this->course, $this->cminfo) = get_course_and_cm_from_cmid($cmid);
            $this->load_instance($this->cminfo->instance);
        }
        $this->formatter = new updateformatter();
        if (!empty($boardid)) {
            $this->load_board($boardid);
        }
    }

    /**
     * Load a kanban instance
     *
     * @param int $instance Instance id
     * @param bool $dontloadcm Don't load course module data - only needed at instance creation time
     * @return void
     */
    public function load_instance(int $instance, bool $dontloadcm = false) {
        global $DB;
        $this->kanban = $DB->get_record('kanban', ['id' => $instance]);
        if (!$dontloadcm) {
            list ($this->course, $this->cminfo) = get_course_and_cm_from_instance($this->kanban->id, 'kanban');
            $this->cmid = $this->cminfo->id;
        }
    }

    /**
     * Load a board.
     *
     * @param int $id Id of the board
     * @return void
     */
    public function load_board(int $id) {
        global $DB;
        $this->board = $DB->get_record('kanban_board', ['id' => $id]);
        if (empty($this->cminfo)) {
            $this->load_instance($this->board->kanban_instance);
        }
    }

    /**
     * Get the current board record.
     *
     * @return object The current board
     */
    public function get_board() {
        return $this->board;
    }

    /**
     * Return representation of collected updates.
     *
     * @return string
     */
    public function format():string {
        return $this->formatter->format();
    }

    /**
     * Get the current template for this board. If there are multiple templates, use the latest one.
     *
     * @return int Board id of the template board, 0 if none found.
     */
    public function get_template_board_id(): int {
        global $DB;
        $result = $DB->get_records(
            'kanban_board',
            ['kanban_instance' => $this->kanban->id, 'template' => 1],
            'timemodified DESC',
            'id',
            0,
            1
        );
        if (!$result) {
            // Is there a system-wide template?
            $result = $DB->get_records('kanban_board', ['kanban_instance' => 0, 'template' => 1], 'timemodified DESC', 'id', 0, 1);
        }
        if (!$result) {
            return 0;
        } else {
            return array_pop($result)->id;
        }
    }

    /**
     * Creates a new user board.
     *
     * @param int $userid The user id (may not be 0, user existence is not checked)
     * @return int Id of the new board
     */
    public function create_user_board(int $userid): int {
        if (!empty($userid)) {
            return $this->create_board_from_template($this->get_template_board_id(), ['userid' => $userid, 'groupid' => 0]);
        }
        return 0;
    }

    /**
     * Creates a new group board.
     *
     * @param int $groupid The group id (may not be 0, group existence is not checked)
     * @return int Id of the new board
     */
    public function create_group_board(int $groupid): int {
        if (!empty($groupid)) {
            return $this->create_board_from_template($this->get_template_board_id(), ['userid' => 0, 'groupid' => $groupid]);
        }
        return 0;
    }

    /**
     * Saves the current board as template.
     *
     * @return int Id of the new board
     */
    public function create_template(): int {
        // For now, this function does not touch existing templates.
        $id = $this->create_board_from_template($this->board->id, ['template' => 1]);
        $this->formatter->put('common', ['template' => $id]);
        return $id;
    }

    /**
     * Creates a board for the whole course.
     *
     * @return int Id of the new board
     */
    public function create_board(): int {
        return $this->create_board_from_template($this->get_template_board_id(), ['userid' => 0, 'groupid' => 0]);
    }

    /**
     * Creates a new board from a template. If no template is given or found, the default template is used.
     * Assigned users, discussions and history are not copied.
     *
     * @param int $templateid Board id of the template.
     * @param array $data Data to override in the board record
     * @return int Id of the new board
     */
    public function create_board_from_template(int $templateid = 0, array $data = []): int {
        global $DB;
        if (empty($templateid)) {
            $templateid = $this->get_template_board_id();
        }
        // Template can still not exist (if kanban instance has none). Use default template.
        if (empty($templateid)) {
            $boarddata = [
                'sequence' => '',
                'userid' => 0,
                'groupid' => 0,
                'template' => 0,
                'timecreated' => time(),
                'timemodified' => time(),
                'kanban_instance' => $this->kanban->id
            ];
            // Replace / append data.
            $boarddata = array_merge($boarddata, $data);
            $boardid = $DB->insert_record('kanban_board', $boarddata);
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
            return $boardid;
        } else {
            $fs = get_file_storage();
            $template = $DB->get_record('kanban_board', ['id' => $templateid]);

            // If it is a site wide template, we need system context to copy files.
            if ($template->kanban_instance == 0) {
                $context = context_system::instance(0);
            } else {
                $context = context_module::instance($this->cmid, 'kanban');
            }

            $newboard = (array)$template;
            // By default, new board is not a template (can be overriden via $data).
            $newboard['template'] = 0;
            $newboard['timecreated'] = time();
            $newboard['timemodified'] = time();
            $newboard['userid'] = 0;
            $newboard['group'] = 0;
            unset($newboard['id']);

            $newboard = array_merge($newboard, $data);

            $newboard['id'] = $DB->insert_record('kanban_board', $newboard);
            $columns = $DB->get_records('kanban_column', ['kanban_board' => $template->id]);
            $cards = $DB->get_records('kanban_card', ['kanban_board' => $template->id]);
            $newcolumn = [];
            $newcard = [];
            foreach ($columns as $column) {
                $newcolumn[$column->id] = clone $column;
                $newcolumn[$column->id]->kanban_board = $newboard['id'];
                $newcolumn[$column->id]->timecreated = time();
                $newcolumn[$column->id]->timemodified = time();
                unset($newcolumn[$column->id]->id);
                $newcolumn[$column->id]->id = $DB->insert_record('kanban_column', $newcolumn[$column->id]);
            }
            foreach ($cards as $card) {
                $newcard[$card->id] = clone $card;
                $newcard[$card->id]->kanban_board = $newboard['id'];
                $newcard[$card->id]->timecreated = time();
                $newcard[$card->id]->timemodified = time();
                $newcard[$card->id]->kanban_column = $newcolumn[$card->kanban_column]->id;
                unset($newcard[$card->id]->id);
                // Remove user id of original creator.
                unset($newcard[$card->id]->createdby);
                $newcard[$card->id]->id = $DB->insert_record('kanban_card', $newcard[$card->id]);
                // Copy attachment files.
                if ($context) {
                    $attachments = $fs->get_area_files($context->id, 'mod_kanban', 'attachments', $card->id, 'filename', false);
                    foreach ($attachments as $attachment) {
                        $newfile = (array)$attachment;
                        $newfile['itemid'] = $newcard[$card->id]->id;
                        $fs->create_file_from_storedfile($newfile, $attachment);
                    }
                }
            }

            $newboard['sequence'] = helper::sequence_replace($newboard['sequence'], $newcolumn);
            $DB->update_record('kanban_board', $newboard);

            foreach ($newcolumn as $col) {
                $col->sequence = helper::sequence_replace($col->sequence, $newcard);
                $DB->update_record('kanban_column', $col);
            }
            return $newboard['id'];
        }
    }

    /**
     * Deletes a board and all contents of it.
     *
     * @param int $id The board id
     * @return void
     */
    public function delete_board(int $id) {
        global $DB;
        // Cards need to be read to identify files, assignees and discussions.
        $cardids = $DB->get_fieldset_select('kanban_card', 'id', 'kanban_board = :id', ['id' => $id]);
        $this->delete_cards($cardids);

        $DB->delete_records('kanban_history', ['kanban_board' => $id]);
        $DB->delete_records('kanban_column', ['kanban_board' => $id]);
        $DB->delete_records('kanban_card', ['kanban_board' => $id]);
        $DB->delete_records('kanban_board', ['id' => $id]);
        // The rest of the elements is skipped in the update message.
        $this->formatter->delete('board', ['id' => $id]);
    }

    /**
     * Delete multiple cards and all attached data (discussions, assignees, files, calendar events).
     *
     * @param array $ids The card ids
     * @param bool $updatecolumn Whether to update the column sequence (can be set to false, if column is going to be deleted)
     * @return void
     */
    public function delete_cards(array $ids, bool $updatecolumn = true) {
        foreach ($ids as $id) {
            $this->delete_card($id, $updatecolumn);
        }
    }

    /**
     * Delete a card and all attached data (discussions, assignees, files, calendar events).
     *
     * @param int $cardid Card id
     * @param bool $updatecolumn Whether to update the column sequence (can be set to false, if column is going to be deleted)
     * @return void
     */
    public function delete_card(int $cardid, bool $updatecolumn = true) {
        global $DB;
        $fs = get_file_storage();
        $DB->delete_records('kanban_discussion', ['kanban_card' => $cardid]);
        $DB->delete_records('kanban_assignee', ['kanban_card' => $cardid]);
        $context = context_module::instance($this->kanban->id, IGNORE_MISSING);
        $fs->delete_area_files($context->id, 'mod_kanban', 'attachments', $cardid);
        if ($updatecolumn) {
            $card = $this->get_card($cardid);
            $column = $DB->get_record('kanban_column', ['id' => $card->kanban_column]);
            $update = [
                'id' => $column->id,
                'timemodified' => time(),
                'sequence' => helper::sequence_remove($column->sequence, $cardid),
            ];
            $DB->update_record('kanban_column', $update);
            $this->formatter->put('columns', $update);
        }
        $DB->delete_records('kanban_card', ['id' => $cardid]);
        helper::remove_calendar_event($this->kanban, (object)['id' => $cardid]);
        $this->formatter->delete('cards', ['id' => $cardid]);
        $this->write_history('deleted', MOD_KANBAN_CARD, [], $card->kanban_column, $cardid);
    }

    /**
     * Delete a column and all cards inside.
     *
     * @param int $id The id of the column
     * @param bool $updateboard Whether to update the board sequence (can be set to false, if board is going to be deleted)
     * @return void
     */
    public function delete_column(int $id, bool $updateboard = true) {
        global $DB;
        $cardids = $DB->get_fieldset_select('kanban_card', 'id', 'kanban_column = :id', ['id' => $id]);
        $this->delete_cards($cardids, false);
        $DB->delete_records('kanban_column', ['id' => $id]);
        $this->formatter->delete('columns', ['id' => $id]);
        if ($updateboard) {
            $this->board->sequence = helper::sequence_remove($this->board->sequence, $id);
            $update = ['id' => $this->board->id, 'sequence' => $this->board->sequence, 'timemodified' => time()];
            $DB->update_record('kanban_board', $update);
            $this->formatter->put('board', $update);
        }
    }

    /**
     * Adds a new column.
     *
     * @param int $aftercol Id of the column before
     * @param array $data Data to override default values
     * @return void
     */
    public function add_column(int $aftercol = 0, array $data = []) {
        global $DB;
        if (empty($this->board->locked)) {
            $aftercol = intval($aftercol);
            $defaults = [
                'title' => get_string('newcolumn', 'mod_kanban'),
                'options' => '{}',
            ];
            $defaultsfixed = [
                'kanban_board' => $this->board->id,
                'timecreated' => time(),
                'timemodified' => time(),
                'sequence' => '',
            ];
            $data = array_merge($defaults, $data, $defaultsfixed);

            $data['id'] = $DB->insert_record('kanban_column', $data);

            $update = [
                'id' => $this->board->id,
                'sequence' => helper::sequence_add_after($this->board->sequence, $aftercol, $data['id']),
                'timemodified' => time()
            ];
            $DB->update_record('kanban_board', $update);

            $this->formatter->put('board', $update);
            $this->formatter->put('columns', $data);
        }
    }


    /**
     * Adds a new card.
     *
     * @param int $columnid Id of the column
     * @param int $aftercard Id of the card before (0 means to insert at top)
     * @param array $data Data to override default values
     * @return void
     */
    public function add_card(int $columnid, int $aftercard = 0, array $data = []) {
        global $DB, $USER;
        $aftercard = intval($aftercard);
        $defaults = [
            'title' => get_string('newcard', 'mod_kanban'),
            'options' => '{}',
            'description' => '',
            'createdby' => $USER->id
        ];
        $defaultsfixed = [
            'kanban_board' => $this->board->id,
            'kanban_column' => $columnid,
            'timecreated' => time(),
            'timemodified' => time(),
            'sequence' => '',
        ];
        $data = array_merge($defaults, $data, $defaultsfixed);

        $data['id'] = $DB->insert_record('kanban_card', $data);
        $data['assignees'] = [];

        $column = $DB->get_record('kanban_column', ['id' => $columnid]);

        $update = [
            'id' => $columnid,
            'sequence' => helper::sequence_add_after($column->sequence, $aftercard, $data['id']),
            'timemodified' => time()
        ];
        $DB->update_record('kanban_column', $update);

        // Users can always edit cards they created.
        $data['canedit'] = true;
        $data['columnname'] = $column->title;

        $this->formatter->put('cards', $data);
        $this->formatter->put('columns', $update);
        $this->write_history('added', MOD_KANBAN_CARD, $data, $columnid, $data['id']);
    }

    /**
     * Moves a column.
     *
     * @param int $columnid Id of the column to move
     * @param int $aftercol Id of the (future) column before (0 means to move at the leftmost position)
     * @return void
     */
    public function move_column(int $columnid, int $aftercol) {
        global $DB;
        $column = $DB->get_record('kanban_column', ['id' => $columnid]);
        if (!$this->board->locked && !$column->locked) {
            $update = [
                'id' => $this->board->id,
                'sequence' => helper::sequence_move_after($this->board->sequence, $aftercol, $columnid),
                'timemodified' => time(),
            ];
            $DB->update_record('kanban_board', $update);
            $this->formatter->put('board', $update);
        }
    }

    /**
     * Moves a card.
     *
     * @param int $cardid Id of the card to move
     * @param int $aftercard If of the card to move after (0 means move to top of the column)
     * @param int $columnid Id of the column to move to (if 0, use current column)
     * @return void
     */
    public function move_card(int $cardid, int $aftercard, int $columnid = 0) {
        global $DB, $USER;
        $card = $this->get_card($cardid);
        if (empty($columnid)) {
            $columnid = $card->kanban_column;
        }

        $sourcecolumn = $DB->get_record('kanban_column', ['id' => $card->kanban_column]);

        if ($card->kanban_column == $columnid) {
            $update = [
                'id' => $columnid,
                'sequence' => helper::sequence_move_after($sourcecolumn->sequence, $aftercard, $cardid),
                'timemodified' => time()
            ];
            $DB->update_record('kanban_column', $update);
            $this->formatter->put('columns', $update);
        } else {
            $targetcolumn = $DB->get_record('kanban_column', ['id' => $columnid]);

            // Remove from current column.
            $update = [
                'id' => $sourcecolumn->id,
                'sequence' => helper::sequence_remove($sourcecolumn->sequence, $cardid),
                'timemodified' => time()
            ];
            $DB->update_record('kanban_column', $update);
            $this->formatter->put('columns', $update);

            // Add to target column.
            $update = [
                'id' => $columnid,
                'sequence' => helper::sequence_add_after($targetcolumn->sequence, $aftercard, $cardid),
                'timemodified' => time()
            ];
            $DB->update_record('kanban_column', $update);
            $this->formatter->put('columns', $update);

            $updatecard = ['id' => $cardid, 'kanban_column' => $columnid, 'timemodified' => time()];
            // If target column has autoclose option set, update card to be completed.
            $options = json_decode($targetcolumn->options);
            if (!empty($options->autoclose)) {
                $updatecard['completed'] = 1;
            }
            $DB->update_record('kanban_card', $updatecard);
            $this->formatter->put('cards', $updatecard);

            $data = array_merge((array)$card, $updatecard);
            $data['username'] = fullname($USER);
            $data['boardname'] = $this->kanban->name;
            $data['columnname'] = $targetcolumn->title;
            $assignees = $this->get_card_assignees($cardid);
            helper::send_notification($this->cminfo, 'moved', $assignees, (object)$data);
            if (!empty($options->autoclose)) {
                $data['title'] = $card->title;
                helper::send_notification($this->cminfo, 'closed', $assignees, (object)$data);
                helper::remove_calendar_event($this->kanban, $card);
                $this->write_history('completed', MOD_KANBAN_CARD, [], $columnid, $cardid);
            }
            $this->write_history('moved', MOD_KANBAN_CARD, ['columnname' => $targetcolumn->title], $card->kanban_column, $cardid);
        }
    }

    /**
     * Assigns a user to a card.
     *
     * @param int $cardid Id of the card
     * @param int $userid Id of the user
     * @return void
     */
    public function assign_user(int $cardid, int $userid) {
        global $DB, $OUTPUT, $USER;
        $DB->insert_record('kanban_assignee', ['kanban_card' => $cardid, 'userid' => $userid]);
        $card = $this->get_card($cardid);
        $update = [
            'id' => $cardid,
            'timemodified' => time(),
        ];
        $DB->update_record('kanban_card', $update);

        helper::add_or_update_calendar_event($this->kanban, $card, [$userid]);

        $userids = $this->get_card_assignees($cardid);

        $update['assignees'] = $userids;
        $update['selfassigned'] = in_array($USER->id, $userids);
        $this->formatter->put('cards', $update);

        $user = \core_user::get_user($userid);
        $this->formatter->put('users', [
            'id' => $user->id,
            'fullname' => fullname($user),
            'userpicture' => $OUTPUT->user_picture($user, ['link' => false]),
        ]);

        $this->write_history('assigned', MOD_KANBAN_CARD, ['userid' => $userid], $card->kanban_column, $cardid);
    }

    /**
     * Unassigns a user from a card.
     *
     * @param int $cardid Id of the card
     * @param int $userid Id of the user
     * @return void
     */
    public function unassign_user(int $cardid, int $userid) {
        global $DB, $USER;
        $DB->delete_records('kanban_assignee', ['kanban_card' => $cardid, 'userid' => $userid]);
        $card = $this->get_card($cardid);
        $update = [
            'id' => $cardid,
            'timemodified' => time(),
        ];
        $DB->update_record('kanban_card', $update);

        helper::remove_calendar_event($this->kanban, (object)['id' => $cardid], [$userid]);

        $userids = $this->get_card_assignees($cardid);
        $userids = array_unique($userids);

        $update['assignees'] = $userids;
        $update['selfassigned'] = in_array($USER->id, $userids);
        $this->formatter->put('cards', $update);
        $this->write_history('unassigned', MOD_KANBAN_CARD, ['userid' => $userid], $card->kanban_column, $cardid);
    }

    /**
     * Changes completion state of a card.
     *
     * @param int $cardid Id of the card
     * @param int $state State
     * @return void
     */
    public function set_card_complete(int $cardid, int $state) {
        global $DB, $USER;
        $card = $this->get_card($cardid);
        $update = ['id' => $cardid, 'completed' => $state, 'timemodified' => time()];
        $this->formatter->put('cards', $update);
        $DB->update_record('kanban_card', $update);
        $assignees = $this->get_card_assignees($cardid);
        if ($state) {
            helper::remove_calendar_event($this->kanban, $card, $assignees);
        } else {
            helper::add_or_update_calendar_event($this->kanban, $card, $assignees);
        }
        $card->username = fullname($USER);
        $card->boardname = $this->kanban->name;
        helper::send_notification($this->cminfo, 'closed', $assignees, $card, ($state == 0 ? 'reopened' : null));
        $this->write_history(($state == 0 ? 'reopened' : 'completed'), MOD_KANBAN_CARD, $update, $card->kanban_column, $cardid);
    }

    /**
     * Changes lock state of a column.
     *
     * @param int $columnid Id of the column
     * @param int $state State
     * @return void
     */
    public function set_column_locked(int $columnid, int $state) {
        global $DB;
        $update = ['id' => $columnid, 'locked' => $state, 'timemodified' => time()];
        $DB->update_record('kanban_column', $update);
        $this->formatter->put('columns', $update);
    }

    /**
     * Changes lock state of all board columns.
     *
     * @param int $state State
     * @return void
     */
    public function set_board_columns_locked(int $state) {
        global $DB;
        $columns = $DB->get_fieldset_select('kanban_column', 'id', 'kanban_board = :id', ['id' => $this->board->id]);
        $update = ['id' => $this->board->id, 'locked' => $state, 'timemodified' => time()];
        $DB->update_record('kanban_board', $update);
        $this->formatter->put('board', $update);
        foreach ($columns as $col) {
            $this->set_column_locked($col, $state);
        }
    }

    /**
     * Add a message to a card discussion.
     *
     * @param int $cardid Id of the card
     * @param string $message Message
     * @return void
     */
    public function add_discussion_message(int $cardid, string $message) {
        global $DB, $USER;
        $card = $this->get_card($cardid);
        $update = ['kanban_card' => $cardid, 'content' => $message, 'userid' => $USER->id, 'timecreated' => time()];
        $update['id'] = $DB->insert_record('kanban_discussion', $update);
        $update['candelete'] = true;
        $update['username'] = fullname($USER);
        $this->formatter->put('discussions', $update);

        if (empty($card->discussion)) {
            $update = ['id' => $cardid, 'discussion' => 1, 'timemodified' => time()];
            $DB->update_record('kanban_card', $update);
            $this->formatter->put('cards', $update);
        }

        $update['boardname'] = $this->kanban->name;
        $update['title'] = $card->title;
        $assignees = $this->get_card_assignees($cardid);
        helper::send_notification($this->cminfo, 'discussion', $assignees, (object)$update);
        $this->write_history('added', MOD_KANBAN_DISCUSSION, $update, $card->kanban_column, $cardid);
    }

    /**
     * Delete a message from a discussion.
     *
     * @param int $messageid Id of the message
     * @param int $cardid Id of the card
     * @return void
     */
    public function delete_discussion_message(int $messageid, int $cardid) {
        global $DB;
        $card = $this->get_card($cardid);
        $update = ['id' => $messageid];
        $DB->delete_records('kanban_discussion', $update);
        $this->formatter->delete('discussions', $update);
        $this->write_history('deleted', MOD_KANBAN_DISCUSSION, $update, $card->kanban_column, $cardid);
        if (!$DB->record_exists('kanban_discussions', ['kanban_card' => $cardid])) {
            $update = ['id' => $cardid, 'discussion' => 0, 'timemodified' => time()];
            $DB->update_record('kanban_card', $update);
            $this->formatter->put('cards', $update);
        }
    }

    /**
     * Updates a card with the given values.
     *
     * @param int $cardid Id of the card
     * @param array $data Data to update
     * @return void
     */
    public function update_card(int $cardid, array $data) {
        global $DB, $OUTPUT;
        $context = context_module::instance($this->cmid);
        $cardkeys = [
            'id',
            'title',
            'description',
            'descriptionformat',
            'duedate',
            'reminderdate',
            'options',
            'kanban_column',
            'kanban_board',
            'completed'
        ];
        if (!empty($data['color'])) {
            $data['options'] = json_encode(['background' => $data['color']]);
        }
        $card = (array)$this->get_card($cardid);
        $cardupdate = [];
        foreach ($cardkeys as $key) {
            if (!isset($data[$key])) {
                continue;
            }
            if ($card[$key] != $data[$key]) {
                $cardupdate[$key] = $data[$key];
            }
        }
        $cardupdate['id'] = $cardid;
        $cardupdate['timemodified'] = time();
        if (count($cardupdate) > 2) {
            $DB->update_record('kanban_card', $cardupdate);
        }
        $carddata = array_merge($card, $cardupdate);
        if (isset($data['assignees'])) {
            $assignees = $data['assignees'];
            $currentassignees = $this->get_card_assignees($cardid);
            $toinsert = array_diff($assignees, $currentassignees);
            $todelete = array_diff($currentassignees, $assignees);

            helper::add_or_update_calendar_event($this->kanban, (object)$carddata, $assignees);
            if (!empty($todelete)) {
                helper::remove_calendar_event($this->kanban, (object)$carddata, $todelete);
                list($sql, $params) = $DB->get_in_or_equal($todelete, SQL_PARAMS_NAMED);
                $sql = 'kanban_card = :cardid AND user ' . $sql;
                $params['cardid'] = $cardid;
                $DB->delete_records_select('kanban_assignee', $sql, $params);
                helper::send_notification($this->cminfo, 'assigned', $todelete, (object)$carddata, 'unassigned');
                foreach ($todelete as $user) {
                    $this->write_history('unassigned', MOD_KANBAN_CARD, ['userid' => $user], $card['kanban_column'], $card['id']);
                }
            }
            if (!empty($toinsert) || !empty($todelete)) {
                $cardupdate['assignees'] = $assignees;
            }
            $assignees = [];
            foreach ($toinsert as $assignee) {
                $assignees[] = ['kanban_card' => $cardid, 'userid' => $assignee];
                $user = \core_user::get_user($assignee);
                $this->formatter->put('users', [
                    'id' => $user->id,
                    'fullname' => fullname($user),
                    'userpicture' => $OUTPUT->user_picture($user, ['link' => false])]
                );
            }
            $DB->insert_records('kanban_assignee', $assignees);
            helper::send_notification(
                $this->cminfo,
                'assigned',
                $toinsert,
                (object)array_merge($carddata, ['boardname' => $this->cminfo->name])
            );
            foreach ($toinsert as $user) {
                $this->write_history('assigned', MOD_KANBAN_CARD, ['userid' => $user], $card['kanban_column'], $card['id']);
            }
        }
        $cardupdate['attachments'] = helper::get_attachments($context->id, $cardid);
        $cardupdate['hasattachment'] = count($cardupdate['attachments']) > 0;
        $cardupdate['hasdescription'] = !empty(trim($cardupdate['description'])) || $cardupdate['hasattachment'];
        if (!empty($cardupdate['description'])) {
            $cardupdate['description'] = file_rewrite_pluginfile_urls(
                $cardupdate['description'],
                'pluginfile.php',
                $context->id,
                'mod_kanban',
                'attachments',
                $cardupdate['id']
            );
        }
        $this->formatter->put('cards', $cardupdate);

        $this->write_history('updated', MOD_KANBAN_CARD, $cardupdate, $card['kanban_column'], $card['id']);
    }

    /**
     * Updates a column with the given values.
     *
     * @param int $columnid Id of the column
     * @param array $data Data to update
     * @return void
     */
    public function update_column(int $columnid, array $data) {
        global $DB;
        $column = $this->get_column($columnid);
        $options = [
            'autoclose' => $data['autoclose'],
            'autohide' => $data['autohide'],
        ];
        $columndata = [
            'id' => $columnid,
            'title' => $data['title'],
            'options' => json_encode($options),
            'timemodified' => time(),
        ];

        $DB->update_record('kanban_column', $columndata);

        $this->formatter->put('columns', $columndata);

        if ($column->title != $columndata['title']) {
            $this->write_history('updated', MOD_KANBAN_COLUMN, $columndata, $columnid);
        }
    }

    /**
     * Returns the ids of all users assignes to a card.
     *
     * @param int $cardid Id of the card
     * @return array Array of userids
     */
    public function get_card_assignees(int $cardid): array {
        global $DB;
        return array_unique($DB->get_fieldset_select('kanban_assignee', 'userid', 'kanban_card = :id', ['id' => $cardid]));
    }

    /**
     * Get a card record.
     *
     * @param int $cardid Id of the card
     * @return object
     */
    public function get_card(int $cardid): object {
        global $DB;
        return $DB->get_record('kanban_card', ['id' => $cardid]);
    }

    /**
     * Get a column record.
     *
     * @param int $columnid Id of the card
     * @return object
     */
    public function get_column(int $columnid): object {
        global $DB;
        return $DB->get_record('kanban_column', ['id' => $columnid]);
    }

    /**
     * Get a discussion record.
     *
     * @param int $messageid Id of the message
     * @return object
     */
    public function get_discussion_message(int $messageid): object {
        global $DB;
        return $DB->get_record('kanban_discussion', ['id' => $messageid]);
    }

    /**
     * Get cm_info object to current instance.
     *
     * @return object
     */
    public function get_cminfo(): object {
        return $this->cminfo;
    }

    /**
     * Writes a record to the history table.
     * @param string $action Action for history
     * @param int $type Type of object affected by the entry
     * @param array $data Array of data to write
     * @param int $columnid Id of the column
     * @param int $cardid Id of the card
     */
    public function write_history(string $action, int $type, array $data = [], int $columnid = 0, int $cardid = 0) {
        global $DB, $USER;
        if (!empty($this->kanban->history) && !empty(get_config('mod_kanban', 'enablehistory'))) {
            $affecteduser = null;
            // Affected user must be written to a separate column (for privacy provider).
            if (!empty($data['userid'])) {
                $affecteduser = $data['userid'];
                unset($data['userid']);
            }
            // Unset unused data.
            unset($data['timemodified']);
            unset($data['timecreated']);
            unset($data['createdby']);
            unset($data['canedit']);
            unset($data['id']);
            $record = [
                'action' => $action,
                'kanban_board' => $this->board->id,
                'userid' => $USER->id,
                'kanban_column' => $columnid,
                'kanban_card' => $cardid,
                'parameters' => json_encode($data),
                'affected_userid' => $affecteduser,
                'timestamp' => time(),
                'type' => $type,
            ];
            $DB->insert_record('kanban_history', $record);
        }
    }
}
