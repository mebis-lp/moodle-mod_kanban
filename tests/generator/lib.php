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
 * mod_kanban data generator
 *
 * @package     mod_kanban
 * @copyright   2023-2024 ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_kanban_generator extends testing_module_generator {
    /**
     * Creates an instance of a kanban.
     *
     * @param array $record
     * @param array|null $options
     * @return stdClass kanban instance
     */
    public function create_instance($record = null, array $options = null): stdClass { // phpcs:ignore
        $record = (array) $record + [
                'name' => 'test kanban',
                'intro' => '',
                'introformat' => 1,
            ];

        return parent::create_instance($record, (array) $options);
    }
}
