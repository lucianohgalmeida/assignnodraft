# Assignment: no draft mode (local_assignnodraft)

A Moodle admin tool that forces assignments to skip **draft mode** — the
*"Require students to click the submit button"* setting (`submissiondrafts`) —
across a whole category or a single course, in one action.

Students and teachers routinely forget the extra "Submit assignment" click, so
submissions get stuck in draft and never reach the teacher as final. This plugin
fixes that in bulk and keeps it fixed.

## What it does

For a chosen scope (a **category**, optionally including subcategories, or a
single **course**):

1. Turns **draft mode off** on every assignment (`submissiondrafts = 0`), so
   saving a submission makes it final immediately.
2. **Promotes stuck drafts** to final submissions using the standard mod_assign
   API — this fires the submission event, runs submission plugins, updates
   activity completion and recalculates grades, so teachers see them as real
   submissions.
3. **Keeps it enforced**: if a teacher later re-enables draft mode on an
   assignment inside a locked scope, an event observer switches it back off.

## Usage

Site administration → Plugins → Local plugins → **Assignment no-draft**.

1. Pick a category (with or without subcategories) or a course.
2. Review the preview: how many assignments are affected, how many still use
   draft mode, how many submissions are stuck in draft.
3. Apply. Locked scopes are listed and can be removed at any time (removing a
   scope stops future enforcement; it does not revert assignments already
   changed).

## Requirements

- Moodle 4.1+ (tested on 4.5).

## Notes

- Submissions already past their cut-off date are left untouched by the promote
  step (the assignment API will not submit a closed submission).
- Removing an enforced scope only stops automatic re-enforcement; assignments
  keep their current setting.

## Capability

- `local/assignnodraft:manage` (granted to Manager by default).

## License

GNU GPL v3 or later.
