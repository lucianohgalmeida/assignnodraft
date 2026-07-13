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
 * English strings for the local_assignnodraft plugin.
 *
 * @package    local_assignnodraft
 * @copyright  2026 onwards
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['applied'] = 'Done.';
$string['assignnodraft:manage'] = 'Manage assignment no-draft enforcement';
$string['assignsaffected'] = 'Assignments affected: {$a}';
$string['assignswithdraftmode'] = 'Currently using draft mode: {$a}';
$string['back'] = 'Back';
$string['category'] = 'Category';
$string['colassign'] = 'Assignment';
$string['colcourse'] = 'Course';
$string['coldraftmode'] = 'Draft mode';
$string['coldrafts'] = 'Draft submissions';
$string['confirmapply'] = 'Apply';
$string['course'] = 'Course';
$string['draftstopromote'] = 'Submissions stuck in draft: {$a}';
$string['errornoscope'] = 'Choose a category or a course.';
$string['hiddennoaction'] = 'Assignments already final with no draft submissions (hidden): {$a}';
$string['includesubcats'] = 'Include subcategories';
$string['includesubcats_help'] = 'Also affect assignments in every descendant category, at any depth.';
$string['intro'] = 'Turn off "Require students to click the submit button" for every assignment in a category or a single course, promote submissions that are stuck in draft to final, and keep it enforced so it cannot be re-enabled.';
$string['lockcontinuous'] = 'Keep enforced (lock)';
$string['lockcontinuous_help'] = 'Remember this scope. If a teacher re-enables draft mode on an assignment inside it, it is automatically switched back off.';
$string['lockedscopes'] = 'Enforced scopes';
$string['manage'] = 'Assignment no-draft';
$string['manageheading'] = 'Force assignments to skip draft mode';
$string['next'] = 'Preview changes';
$string['nolockedscopes'] = 'No scopes are currently enforced.';
$string['nothingtodo'] = 'Nothing to change in this scope: no assignments use draft mode and there are no draft submissions.';
$string['off'] = 'Off';
$string['on'] = 'On';
$string['pluginname'] = 'Assignment: no draft mode';



$string['previewheading'] = 'Preview';
$string['previewscope'] = 'Scope: {$a}';
$string['privacy:metadata'] = 'The Assignment no-draft plugin does not store any personal data.';
$string['promote'] = 'Promote drafts to final submission';
$string['promote_help'] = 'Submissions stuck in draft are marked as submitted so teachers see them as final. This triggers the standard submission event and recalculates grades. It cannot be undone.';
$string['promoteclosed'] = 'Also promote drafts past the cut-off date';
$string['promoteclosed_help'] = 'By default a draft whose submission window has already closed is left alone. Enable this to submit those too, so a student who missed the deadline while still in draft is not left out.';
$string['removescope'] = 'Remove';
$string['removescopeconfirm'] = 'Stop enforcing no-draft for this scope? Existing assignments keep their current setting; only automatic re-enforcement stops.';
$string['reportmodenote'] = 'Report only: nothing was changed. This is the full inventory of the scope.';
$string['reportonly'] = 'Only map (report, do not change anything)';
$string['reportonly_help'] = 'Produce a read-only inventory of the scope: every assignment, whether it uses draft mode and how many submissions are stuck in draft. No setting is changed and no submission is promoted.';
$string['resultassigns'] = 'Assignments switched to no-draft: {$a}';
$string['resulterrors'] = 'Assignments skipped due to errors: {$a}';
$string['resultlocked'] = 'Scope locked and will stay enforced.';
$string['resultpromoted'] = 'Draft submissions promoted to final: {$a}';
$string['scope'] = 'Scope';
$string['scopelabelcategory'] = 'Category "{$a->name}"{$a->sub}';
$string['scopelabelcategorysub'] = ' and subcategories';
$string['scopelabelcourse'] = 'Course "{$a}"';
$string['scoperemoved'] = 'Scope removed from enforcement.';
$string['scopetype'] = 'Apply to';
$string['scopetype_category'] = 'Category';
$string['scopetype_course'] = 'Course';
