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
 * questions.php
 *
 * @package   mod_videomarker
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videomarker', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videomarker', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/videomarker:managequestions', $context);

$PAGE->set_url('/mod/videomarker/questions.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('managequestions', 'videomarker'));
$PAGE->set_heading($course->fullname);

$questions = array_values(\mod_videomarker\marker_manager::questions((int)$activity->id));

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($activity->name));
echo $OUTPUT->heading(get_string('managequestions', 'videomarker'), 3);
echo html_writer::div(
    html_writer::link(new moodle_url('/mod/videomarker/question.php',
        ['cmid' => $cm->id]), get_string('addquestion', 'videomarker'), ['class' => 'btn btn-primary']) . ' ' .
    html_writer::link(new moodle_url('/mod/videomarker/view.php',
        ['id' => $cm->id]), get_string('backtoactivity', 'videomarker'), ['class' => 'btn btn-secondary']),
    'mb-3'
);

if (!$questions) {
    echo $OUTPUT->notification(get_string('noquestions', 'videomarker'), 'info');
} else {
    $table = new html_table();
    $table->head = ['#', get_string('questiontext', 'videomarker'), get_string('questiontype', 'videomarker'),
        get_string('markers', 'videomarker'), get_string('points', 'videomarker'), get_string('actions')];
    foreach ($questions as $index => $question) {
        $targetcount = $DB->count_records('videomarker_targets', ['questionid' => $question->id]);
        $actions = [];
        $actions[] = html_writer::link(new moodle_url('/mod/videomarker/question.php', [
            'cmid' => $cm->id, 'qid' => $question->id,
        ]), get_string('edit'));
        if ($index > 0) {
            $actions[] = html_writer::link(new moodle_url('/mod/videomarker/question_action.php', [
                'id' => $cm->id, 'qid' => $question->id, 'action' => 'up', 'sesskey' => sesskey(),
            ]), get_string('moveup', 'videomarker'));
        }
        if ($index < count($questions) - 1) {
            $actions[] = html_writer::link(new moodle_url('/mod/videomarker/question_action.php', [
                'id' => $cm->id, 'qid' => $question->id, 'action' => 'down', 'sesskey' => sesskey(),
            ]), get_string('movedown', 'videomarker'));
        }
        $actions[] = html_writer::link(new moodle_url('/mod/videomarker/question_action.php', [
            'id' => $cm->id, 'qid' => $question->id, 'action' => 'delete', 'sesskey' => sesskey(),
        ]), get_string('delete'));
        $table->data[] = [
            $index + 1,
            format_text($question->questiontext, $question->questiontextformat, ['context' => $context]),
            get_string('questiontype' . $question->questiontype, 'videomarker'),
            $targetcount,
            format_float($question->points, 2),
            implode(' | ', $actions),
        ];
    }
    echo html_writer::table($table);
}
echo $OUTPUT->footer();
