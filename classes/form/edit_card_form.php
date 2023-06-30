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

namespace mod_kanban\form;

use core_form\dynamic_form;
use moodle_url;
use context;
use context_module;
use mod_kanban\updateformatter;

/**
 * Class for delivering kanban content
 *
 * @package    mod_kanban
 * @copyright  2023 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_card_form extends dynamic_form {
    /**
     * Define the form
     */
    public function definition() {
        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'kanban_board');
        $mform->setType('kanban_board', PARAM_INT);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('text', 'title', get_string('cardtitle', 'kanban'), ['size' => '50']);
        $mform->setType('text', PARAM_TEXT);

        $mform->addElement('editor', 'description', get_string('description'));
        /*
        Not used yet.
        $mform->addElement('date_time_selector', 'duedate', get_string('duedate', 'kanban'));

        $mform->addElement('date_time_selector', 'reminderdate', get_string('reminderdate', 'kanban'));

        $mform->addElement('filemanager', 'attachments', get_string('attachments', 'kanban'));
        */
    }

    /**
     * Returns context where this form is used
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        return context_module::instance($cmid);
    }

    /**
     * Checks if current user has access to this card, otherwise throws exception
     */
    protected function check_access_for_dynamic_submission(): void {
        global $COURSE, $DB;
        $context = $this->get_context_for_dynamic_submission();
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        $kanban_board = $this->optional_param('kanban_board', null, PARAM_INT);
        $kanbanboard = $DB->get_record('kanban_board', ['id' => $kanban_board]);
        $id = $this->optional_param('id', null, PARAM_INT);
        require_capability('mod/kanban:managecards', $context);
        $modinfo = get_fast_modinfo($COURSE);
        $cm = $modinfo->get_cm($cmid);
        \mod_kanban\helper::check_permissions_for_user_or_group($kanbanboard, $context, $cm);
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * @return array Returns whether a new template was created.
     */
    public function process_dynamic_submission(): array {
        global $DB;
        $formdata = $this->get_data();

        $carddata = [
            'id' => $formdata->id,
            'title' => $formdata->title,
            'description' => $formdata->description['text'],
            'duedate' => $formdata->duedate,
            'reminderdate' => $formdata->reminderdate,
            'timemodified' => time(),
        ];
        $result = $DB->update_record('kanban_card', $carddata);
        $formatter = new updateformatter();
        $carddata['hasdescription'] = !empty(trim($carddata['description']));
        $formatter->put('cards', $carddata);
        $updatestr = $formatter->format();
        // Handle attachment files.
        return [
            'result' => $result,
            'update' => $updatestr,
        ];
    }

    /**
     * Load in existing data as form defaults
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;
        $id = $this->optional_param('id', null, PARAM_INT);
        $card = $DB->get_record('kanban_card', ['id' => $id]);
        $card->cmid = $this->optional_param('cmid', null, PARAM_INT);
        $this->set_data($card);
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $params = [
            'id' => $this->optional_param('id', null, PARAM_INT),
            'kanban_board' => $this->optional_param('kanban_board', null, PARAM_INT),
            'cmid' => $this->optional_param('cmid', null, PARAM_INT),
        ];
        return new moodle_url('/mod/kanban/view.php', $params);
    }
}
