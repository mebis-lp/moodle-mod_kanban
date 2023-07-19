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

namespace mod_kanban;

/**
 * Unit test for mod_kanban
 *
 * @package     mod_kanban
 * @copyright   2021-2023, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_kanban\boardmanager
 */
class mod_kanban_boardmanager_test extends \advanced_testcase {
    /**
     * The course used for testing
     *
     * @var \stdClass
     */
    protected $course;
    /**
     * The kanban used for testing
     *
     * @var \stdClass
     */
    protected $kanban;
    /**
     * The users used for testing
     *
     * @var array
     */
    protected $users;

    /**
     * Prepare testing environment
     */
    public function setUp(): void {
        global $DB;
        $this->course = $this->getDataGenerator()->create_course();
        $this->kanban = $this->getDataGenerator()->create_module('kanban', ['course' => $this->course]);

        for ($i = 0; $i < 3; $i++) {
            $this->users[$i] = $this->getDataGenerator()->create_user(
                [
                    'email' => $i . 'user@example.com',
                    'username' => 'userid' . $i,
                ]
            );
        }

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $this->getDataGenerator()->enrol_user($this->users[0]->id, $this->course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($this->users[1]->id, $this->course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($this->users[2]->id, $this->course->id, $teacherrole->id);
    }

    /**
     * Test for creating a (course) board.
     *
     * @return void
     */
    public function test_create_board() {
        global $DB;

        $this->resetAfterTest();

        $boardmanager = new boardmanager($this->kanban->cmid);
        $boards = $DB->get_records('kanban_board', ['kanban_instance' => $this->kanban->id]);
        $this->assertCount(1, $boards);
        $boardid = $boardmanager->create_board();
        $this->assertNotEquals(false, $boardid);
        $boards = $DB->get_records('kanban_board', ['kanban_instance' => $this->kanban->id]);
        $this->assertCount(2, $boards);
        // Board should consist of three columns without any cards as there is no template yet.
        $columns = $DB->get_records('kanban_column', ['kanban_board' => $boardid]);
        $this->assertCount(3, $columns);
        $cards = $DB->get_records('kanban_card', ['kanban_board' => $boardid]);
        $this->assertCount(0, $cards);
    }

    /**
     * Test for creating a card.
     *
     * @return void
     */
    public function test_add_card() {
        global $DB;

        $this->resetAfterTest();

        $boardmanager = new boardmanager($this->kanban->cmid);
        $boardid = $boardmanager->create_board();
        $boardmanager->load_board($boardid);
        $columnid = $DB->get_field('kanban_column', 'id', ['kanban_board' => $boardid], IGNORE_MULTIPLE);
        $cardid = $boardmanager->add_card($columnid, 0, ['title' => 'Testcard']);
        $card = $boardmanager->get_card($cardid);
        $this->assertEquals('Testcard', $card->title);
        $this->assertEquals($boardid, $card->kanban_board);
        $this->assertEquals($columnid, $card->kanban_column);

        $card2id = $boardmanager->add_card($columnid, $cardid, ['title' => 'Testcard2']);
        $column = $boardmanager->get_column($columnid);
        $this->assertEquals(join(',', [$cardid, $card2id]), $column->sequence);
    }

    /**
     * Test for moving a card.
     *
     * @return void
     */
    public function test_move_card() {
        global $DB;

        $this->resetAfterTest();

        $boardmanager = new boardmanager($this->kanban->cmid);
        $boardid = $boardmanager->create_board();
        $boardmanager->load_board($boardid);
        $columnids = $DB->get_fieldset_select('kanban_column', 'id', 'kanban_board = :id', ['id' => $boardid]);
        // Add one card to each column (three columns expected).
        $cards = [];
        foreach ($columnids as $columnid){
            $cardid = $boardmanager->add_card($columnids[0], 0, ['title' => 'Testcard']);
            $cards[] = $boardmanager->get_card($cardid);
        }
        $boardmanager->move_card($cards[0]->id, 0, $columnids[2]);
        $cards[0] = $boardmanager->get_card($cards[0]->id);
        $this->assertEquals($columnids[2], $cards[0]->kanban_column);

        $column = $boardmanager->get_column($columnids[0]);
        $this->assertEquals('', $column->sequence);

        $column = $boardmanager->get_column($columnids[2]);
        $this->assertEquals(join(',', [$cards[0]->id, $cards[2]->id]), $column->sequence);
        
        $boardmanager->move_card($cards[0]->id, $cards[2]->id);
        $cards[0] = $boardmanager->get_card($cards[0]->id);
        $this->assertEquals($columnids[2], $cards[0]->kanban_column);

        $column = $boardmanager->get_column($columnids[2]);
        $this->assertEquals($column->sequence, join(',', [$cards[2]->id, $cards[0]->id]));

        $boardmanager->move_card($cards[1]->id, $cards[2]->id, $columnids[2]);
        $cards[1] = $boardmanager->get_card($cards[1]->id);
        $this->assertEquals($columnids[2], $cards[1]->kanban_column);

        $column = $boardmanager->get_column($columnids[2]);
        $this->assertEquals($column->sequence, join(',', [$cards[2]->id, $cards[1]->id, $cards[0]->id]));
    }
}
