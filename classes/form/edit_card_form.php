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
use mod_kanban\helper;

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

        $mform->addElement('hidden', 'boardid');
        $mform->setType('boardid', PARAM_INT);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('text', 'title', get_string('cardtitle', 'kanban'), ['size' => '50']);
        $mform->setType('text', PARAM_TEXT);

        $userid = $this->optional_param('userid', 0, PARAM_INT);
        $groupid = $this->optional_param('groupid', 0, PARAM_INT);

        $context = $this->get_context_for_dynamic_submission();
        if (has_capability('mod/kanban:assignothers', $context)) {
            $userlist = get_enrolled_users($context, '', $groupid);

            $users = [];
            foreach ($userlist as $user) {
                if (!empty($userid) && $userid != $user->id) {
                    continue;
                }
                $users[$user->id] = fullname($user);
            }
            $mform->addElement(
                'autocomplete',
                'assignees',
                get_string('assignees', 'mod_kanban'),
                $users,
                ['multiple' => true]
            );
        }

        $mform->addElement('editor', 'description_editor', get_string('description'), null, ['maxfiles' => -1]);
        $mform->setType('description_editor', PARAM_RAW);

        $mform->addElement('date_time_selector', 'duedate', get_string('duedate', 'kanban'), ['optional' => true]);

        $mform->addElement('date_time_selector', 'reminderdate', get_string('reminderdate', 'kanban'), ['optional' => true]);

        $mform->addElement('filemanager', 'attachments', get_string('attachments', 'kanban'));
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
        $boardid = $this->optional_param('boardid', null, PARAM_INT);
        $kanbanboard = $DB->get_record('kanban_board', ['id' => $boardid]);
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
        global $DB, $USER;
        $context = $this->get_context_for_dynamic_submission();
        $formdata = $this->get_data();
        $carddata = [
            'id' => $formdata->id,
            'title' => $formdata->title,
            'description' => $formdata->description_editor['text'],
            'descriptionformat' => $formdata->description_editor['format'],
            'duedate' => $formdata->duedate,
            'reminderdate' => $formdata->reminderdate,
            'timemodified' => time(),
        ];

        $draftinfo = file_get_draft_area_info($formdata->attachments);
        $carddata['description'] = file_save_draft_area_files(
            $formdata->attachments,
            $context->id,
            'mod_kanban',
            'attachments',
            $formdata->id,
            [],
            $carddata['description']
        );

        $result = $DB->update_record('kanban_card', $carddata);
        if (has_capability('mod/kanban:assignothers', $context)) {
            $result2 = $DB->delete_records('kanban_assignee', ['kanban_card' => $formdata->id]);
        }
        $assignees = [];
        foreach ($formdata->assignees as $assignee) {
            if (has_capability('mod/kanban:assignothers', $context) || $assignee == $USER->id) {
                $assignees[] = ['kanban_card' => $formdata->id, 'user' => $assignee];
            }
        }
        $result3 = $DB->insert_records('kanban_assignee', $assignees);

        $carddata['description'] = file_rewrite_pluginfile_urls(
            $carddata['description'],
            'pluginfile.php',
            $context->id,
            'mod_kanban',
            'attachments',
            $carddata['id']
        );

        $carddata['attachments'] = helper::get_attachments($context->id, $formdata->id);
        $carddata['hasattachment'] = count($carddata['attachments']) > 0;
        $formatter = new updateformatter();
        $carddata['hasdescription'] = !empty(trim($carddata['description'])) || $draftinfo['filecount'] > 0;
        $carddata['assignees'] = $formdata->assignees;
        $formatter->put('cards', $carddata);
        $updatestr = $formatter->format();
        return [
            'result' => $result && $result2 && $result3,
            'update' => $updatestr,
        ];
    }

    /**
     * Load in existing data as form defaults
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;
        $context = $this->get_context_for_dynamic_submission();
        $id = $this->optional_param('id', null, PARAM_INT);
        $card = $DB->get_record('kanban_card', ['id' => $id]);
        $card->cmid = $this->optional_param('cmid', null, PARAM_INT);
        $card->boardid = $card->kanban_board;
        $card->assignees = $DB->get_fieldset_select('kanban_assignee', 'user', 'kanban_card = :cardid', ['cardid' => $id]);
        $draftitemid = file_get_submitted_draft_itemid('attachments');
        $card->description = file_prepare_draft_area(
            $draftitemid,
            $context->id,
            'mod_kanban',
            'attachments',
            $card->id,
            [],
            $card->description
        );
        $card->description_editor['text'] = $card->description;
        $card->description_editor['format'] = $card->descriptionformat;
        $card->description_editor['itemid'] = $draftitemid;
        $card->attachments = $draftitemid;
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
            'boardid' => $this->optional_param('boardid', null, PARAM_INT),
            'cmid' => $this->optional_param('cmid', null, PARAM_INT),
        ];
        return new moodle_url('/mod/kanban/view.php', $params);
    }
}
