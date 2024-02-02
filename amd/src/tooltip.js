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

import $ from 'jquery';

/**
 * Show tooltip on click.
 *
 * @module      mod_kanban/tooltip
 * @copyright   2023-2024, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Show the tooltip for two seconds after click.
 * @param {*} id Id of the element
 */
export const addTooltipClickListener = function(id) {
    document.getElementById(id).addEventListener('click', function() {
        $('#' + id).tooltip('show');
        setTimeout(function() {
            $('#' + id).tooltip('hide');
        }, 2000);
    });
};
