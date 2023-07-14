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

/**
 * Language file for mod_kanban
 *
 * @package     mod_kanban
 * @copyright   2023, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addcard'] = 'Add a card to this column';
$string['addcolumn'] = 'Add a column to this board';
$string['assignee'] = 'Assignee';
$string['assignees'] = 'Assignees';
$string['assignme'] = 'Assign me';
$string['attachments'] = 'Attachments';
$string['autoclose'] = 'Auto close cards';
$string['autohide'] = 'Auto hide closed cards';
$string['cardtitle'] = 'Card title';
$string['color'] = 'Color';
$string['column'] = 'Column';
$string['columntitle'] = 'Column title';
$string['courseboard'] = 'Course board';
$string['closecard'] = 'Close card';
$string['createtemplate'] = 'Create template';
$string['deletecard'] = 'Delete card';
$string['deletecardconfirm'] = 'Do you really want to delete this card?';
$string['deletecolumn'] = 'Delete column';
$string['deletecolumnconfirm'] = 'Do you really want to delete this column?';
$string['deletemessage'] = 'Delete message';
$string['deletemessageconfirm'] = 'Do you really want to delete this message?';
$string['doing'] = 'Doing';
$string['done'] = 'Done';
$string['duedate'] = 'Due date';
$string['editboard'] = 'Edit board';
$string['editcard'] = 'Edit card';
$string['editcolumn'] = 'Edit column';
$string['editdetails'] = 'Edit details';
$string['groupboard'] = 'Group board for group "{$a}"';
$string['kanban:addcard'] = 'Add a card to a Kanban board';
$string['kanban:addinstance'] = 'Add a Kanban board';
$string['kanban:assignothers'] = 'Assign others to a card';
$string['kanban:assignself'] = 'Assign self to a card';
$string['kanban:editallboards'] = 'Edit all boards';
$string['kanban:managecards'] = 'Edit the cards of the board';
$string['kanban:managecolumns'] = 'Edit the columns of the board';
$string['kanban:moveallcards'] = 'Move all cards';
$string['kanban:moveassignedcards'] = 'Move cards assigned to self';
$string['kanban:view'] = 'View a Kanban board';
$string['kanban:viewallboards'] = 'View all boards';
$string['kanban:viewhistory'] = 'View the history of the board';
$string['loading'] = 'Loading kanban board';
$string['loadingdiscussion'] = 'Loading discussion';
$string['lock'] = 'Lock';
$string['lockboardcolumns'] = 'Lock board columns';
$string['message_assigned_fullmessage'] = 'Card "{$a->title}" in board "{$a->boardname}" was assigned to you by {$a->username}';
$string['message_assigned_smallmessage'] = 'Card "{$a->title}" was assigned to you';
$string['message_assigned_subject'] = 'Card "{$a->title}" assigned to you';
$string['message_closed_fullmessage'] = 'Card "{$a->title}" was closed by {$a->username}';
$string['message_closed_smallmessage'] = 'Card "{$a->title}" closed';
$string['message_closed_subject'] = 'Card "{$a->title}" closed';
$string['message_discussion_fullmessage'] = 'There is a new message in discussion for card "{$a->title}" in board "{$a->boardname}":
{$a->username}
{$a->content}';
$string['message_discussion_smallmessage'] = 'Card "{$a->title}" discussed';
$string['message_discussion_subject'] = 'Card "{$a->title}" discussed';
$string['message_due_fullmessage'] = 'Card "{$a->title}" in board "{$a->boardname}" is due at {$a->username}';
$string['message_due_smallmessage'] = 'Card "{$a->title}" due';
$string['message_due_subject'] = 'Card "{$a->title}" due';
$string['message_moved_fullmessage'] = 'Card "{$a->title}" was moved to column "{$a->columnname}" by {$a->username}';
$string['message_moved_smallmessage'] = 'Card "{$a->title}" moved';
$string['message_moved_subject'] = 'Card "{$a->title}" moved';
$string['message_reopened_fullmessage'] = 'Card "{$a->title}" in board "{$a->boardname}" was reopened by {$a->username}';
$string['message_reopened_smallmessage'] = 'Card "{$a->title}" reopened';
$string['message_reopened_subject'] = 'Card "{$a->title}" reopened';
$string['message_unassigned_fullmessage'] = 'Card "{$a->title}" in board "{$a->boardname}" was unassigned from you by {$a->username}';
$string['message_unassigned_smallmessage'] = 'Card "{$a->title}" was unassigned from you';
$string['message_unassigned_subject'] = 'Card "{$a->title}" unassigned from you';
$string['messageprovider:assigned'] = 'Card assigned / unassigned';
$string['messageprovider:closed'] = 'Card closed / reopened';
$string['messageprovider:discussion'] = 'Card discussion';
$string['messageprovider:due'] = 'Card due';
$string['messageprovider:moved'] = 'Card moved';
$string['modulename'] = 'Kanban board';
$string['modulenameplural'] = 'Kanban boards';
$string['movecard'] = 'Move card';
$string['movecolumn'] = 'Move column';
$string['myuserboard'] = 'My personal board';
$string['name'] = 'Name of the board';
$string['name_help'] = 'This name will be visible in course overview and as a title of the board';
$string['newcard'] = 'New card';
$string['newcolumn'] = 'New column';
$string['nogroupavailable'] = 'No group available';
$string['nouserboards'] = 'No personal boards';
$string['pluginadministration'] = 'Kanban administration';
$string['pluginname'] = 'Kanban board';
$string['reminderdate'] = 'Reminder date';
$string['senddiscussion'] = 'Send discussion message';
$string['showattachment'] = 'Show attachments';
$string['showdescription'] = 'Show description';
$string['showdiscussion'] = 'Show discussion';
$string['showhidden'] = 'Show hidden cards';
$string['startdiscussion'] = 'Start discussion';
$string['toboard'] = 'Board "{$a->boardname}"';
$string['todo'] = 'Todo';
$string['unassign'] = 'Unassign this user';
$string['unassignme'] = 'Unassign me';
$string['uncomplete'] = 'Reopen';
$string['unlock'] = 'Unlock';
$string['unlockboardcolumns'] = 'Unlock board columns';
$string['userboard'] = 'Personal board for {$a}';
$string['userboards'] = 'Personal boards';
$string['userboardsenabled'] = 'Personal boards enabled';
$string['userboardsonly'] = 'Personal boards only';
