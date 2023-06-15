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
 * Helper for formatting updates
 *
 * @package    mod_kanban
 * @copyright  2023 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_kanban;

/**
 * Helper for formatting updates
 *
 * @package    mod_kanban
 * @copyright  2023 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class updateformatter {
    /**
     * Stored update messages.
     *
     * @var array
     */
    private $updates = [];
    /**
     * Put a new value.
     * @param string $name Name of the value to update
     * @param array $data Fields to update, must contain 'id' field
     */
    public function put(string $name, array $data) {
        $this->updates[] = ['name' => $name, 'action' => 'put', 'fields' => $data];
    }

    /**
     * Delete a value.
     * @param string $name Name of the value to update
     * @param array $data Fields to identify item, must contain 'id' field
     */
    public function delete(string $name, array $data) {
        $this->updates[] = ['name' => $name, 'action' => 'delete', 'fields' => $data];
    }

    /**
     * Return update JSON.
     * @return string JSON encoded update string
     */
    public function format() {
        return json_encode($this->updates);
    }
}
