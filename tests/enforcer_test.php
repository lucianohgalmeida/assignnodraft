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
 * Unit tests for the local_assignnodraft plugin.
 *
 * @package    local_assignnodraft
 * @copyright  2026 onwards
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assignnodraft;


/**
 * Tests for the enforcer and observer.
 *
 * @covers \local_assignnodraft\enforcer
 * @covers \local_assignnodraft\observer
 */
final class enforcer_test extends \advanced_testcase {
    /**
     * Create an assignment with draft mode on and online text enabled.
     *
     * @param int $courseid
     * @return \stdClass module record (has ->id and ->cmid)
     */
    private function make_assign($courseid) {
        return $this->getDataGenerator()->create_module('assign', [
            'course'                          => $courseid,
            'submissiondrafts'                => 1,
            'assignsubmission_onlinetext_enabled' => 1,
        ]);
    }

    /**
     * Insert a latest draft submission for a user.
     *
     * @param int $assignid
     * @param int $userid
     */
    private function make_draft($assignid, $userid) {
        global $DB;
        $now = time();
        $DB->insert_record('assign_submission', (object) [
            'assignment'   => $assignid,
            'userid'       => $userid,
            'status'       => 'draft',
            'latest'       => 1,
            'attemptnumber' => 0,
            'groupid'      => 0,
            'timecreated'  => $now,
            'timemodified' => $now,
        ]);
    }

    public function test_apply_course_scope_sets_no_draft_and_promotes(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->redirectMessages();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->make_assign($course->id);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->make_draft($assign->id, $student->id);

        $result = enforcer::apply(enforcer::SCOPE_COURSE, $course->id, false, true);

        $this->assertEquals(0, (int) $DB->get_field('assign', 'submissiondrafts', ['id' => $assign->id]));
        $this->assertEquals(1, $result->assignschanged);
        $this->assertEquals(1, $result->promoted);
        $this->assertEquals(0, $result->errors);
        $status = $DB->get_field(
            'assign_submission',
            'status',
            ['assignment' => $assign->id, 'userid' => $student->id, 'latest' => 1]
        );
        $this->assertEquals('submitted', $status);
    }

    public function test_promote_respects_cutoff_unless_forced(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->redirectMessages();

        // Assignment whose submission window is already closed.
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course'                              => $course->id,
            'submissiondrafts'                    => 1,
            'assignsubmission_onlinetext_enabled' => 1,
            'duedate'                             => time() - (2 * DAYSECS),
            'cutoffdate'                          => time() - DAYSECS,
        ]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->make_draft($assign->id, $student->id);

        // Without the force flag: the closed draft stays draft.
        $r1 = enforcer::apply(enforcer::SCOPE_COURSE, $course->id, false, true, false);
        $this->assertEquals(0, $r1->promoted);
        $this->assertEquals('draft', $DB->get_field(
            'assign_submission',
            'status',
            ['assignment' => $assign->id, 'userid' => $student->id, 'latest' => 1]
        ));

        // With the force flag: the closed draft is promoted.
        $r2 = enforcer::apply(enforcer::SCOPE_COURSE, $course->id, false, true, true);
        $this->assertEquals(1, $r2->promoted);
        $this->assertEquals('submitted', $DB->get_field(
            'assign_submission',
            'status',
            ['assignment' => $assign->id, 'userid' => $student->id, 'latest' => 1]
        ));
    }

    public function test_category_scope_with_subcats_includes_descendants(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $parent = $this->getDataGenerator()->create_category();
        $child = $this->getDataGenerator()->create_category(['parent' => $parent->id]);
        $course = $this->getDataGenerator()->create_course(['category' => $child->id]);
        $assign = $this->make_assign($course->id);

        // With subcategories: descendant course is affected.
        enforcer::apply(enforcer::SCOPE_CATEGORY, $parent->id, true, false);
        $this->assertEquals(0, (int) $DB->get_field('assign', 'submissiondrafts', ['id' => $assign->id]));
    }

    public function test_category_scope_without_subcats_skips_descendants(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $parent = $this->getDataGenerator()->create_category();
        $child = $this->getDataGenerator()->create_category(['parent' => $parent->id]);
        $course = $this->getDataGenerator()->create_course(['category' => $child->id]);
        $assign = $this->make_assign($course->id);

        // Without subcategories: a course in a child category must NOT be touched.
        enforcer::apply(enforcer::SCOPE_CATEGORY, $parent->id, false, false);
        $this->assertEquals(1, (int) $DB->get_field('assign', 'submissiondrafts', ['id' => $assign->id]));
    }

    public function test_observer_forces_no_draft_in_enforced_scope(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        enforcer::save_scope(enforcer::SCOPE_COURSE, $course->id, false, true);

        // Creating an assignment with draft mode on fires course_module_created;
        // the observer must flip it off.
        $assign = $this->make_assign($course->id);
        $this->assertEquals(0, (int) $DB->get_field('assign', 'submissiondrafts', ['id' => $assign->id]));
    }

    public function test_observer_ignores_out_of_scope_course(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $enforced = $this->getDataGenerator()->create_course();
        enforcer::save_scope(enforcer::SCOPE_COURSE, $enforced->id, false, true);

        $other = $this->getDataGenerator()->create_course();
        $assign = $this->make_assign($other->id);
        // Not in scope: keeps its draft mode.
        $this->assertEquals(1, (int) $DB->get_field('assign', 'submissiondrafts', ['id' => $assign->id]));
    }

    public function test_remove_scope_stops_enforcement(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $id = enforcer::save_scope(enforcer::SCOPE_COURSE, $course->id, false, true);
        enforcer::remove_scope($id);

        $assign = $this->make_assign($course->id);
        // No active scope: observer must not touch it.
        $this->assertEquals(1, (int) $DB->get_field('assign', 'submissiondrafts', ['id' => $assign->id]));
    }
}
