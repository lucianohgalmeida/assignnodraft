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
 * Event observer for the local_assignnodraft plugin.
 *
 * @package    local_assignnodraft
 * @copyright  2026 onwards
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assignnodraft;


/**
 * Keeps draft mode off on assignments inside enforced scopes.
 */
class observer {
    /**
     * React to an assignment module being created or updated: if it lives in an
     * enforced scope and has draft mode on, switch it back off.
     *
     * @param \core\event\base $event course_module_created / course_module_updated
     */
    public static function course_module_changed(\core\event\base $event) {
        $other = $event->other;
        $modulename = is_array($other) && isset($other['modulename']) ? $other['modulename'] : '';
        if ($modulename !== 'assign') {
            return;
        }
        $courseid = (int) $event->courseid;
        if (!enforcer::course_in_any_active_scope($courseid)) {
            return;
        }
        $instanceid = is_array($other) && isset($other['instanceid']) ? (int) $other['instanceid'] : 0;
        if ($instanceid <= 0) {
            return;
        }
        enforcer::enforce_instance($instanceid, $courseid);
    }
}
