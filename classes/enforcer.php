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
 * Core enforcement logic for the local_assignnodraft plugin.
 *
 * @package    local_assignnodraft
 * @copyright  2026 onwards
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assignnodraft;


/**
 * Resolves scopes to assignments, applies the no-draft change, promotes stuck
 * drafts and persists enforced scopes.
 */
class enforcer {
    /** @var string Course scope. */
    const SCOPE_COURSE = 'course';
    /** @var string Category scope. */
    const SCOPE_CATEGORY = 'category';

    /**
     * Course ids inside a scope.
     *
     * @param string $scopetype self::SCOPE_COURSE|self::SCOPE_CATEGORY
     * @param int $instanceid course id or category id
     * @param bool $includesubcats include descendant categories (category scope)
     * @return int[] course ids
     */
    public static function get_courses_in_scope($scopetype, $instanceid, $includesubcats) {
        global $DB;
        $instanceid = (int) $instanceid;

        if ($scopetype === self::SCOPE_COURSE) {
            return $DB->record_exists('course', ['id' => $instanceid]) ? [$instanceid] : [];
        }

        $catids = $includesubcats
            ? self::category_and_descendants($instanceid)
            : [$instanceid];
        if (empty($catids)) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED);
        $sql = "SELECT id FROM {course} WHERE category $insql";
        return array_map('intval', array_keys($DB->get_records_sql($sql, $params)));
    }

    /**
     * A category id plus all its descendant category ids.
     *
     * @param int $catid
     * @return int[]
     */
    public static function category_and_descendants($catid) {
        global $DB;
        $cat = $DB->get_record('course_categories', ['id' => $catid], 'id, path');
        if (!$cat) {
            return [];
        }
        $like = $DB->sql_like('path', ':path');
        $sql = "SELECT id FROM {course_categories} WHERE id = :catid OR $like";
        $params = ['catid' => (int) $catid, 'path' => $cat->path . '/%'];
        return array_map('intval', array_keys($DB->get_records_sql($sql, $params)));
    }

    /**
     * Assignment records inside a scope.
     *
     * @param string $scopetype
     * @param int $instanceid
     * @param bool $includesubcats
     * @return \stdClass[] rows with id, course, name, submissiondrafts
     */
    public static function get_assigns_in_scope($scopetype, $instanceid, $includesubcats) {
        global $DB;
        $courseids = self::get_courses_in_scope($scopetype, $instanceid, $includesubcats);
        if (empty($courseids)) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $sql = "SELECT a.id, a.course, a.name, a.submissiondrafts
                  FROM {assign} a
                 WHERE a.course $insql
              ORDER BY a.course ASC, a.name ASC";
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Count of latest draft submissions for an assignment.
     *
     * @param int $assignid
     * @return int
     */
    public static function count_drafts($assignid) {
        global $DB;
        return $DB->count_records_select(
            'assign_submission',
            'assignment = :a AND status = :s AND latest = 1',
            ['a' => (int) $assignid, 's' => ASSIGN_SUBMISSION_STATUS_DRAFT]
        );
    }

    /**
     * Apply the no-draft change to every assignment in a scope.
     *
     * @param string $scopetype
     * @param int $instanceid
     * @param bool $includesubcats
     * @param bool $promote also promote stuck drafts to submitted
     * @param bool $promoteclosed also promote drafts whose submission window is closed (past cut-off)
     * @return \stdClass {assignschanged, promoted, errors}
     */
    public static function apply($scopetype, $instanceid, $includesubcats, $promote, $promoteclosed = false) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $assigns = self::get_assigns_in_scope($scopetype, $instanceid, $includesubcats);
        $result = (object) ['assignschanged' => 0, 'promoted' => 0, 'errors' => 0];
        $affectedcourses = [];

        foreach ($assigns as $a) {
            try {
                if ((int) $a->submissiondrafts !== 0) {
                    self::set_no_draft($a->id);
                    $result->assignschanged++;
                    $affectedcourses[$a->course] = true;
                }
                if ($promote) {
                    $promoted = self::promote_drafts_for_assign($a->id, $promoteclosed);
                    if ($promoted > 0) {
                        $result->promoted += $promoted;
                        $affectedcourses[$a->course] = true;
                    }
                }
            } catch (\Throwable $e) {
                $result->errors++;
                debugging(
                    'local_assignnodraft: failed on assign ' . $a->id . ': ' . $e->getMessage(),
                    DEBUG_DEVELOPER
                );
            }
        }

        foreach (array_keys($affectedcourses) as $cid) {
            rebuild_course_cache($cid, true);
        }
        return $result;
    }

    /**
     * Turn draft mode off on a single assignment (no cache rebuild here).
     *
     * @param int $assignid
     */
    public static function set_no_draft($assignid) {
        global $DB;
        $DB->set_field('assign', 'submissiondrafts', 0, ['id' => (int) $assignid]);
    }

    /**
     * Promote every stuck draft of an assignment to a final submission using the
     * mod_assign API (fires events, notifications, completion and gradebook).
     *
     * By default submissions whose window is closed (past cut-off) are left
     * untouched, because the API refuses to submit them. Pass $promoteclosed to
     * force those too.
     *
     * @param int $assignid
     * @param bool $promoteclosed force-promote drafts even when the window is closed
     * @return int number of submissions promoted
     */
    public static function promote_drafts_for_assign($assignid, $promoteclosed = false) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $hasdrafts = $DB->record_exists_select(
            'assign_submission',
            'assignment = :a AND status = :s AND latest = 1',
            ['a' => (int) $assignid, 's' => ASSIGN_SUBMISSION_STATUS_DRAFT]
        );
        if (!$hasdrafts) {
            return 0;
        }

        [$course, $cm] = get_course_and_cm_from_instance($assignid, 'assign');
        $context = \context_module::instance($cm->id);
        $assign = new \assign($context, $cm, $course);
        $instance = $assign->get_instance();

        $count = 0;
        if (empty($instance->teamsubmission)) {
            $userids = $DB->get_fieldset_select(
                'assign_submission',
                'DISTINCT userid',
                'assignment = :a AND status = :s AND latest = 1 AND userid > 0',
                ['a' => (int) $assignid, 's' => ASSIGN_SUBMISSION_STATUS_DRAFT]
            );
            foreach ($userids as $userid) {
                if (self::submit_one($assign, $userid, $promoteclosed)) {
                    $count++;
                }
            }
        } else {
            $groupids = $DB->get_fieldset_select(
                'assign_submission',
                'DISTINCT groupid',
                'assignment = :a AND status = :s AND latest = 1 AND groupid > 0',
                ['a' => (int) $assignid, 's' => ASSIGN_SUBMISSION_STATUS_DRAFT]
            );
            foreach ($groupids as $groupid) {
                $members = $DB->get_records('groups_members', ['groupid' => $groupid], 'id ASC', 'userid', 0, 1);
                if (!$members) {
                    continue;
                }
                $member = reset($members);
                if (self::submit_one($assign, $member->userid, $promoteclosed)) {
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Submit one user's (or their group's) draft for grading.
     *
     * Tries the standard mod_assign API first. If that refuses because the
     * window is closed and $promoteclosed is set, force the same effect
     * (run submission plugins, mark submitted, fire the event, update grades).
     *
     * @param \assign $assign
     * @param int $userid
     * @param bool $promoteclosed
     * @return bool true if the submission became submitted
     */
    private static function submit_one($assign, $userid, $promoteclosed) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/assign/lib.php');

        $notices = [];
        if ($assign->submit_for_grading((object) ['userid' => $userid], $notices)) {
            return true;
        }
        if (!$promoteclosed) {
            return false;
        }

        $instance = $assign->get_instance();
        if (!empty($instance->teamsubmission)) {
            $submission = $assign->get_group_submission($userid, 0, false);
        } else {
            $submission = $assign->get_user_submission($userid, false);
        }
        if (!$submission || $submission->status === ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
            return false;
        }

        foreach ($assign->get_submission_plugins() as $plugin) {
            if ($plugin->is_enabled() && $plugin->is_visible()) {
                $plugin->submit_for_grading($submission);
            }
        }
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $submission->timemodified = time();
        $DB->update_record('assign_submission', $submission);

        \mod_assign\event\assessable_submitted::create_from_submission($assign, $submission, false)->trigger();

        // Sync the gradebook. assign_update_grades() reads cmidnumber off the instance
        // record and get_instance() omits it, so set it before the call.
        $instance->cmidnumber = $assign->get_course_module()->idnumber;
        assign_update_grades($instance, $userid);

        return true;
    }

    // Continuous enforcement (persisted scopes).
    /**
     * Store (or refresh) an enforced scope.
     *
     * @param string $scopetype
     * @param int $instanceid
     * @param bool $includesubcats
     * @param bool $promote
     * @return int scope record id
     */
    public static function save_scope($scopetype, $instanceid, $includesubcats, $promote) {
        global $DB, $USER;
        $now = time();
        $existing = $DB->get_record(
            'local_assignnodraft_scope',
            ['scopetype' => $scopetype, 'instanceid' => (int) $instanceid]
        );
        $rec = (object) [
            'scopetype'      => $scopetype,
            'instanceid'     => (int) $instanceid,
            'includesubcats' => $includesubcats ? 1 : 0,
            'promote'        => $promote ? 1 : 0,
            'timemodified'   => $now,
            'usermodified'   => $USER->id,
        ];
        if ($existing) {
            $rec->id = $existing->id;
            $DB->update_record('local_assignnodraft_scope', $rec);
            return (int) $existing->id;
        }
        $rec->timecreated = $now;
        return (int) $DB->insert_record('local_assignnodraft_scope', $rec);
    }

    /**
     * All enforced scopes.
     *
     * @return \stdClass[]
     */
    public static function get_scopes() {
        global $DB;
        return $DB->get_records('local_assignnodraft_scope', null, 'timecreated ASC');
    }

    /**
     * Remove an enforced scope (does not revert assignments already changed).
     *
     * @param int $id
     */
    public static function remove_scope($id) {
        global $DB;
        $DB->delete_records('local_assignnodraft_scope', ['id' => (int) $id]);
    }

    /**
     * Whether a course falls inside any enforced scope.
     *
     * @param int $courseid
     * @return bool
     */
    public static function course_in_any_active_scope($courseid) {
        global $DB;
        $scopes = $DB->get_records('local_assignnodraft_scope');
        if (!$scopes) {
            return false;
        }
        $course = $DB->get_record('course', ['id' => $courseid], 'id, category');
        if (!$course) {
            return false;
        }
        foreach ($scopes as $s) {
            if ($s->scopetype === self::SCOPE_COURSE && (int) $s->instanceid === (int) $courseid) {
                return true;
            }
            if ($s->scopetype === self::SCOPE_CATEGORY) {
                if (!empty($s->includesubcats)) {
                    if (self::category_contains_category($s->instanceid, $course->category)) {
                        return true;
                    }
                } else if ((int) $course->category === (int) $s->instanceid) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * True if $catid equals $ancestorid or is a descendant of it.
     *
     * @param int $ancestorid
     * @param int $catid
     * @return bool
     */
    public static function category_contains_category($ancestorid, $catid) {
        global $DB;
        if ((int) $ancestorid === (int) $catid) {
            return true;
        }
        $anc = $DB->get_record('course_categories', ['id' => $ancestorid], 'id, path');
        $cat = $DB->get_record('course_categories', ['id' => $catid], 'id, path');
        if (!$anc || !$cat) {
            return false;
        }
        return strpos($cat->path, $anc->path . '/') === 0;
    }

    /**
     * Force draft mode off on one assignment instance (used by the observer).
     *
     * @param int $assigninstanceid
     * @param int $courseid
     * @return bool true if a change was made
     */
    public static function enforce_instance($assigninstanceid, $courseid) {
        global $DB;
        $sd = $DB->get_field('assign', 'submissiondrafts', ['id' => (int) $assigninstanceid]);
        if ($sd === false || (int) $sd === 0) {
            return false;
        }
        $DB->set_field('assign', 'submissiondrafts', 0, ['id' => (int) $assigninstanceid]);
        rebuild_course_cache($courseid, true);
        return true;
    }

    /**
     * Human-readable label for a scope record.
     *
     * @param \stdClass $scope row from local_assignnodraft_scope
     * @return string
     */
    public static function scope_label($scope) {
        global $DB;
        if ($scope->scopetype === self::SCOPE_COURSE) {
            $name = $DB->get_field('course', 'fullname', ['id' => $scope->instanceid]);
            return get_string('scopelabelcourse', 'local_assignnodraft', $name !== false ? $name : $scope->instanceid);
        }
        $name = $DB->get_field('course_categories', 'name', ['id' => $scope->instanceid]);
        $a = (object) [
            'name' => $name !== false ? $name : $scope->instanceid,
            'sub'  => !empty($scope->includesubcats) ? get_string('scopelabelcategorysub', 'local_assignnodraft') : '',
        ];
        return get_string('scopelabelcategory', 'local_assignnodraft', $a);
    }
}
