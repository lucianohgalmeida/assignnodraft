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
 * Scope selection form for the local_assignnodraft plugin.
 *
 * @package    local_assignnodraft
 * @copyright  2026 onwards
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assignnodraft\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use local_assignnodraft\enforcer;

/**
 * Step 1: choose a category (optionally with subcategories) or a course, plus
 * the promote / lock options.
 */
class scope_form extends \moodleform {
    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        // What to apply to.
        $mform->addElement('select', 'scopetype', get_string('scopetype', 'local_assignnodraft'), [
            enforcer::SCOPE_CATEGORY => get_string('scopetype_category', 'local_assignnodraft'),
            enforcer::SCOPE_COURSE   => get_string('scopetype_course', 'local_assignnodraft'),
        ]);
        $mform->setDefault('scopetype', enforcer::SCOPE_CATEGORY);

        // Category picker.
        $categories = \core_course_category::make_categories_list();
        $mform->addElement(
            'autocomplete',
            'category',
            get_string('category', 'local_assignnodraft'),
            $categories,
            ['noselectionstring' => get_string('category', 'local_assignnodraft')]
        );
        $mform->hideIf('category', 'scopetype', 'neq', enforcer::SCOPE_CATEGORY);

        $mform->addElement(
            'advcheckbox',
            'includesubcats',
            get_string('includesubcats', 'local_assignnodraft')
        );
        $mform->setDefault('includesubcats', 1);
        $mform->addHelpButton('includesubcats', 'includesubcats', 'local_assignnodraft');
        $mform->hideIf('includesubcats', 'scopetype', 'neq', enforcer::SCOPE_CATEGORY);

        // Course picker.
        $mform->addElement(
            'course',
            'course',
            get_string('course', 'local_assignnodraft'),
            ['multiple' => false]
        );
        $mform->hideIf('course', 'scopetype', 'neq', enforcer::SCOPE_COURSE);

        // Report-only mode: just map the scope, change nothing.
        $mform->addElement('advcheckbox', 'reportonly', get_string('reportonly', 'local_assignnodraft'));
        $mform->setDefault('reportonly', 0);
        $mform->addHelpButton('reportonly', 'reportonly', 'local_assignnodraft');

        // Options (irrelevant in report-only mode, so hidden then).
        $mform->addElement('advcheckbox', 'promote', get_string('promote', 'local_assignnodraft'));
        $mform->setDefault('promote', 1);
        $mform->addHelpButton('promote', 'promote', 'local_assignnodraft');
        $mform->hideIf('promote', 'reportonly', 'checked');

        $mform->addElement('advcheckbox', 'promoteclosed', get_string('promoteclosed', 'local_assignnodraft'));
        $mform->setDefault('promoteclosed', 0);
        $mform->addHelpButton('promoteclosed', 'promoteclosed', 'local_assignnodraft');
        $mform->hideIf('promoteclosed', 'promote', 'notchecked');
        $mform->hideIf('promoteclosed', 'reportonly', 'checked');

        $mform->addElement('advcheckbox', 'lockcontinuous', get_string('lockcontinuous', 'local_assignnodraft'));
        $mform->setDefault('lockcontinuous', 1);
        $mform->addHelpButton('lockcontinuous', 'lockcontinuous', 'local_assignnodraft');
        $mform->hideIf('lockcontinuous', 'reportonly', 'checked');

        $this->add_action_buttons(false, get_string('next', 'local_assignnodraft'));
    }

    /**
     * Validate that a real scope target was chosen.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if ($data['scopetype'] === enforcer::SCOPE_CATEGORY) {
            if (empty($data['category'])) {
                $errors['category'] = get_string('errornoscope', 'local_assignnodraft');
            }
        } else {
            if (empty($data['course'])) {
                $errors['course'] = get_string('errornoscope', 'local_assignnodraft');
            }
        }
        return $errors;
    }
}
