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
 * @copyright   2023, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_kanban\external\change_kanban_content
 * @runTestsInSeparateProcesses
 */
class change_kanban_content_test extends \advanced_testcase {
    /** @var \stdClass The course used for testing */
    private $course;
    /** @var \stdClass The kanban used for testing */
    private $kanban;
    /** @var array The users used for testing */
    private $users;

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
     * Test for creating a column.
     *
     * @return void
     */
    public function test_add_column() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/lib/externallib.php');

        $this->resetAfterTest();
        $this->setUser($this->users[2]);

        $boardmanager = new boardmanager($this->kanban->cmid);
        $boardid = $boardmanager->create_board();
        $boardmanager->load_board($boardid);
        $columnids = $DB->get_fieldset_select('kanban_column', 'id', 'kanban_board = :id', ['id' => $boardid]);

        $returnvalue = \mod_kanban\external\change_kanban_content::add_column(
            $this->kanban->cmid,
            $boardid,
            ['aftercol' => 0, 'title' => 'Testcolumn']
        );
        $returnvalue = \external_api::clean_returnvalue(
            \mod_kanban\external\change_kanban_content::add_column_returns(),
            $returnvalue
        );

        $update = json_decode($returnvalue['update'], true);

        $this->assertCount(2, $update);
        $this->assertEquals('board', $update[0]['name']);
        $this->assertEquals('columns', $update[1]['name']);
        $columnid = $update[1]['fields']['id'];

        $columnids = array_merge([$columnid], $columnids);
        $this->assertEquals(join(',', $columnids), $update[0]['fields']['sequence']);

        $this->assertEquals(1, $DB->count_records('kanban_column', ['id' => $columnid]));

        $returnvalue = \mod_kanban\external\change_kanban_content::add_column(
            $this->kanban->cmid,
            $boardid,
            ['aftercol' => $columnids[3], 'title' => 'Testcolumn 2']
        );
        $returnvalue = \external_api::clean_returnvalue(
            \mod_kanban\external\change_kanban_content::add_column_returns(),
            $returnvalue
        );

        $update = json_decode($returnvalue['update'], true);
        $this->assertCount(2, $update);
        $columnid = $update[1]['fields']['id'];

        $columnids = array_merge($columnids, [$columnid]);
        $this->assertEquals(join(',', $columnids), $update[0]['fields']['sequence']);

        $this->assertEquals(1, $DB->count_records('kanban_column', ['id' => $columnid]));
    }
}
