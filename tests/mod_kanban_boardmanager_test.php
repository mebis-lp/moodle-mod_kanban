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
}
