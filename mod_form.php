<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Editing form for mod_kanban
 *
 * @package     mod_kanban
 * @copyright   2023, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_kanban_mod_form extends moodleform_mod {
    /**
     * Defines the editing form for mod_kanban
     *
     * @return void
     */
    public function definition() : void {
        $mform = $this->_form;

        $mform->addElement('header', 'generalhdr', get_string('general'));

        $mform->addElement('text', 'name', get_string('name', 'kanban'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addHelpButton('name', 'name', 'kanban');

        $this->standard_intro_elements(get_string('description'));

        $userboards = [
            MOD_KANBAN_NOUSERBOARDS => get_string('nouserboards', 'kanban'),
            MOD_KANBAN_USERBOARDS_ENABLED => get_string('userboardsenabled', 'kanban'),
            MOD_KANBAN_USERBOARDS_ONLY => get_string('userboardsonly', 'kanban'),
        ];
        $mform->addElement('select', 'userboards', get_string('userboards', 'kanban'), $userboards);

        if (!empty(get_config('mod_kanban', 'enablehistory'))) {
            $mform->addElement('checkbox', 'history', get_string('enablehistory', 'mod_kanban'));
        }

        $this->standard_coursemodule_elements();

        $this->add_action_buttons(true, false, null);
    }
}
