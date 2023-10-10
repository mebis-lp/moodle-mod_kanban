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
 * German language file for mod_kanban - will be moved to AMOS once the plugin is approved.
 *
 * @package     mod_kanban
 * @copyright   2023, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addcard'] = 'Füge eine Karte zu dieser Spalte hinzu';
$string['addcolumn'] = 'Füge eine Spalte zu diesem Board hinzu';
$string['assignee'] = 'Verantwortliche(r)';
$string['assignees'] = 'Verantwortliche';
$string['assignme'] = 'Mir zuweisen';
$string['attachments'] = 'Anhänge';
$string['autoclose'] = 'Karten automatisch abschließen';
$string['autohide'] = 'Abgeschlossene Karten automatisch verbergen';
$string['cardtitle'] = 'Titel der Karte';
$string['changegroup'] = 'Zu anderem Gruppen-Board wechseln';
$string['changeuser'] = 'Zu anderem persönlichen Board wechseln';
$string['color'] = 'Farbe';
$string['column'] = 'Spalte';
$string['columntitle'] = 'Spaltentitel';
$string['completioncreate'] = 'Diese Anzahl an Karten erstellen';
$string['completioncomplete'] = 'Diese Anzahl an Karten abschließen';
$string['completiondetail:create'] = 'Karten erstellen: {$a}';
$string['completiondetail:complete'] = 'Karten abschließen: {$a}';
$string['courseboard'] = 'Gemeinsames Board';
$string['closecard'] = 'Karte abschließen';
$string['createtemplate'] = 'Als Vorlage speichern';
$string['deleteboard'] = 'Board löschen';
$string['deleteboardconfirm'] = 'Sind Sie sicher, dass Sie dieses Board löschen wollen? Es wird ein neues Board aus einer Vorlage erstellt.';
$string['deletecard'] = 'Karte löschen';
$string['deletecardconfirm'] = 'Möchten Sie diese Karte wirklich löschen?';
$string['deletecolumn'] = 'Spalte löschen';
$string['deletecolumnconfirm'] = 'Möchten Sie diese Spalte wirklich löschen?';
$string['deletemessage'] = 'Nachricht löschen';
$string['deletemessageconfirm'] = 'Möchten Sie diese Nachricht wirklich löschen?';
$string['deletetemplate'] = 'Vorlage löschen';
$string['deletetemplateconfirm'] = 'Möchten Sie diese Vorlage wirklich löschen?';
$string['doing'] = 'In Arbeit';
$string['done'] = 'Erledigt';
$string['due'] = 'Fällig';
$string['duedate'] = 'Fälligkeitsdatum';
$string['editboard'] = 'Board bearbeiten';
$string['editcard'] = 'Karte bearbeiten';
$string['editcolumn'] = 'Spalte bearbeiten';
$string['editdetails'] = 'Details bearbeiten';
$string['editing_this_card_is_not_allowed'] = 'Sie haben nicht das Recht, diese Karte zu bearbeiten';
$string['enablehistory'] = 'Verlauf aktivieren';
$string['enablehistory_help'] = 'Aktiviert die Speicherung des Bearbeitungsverlaufs der Karten im Board (z.B. wann eine Karte verschoben / umbenannt oder abgeschlossen wurde).';
$string['enablehistorydescription'] = 'Wenn diese Option aktiv ist, ist der Änderungsverlauf in den Boards verfügbar.';
$string['groupboard'] = 'Gruppen-Board der Gruppe "{$a}"';
$string['hidehidden'] = 'Verborgene Karten nicht mehr anzeigen';
$string['history'] = 'Verlauf';
$string['history_card_added'] = '{$a->username} hat die Karte "{$a->title}" zur Spalte "{$a->columnname}" hinzugefügt';
$string['history_card_assigned'] = '{$a->username} hat die Karte an {$a->affectedusername} zugewiesen';
$string['history_card_completed'] = '{$a->username} hat die Karte abgeschlossen';
$string['history_card_deleted'] = '{$a->username} hat die Karte aus der Spalte "{$a->columnname}" gelöscht';
$string['history_card_moved'] = '{$a->username} hat die Karte in die Spalte "{$a->columnname}" verschoben';
$string['history_card_reopened'] = '{$a->username} hat die Karte erneut geöffnet';
$string['history_card_updated'] = '{$a->username} hat den Titel in "{$a->title}" geändert';
$string['history_card_unassigned'] = '{$a->username} hat die Zuweisung an {$a->affectedusername} aufgehoben';
$string['history_discussion_added'] = '{$a->username} hat eine Nachricht zur Diskussion hinzugefügt';
$string['history_discussion_deleted'] = '{$a->username} hat eine Nachricht aus der Diskussion entfernt';
$string['kanban:addcard'] = 'Eine Karte zu einem Board hinzufügen';
$string['kanban:addinstance'] = 'Eim Board hinzufügen';
$string['kanban:assignothers'] = 'Anderen eine Karte zuweisen';
$string['kanban:assignself'] = 'Sich selbst eine Karte zuweisen';
$string['kanban:editallboards'] = 'Alle Boards bearbeiten';
$string['kanban:manageboard'] = 'Das Board verwalten (Vorlagen speichern, das Board löschen)';
$string['kanban:manageallcards'] = 'Alle Karten auf dem Board bearbeiten / verschieben';
$string['kanban:manageassignedcards'] = 'Karten bearbeiten / verschieben, denen man selbst zugewiesen ist';
$string['kanban:managecolumns'] = 'Die Spalten auf dem Board bearbeiten';
$string['kanban:view'] = 'Ein Board anzeigen';
$string['kanban:viewallboards'] = 'Alle Boards anzeigen';
$string['kanban:viewhistory'] = 'Den Verlauf des Boards anzeigen';
$string['liveupdatetime'] = 'Intervall für die automatische Aktualisierung in Sekunden';
$string['liveupdatetimedescription'] = 'Die Boards werden nach diesem Intervall nach Aktualisierungen suchen. Der Wert 0 deaktiviert die automatische Aktualisierung.';
$string['loading'] = 'Kanban-Board wird geladen';
$string['loadingdiscussion'] = 'Diskussion wird geladen';
$string['lock'] = 'Sperren';
$string['lockboardcolumns'] = 'Alle Spalten sperren';
$string['message_assigned_fullmessage'] = 'Karte "{$a->title}" im Board "{$a->boardname}" wurde Ihnen von {$a->username} zugewiesen';
$string['message_assigned_smallmessage'] = 'Karte "{$a->title}" wurde Ihnen zugewiesen';
$string['message_closed_fullmessage'] = 'Karte "{$a->title}" wurde von {$a->username} als fertig markiert.';
$string['message_closed_smallmessage'] = 'Karte "{$a->title}" wurde als fertig markiert';
$string['message_discussion_fullmessage'] = 'Es gibt eine neue Nachricht in der Diskussion für die Karte "{$a->title}" im Board "{$a->boardname}":
{$a->username}
{$a->content}';
$string['message_discussion_smallmessage'] = 'Karte "{$a->title}" wurde diskutiert';
$string['message_due_fullmessage'] = 'Karte "{$a->title}" im Board "{$a->boardname}" ist fällig am {$a->duedate}';
$string['message_due_smallmessage'] = 'Karte "{$a->title}" ist fällig';
$string['message_moved_fullmessage'] = 'Karte "{$a->title}" wurde von {$a->username} in die Spalte "{$a->columnname}" verschoben.';
$string['message_moved_smallmessage'] = 'Karte "{$a->title}" wurde verschoben';
$string['message_reopened_fullmessage'] = 'Karte "{$a->title}" im Board "{$a->boardname}" wurde von {$a->username} wieder geöffnet.';
$string['message_reopened_smallmessage'] = 'Karte "{$a->title}" wurde wieder geöffnet.';
$string['message_unassigned_fullmessage'] = 'Karte "{$a->title}" im Board "{$a->boardname}" wurde Ihnen von {$a->username} entzogen.';
$string['message_unassigned_smallmessage'] = 'Karte "{$a->title}" wurde Ihnen entzogen';
$string['messageprovider:assigned'] = 'Karte zugewiesen / Zuweisung entfernt';
$string['messageprovider:closed'] = 'Karte abgeschlossen / wieder geöffnet';
$string['messageprovider:due'] = 'Karte fällig';
$string['messageprovider:discussion'] = 'Karte diskutiert';
$string['messageprovider:moved'] = 'Karte verschoben';
$string['modulename'] = 'Kanban-Board';
$string['modulenameplural'] = 'Kanban-Boards';
$string['moveaftercard'] = 'Verschieben hinter';
$string['movecard'] = 'Karte verschieben';
$string['movecolumn'] = 'Spalte verschieben';
$string['myuserboard'] = 'Mein persönliches Board';
$string['name'] = 'Name des Boards';
$string['name_help'] = 'Dieser Name wird in der Kursübersicht und als Titel des Boards sichtbar sein.';
$string['newcard'] = 'Neue Karte';
$string['newcolumn'] = 'Neue Spalte';
$string['nogroupavailable'] = 'Keine Gruppe verfügbar';
$string['nouser'] = 'Kein/e Nutzer/in';
$string['nouserboards'] = 'Keine persönlichen Boards für die Nutzer/innen';
$string['pluginadministration'] = 'Kanban-Administration';
$string['pluginname'] = 'Kanban-Board';
$string['pushcard'] = 'Karte auf alle Boards kopieren';
$string['pushcardconfirm'] = 'Diese Karte wird auf alle Boards (inkl. Vorlagen) innerhalb dieser Kanban-Aktivität kopiert. Bereits vorhandene Kopien werden ersetzt.';
$string['reminderdate'] = 'Zeitpunkt für die Erinnerung';
$string['remindertask'] = 'Erinnerungsnachrichten verschicken';
$string['reset_kanban'] = 'Gemeinsames Board zurücksetzen';
$string['reset_group'] = 'Persönliche Boards zurücksetzen';
$string['reset_personal'] = 'Gruppen-Boards zurücksetzen';
$string['saveastemplate'] = 'Als Vorlage speichern';
$string['saveastemplateconfirm'] = 'Sind Sie sicher, dass Sie dieses Board als Vorlage speichern wollen? Es wird die derzeitige Vorlage ersetzen (falls vorhanden).';
$string['senddiscussion'] = 'Diskussionsbeitrag absenden';
$string['showattachment'] = 'Anhänge anzeigen';
$string['showboard'] = 'Gemeinsames Board anzeigen';
$string['showdescription'] = 'Beschreibung anzeigen';
$string['showdiscussion'] = 'Diskussion anzeigen';
$string['showhidden'] = 'Verborgene Karten anzeigen';
$string['showtemplate'] = 'Vorlage anzeigen';
$string['startdiscussion'] = 'Diskussion beginnen';
$string['template'] = 'Vorlage';
$string['toboard'] = 'Board "{$a->boardname}"';
$string['todo'] = 'Zu erledigen';
$string['topofcolumn'] = 'Beginn der Spalte';
$string['unassign'] = 'Zuweisung aufheben';
$string['unassignme'] = 'Mir nicht mehr zuweisen';
$string['uncomplete'] = 'Erneut öffnen';
$string['unlock'] = 'Entsperren';
$string['unlockboardcolumns'] = 'Alle Spalten entsperren';
$string['userboard'] = 'Persönliches Board von {$a}';
$string['userboards'] = 'Persönliche Boards für die Nutzer/innen';
$string['userboards_help'] = 'Aktiviert persönliche Boards für die Teilnehmenden (nur für diese selbst und die Trainer/innen sichtbar).';
$string['userboardsenabled'] = 'Persönliche Boards für die Nutzer/innen aktiviert';
$string['userboardsonly'] = 'Ausschließlich persönliche Boards für die Nutzer/innen';
