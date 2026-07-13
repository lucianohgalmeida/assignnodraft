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
 * Admin UI: choose a scope, preview and apply the no-draft change.
 *
 * @package    local_assignnodraft
 * @copyright  2026 onwards
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_assignnodraft\enforcer;
use local_assignnodraft\form\scope_form;

admin_externalpage_setup('local_assignnodraft_manage');
$context = context_system::instance();
require_capability('local/assignnodraft:manage', $context);

$action = optional_param('action', '', PARAM_ALPHA);
$baseurl = new moodle_url('/local/assignnodraft/manage.php');
$PAGE->set_url($baseurl);

// Remove an enforced scope.
if ($action === 'remove') {
    require_sesskey();
    $id = required_param('id', PARAM_INT);
    enforcer::remove_scope($id);
    redirect(
        $baseurl,
        get_string('scoperemoved', 'local_assignnodraft'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$form = new scope_form($baseurl->out(false));

// Apply (confirmed).
if ($action === 'apply' && confirm_sesskey()) {
    $scopetype = required_param('scopetype', PARAM_ALPHA);
    $instanceid = required_param('instanceid', PARAM_INT);
    $includesubcats = optional_param('includesubcats', 0, PARAM_BOOL);
    $promote = optional_param('promote', 0, PARAM_BOOL);
    $promoteclosed = optional_param('promoteclosed', 0, PARAM_BOOL);
    $lock = optional_param('lockcontinuous', 0, PARAM_BOOL);

    $result = enforcer::apply($scopetype, $instanceid, $includesubcats, $promote, $promoteclosed);
    if ($lock) {
        enforcer::save_scope($scopetype, $instanceid, $includesubcats, $promote);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('applied', 'local_assignnodraft'));
    $items = [
        get_string('resultassigns', 'local_assignnodraft', $result->assignschanged),
        get_string('resultpromoted', 'local_assignnodraft', $result->promoted),
    ];
    if ($result->errors > 0) {
        $items[] = get_string('resulterrors', 'local_assignnodraft', $result->errors);
    }
    if ($lock) {
        $items[] = get_string('resultlocked', 'local_assignnodraft');
    }
    echo html_writer::alist($items);
    echo local_assignnodraft_render_scopes();
    echo $OUTPUT->continue_button($baseurl);
    echo $OUTPUT->footer();
    exit;
}

// Preview (form submitted and valid).
if ($data = $form->get_data()) {
    $scopetype = $data->scopetype;
    if ($scopetype === enforcer::SCOPE_CATEGORY) {
        $instanceid = (int) $data->category;
        $includesubcats = !empty($data->includesubcats);
    } else {
        $instanceid = (int) $data->course;
        $includesubcats = false;
    }
    $reportonly = !empty($data->reportonly);
    $promote = !$reportonly && !empty($data->promote);
    $promoteclosed = !$reportonly && !empty($data->promoteclosed);
    $lock = !$reportonly && !empty($data->lockcontinuous);

    $assigns = enforcer::get_assigns_in_scope($scopetype, $instanceid, $includesubcats);

    $withdraftmode = 0;
    $totaldrafts = 0;
    $noaction = 0;
    $coursenames = [];
    $table = new html_table();
    $table->head = [
        get_string('colcourse', 'local_assignnodraft'),
        get_string('colassign', 'local_assignnodraft'),
        get_string('coldraftmode', 'local_assignnodraft'),
        get_string('coldrafts', 'local_assignnodraft'),
    ];
    foreach ($assigns as $a) {
        if (!isset($coursenames[$a->course])) {
            $coursenames[$a->course] = $DB->get_field('course', 'fullname', ['id' => $a->course]);
        }
        $drafts = enforcer::count_drafts($a->id);
        $totaldrafts += $drafts;
        $draftmode = ((int) $a->submissiondrafts !== 0);
        if ($draftmode) {
            $withdraftmode++;
        }
        // In apply mode, only list assignments that need action (draft mode on, or
        // drafts received); the rest are counted but hidden. Report mode lists all.
        if (!$reportonly && !$draftmode && $drafts === 0) {
            $noaction++;
            continue;
        }
        $table->data[] = [
            format_string($coursenames[$a->course]),
            format_string($a->name),
            $draftmode ? get_string('on', 'local_assignnodraft') : get_string('off', 'local_assignnodraft'),
            $drafts > 0 ? $drafts : '-',
        ];
    }

    $scopelabelobj = (object) [
        'scopetype'      => $scopetype,
        'instanceid'     => $instanceid,
        'includesubcats' => $includesubcats ? 1 : 0,
    ];

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('previewheading', 'local_assignnodraft'));
    echo html_writer::tag('p', get_string(
        'previewscope',
        'local_assignnodraft',
        enforcer::scope_label($scopelabelobj)
    ));
    echo html_writer::alist([
        get_string('assignsaffected', 'local_assignnodraft', count($assigns)),
        get_string('assignswithdraftmode', 'local_assignnodraft', $withdraftmode),
        get_string('draftstopromote', 'local_assignnodraft', $totaldrafts),
    ]);

    if ($noaction > 0) {
        echo html_writer::tag(
            'p',
            get_string('hiddennoaction', 'local_assignnodraft', $noaction),
            ['class' => 'text-muted']
        );
    }

    // Report-only mode: full inventory, nothing changed, no apply button.
    if ($reportonly) {
        echo $OUTPUT->notification(
            get_string('reportmodenote', 'local_assignnodraft'),
            \core\output\notification::NOTIFY_INFO
        );
        if (!empty($table->data)) {
            echo html_writer::table($table);
        }
        echo $OUTPUT->continue_button($baseurl);
        echo $OUTPUT->footer();
        exit;
    }

    if ($withdraftmode === 0 && $totaldrafts === 0 && !$lock) {
        echo $OUTPUT->notification(
            get_string('nothingtodo', 'local_assignnodraft'),
            \core\output\notification::NOTIFY_INFO
        );
        echo $OUTPUT->continue_button($baseurl);
        echo $OUTPUT->footer();
        exit;
    }

    if (!empty($table->data)) {
        echo html_writer::table($table);
    }

    // Confirm form (carries the chosen scope + options).
    $confirmurl = new moodle_url('/local/assignnodraft/manage.php');
    $hidden = [
        'action'         => 'apply',
        'sesskey'        => sesskey(),
        'scopetype'      => $scopetype,
        'instanceid'     => $instanceid,
        'includesubcats' => $includesubcats ? 1 : 0,
        'promote'        => $promote ? 1 : 0,
        'promoteclosed'  => $promoteclosed ? 1 : 0,
        'lockcontinuous' => $lock ? 1 : 0,
    ];
    $formhtml = html_writer::start_tag('form', ['method' => 'post', 'action' => $confirmurl->out(false)]);
    foreach ($hidden as $name => $value) {
        $formhtml .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
    }
    $formhtml .= html_writer::empty_tag('input', [
        'type'  => 'submit',
        'class' => 'btn btn-primary',
        'value' => get_string('confirmapply', 'local_assignnodraft'),
    ]);
    $formhtml .= ' ' . html_writer::link(
        $baseurl,
        get_string('back', 'local_assignnodraft'),
        ['class' => 'btn btn-secondary']
    );
    $formhtml .= html_writer::end_tag('form');
    echo $formhtml;

    echo $OUTPUT->footer();
    exit;
}

// Default: form + enforced scopes list.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageheading', 'local_assignnodraft'));
echo html_writer::tag('p', get_string('intro', 'local_assignnodraft'));
$form->display();
echo local_assignnodraft_render_scopes();
echo $OUTPUT->footer();

/**
 * Render the list of currently enforced scopes with remove links.
 *
 * @return string HTML
 */
function local_assignnodraft_render_scopes() {
    global $OUTPUT;
    $baseurl = new moodle_url('/local/assignnodraft/manage.php');
    $scopes = enforcer::get_scopes();
    $html = $OUTPUT->heading(get_string('lockedscopes', 'local_assignnodraft'), 3);
    if (!$scopes) {
        return $html . html_writer::tag('p', get_string('nolockedscopes', 'local_assignnodraft'));
    }
    $table = new html_table();
    $table->head = [get_string('scope', 'local_assignnodraft'), ''];
    foreach ($scopes as $scope) {
        $removeurl = new moodle_url($baseurl, [
            'action'  => 'remove',
            'id'      => $scope->id,
            'sesskey' => sesskey(),
        ]);
        $removelink = $OUTPUT->action_link(
            $removeurl,
            get_string('removescope', 'local_assignnodraft'),
            new confirm_action(get_string('removescopeconfirm', 'local_assignnodraft')),
            ['class' => 'btn btn-secondary btn-sm']
        );
        $table->data[] = [enforcer::scope_label($scope), $removelink];
    }
    return $html . html_writer::table($table);
}
