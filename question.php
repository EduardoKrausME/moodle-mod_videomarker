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
 * question.php
 *
 * @package   mod_videomarker
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once("{$CFG->libdir}/formslib.php");

$cmid = required_param('cmid', PARAM_INT);
$qid = optional_param('qid', 0, PARAM_INT);
$cm = get_coursemodule_from_id('videomarker', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videomarker', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/videomarker:managequestions', $context);

$PAGE->set_url('/mod/videomarker/question.php', ['cmid' => $cmid, 'qid' => $qid]);
$PAGE->set_title($qid ? get_string('editquestion', 'videomarker') : get_string('addquestion', 'videomarker'));
$PAGE->set_heading($course->fullname);

$form = new \mod_videomarker\form\question_form(null, ['cmid' => $cmid]);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/videomarker/questions.php', ['id' => $cmid]));
}
if ($data = $form->get_data()) {
    $editor = $data->questiontext_editor;
    $data->questiontext = (string)$editor['text'];
    $data->questiontextformat = (int)$editor['format'];
    \mod_videomarker\marker_manager::save_question($data, (int)$activity->id);
    redirect(new moodle_url('/mod/videomarker/questions.php', ['id' => $cmid]), get_string('changessaved'));
}

if ($qid) {
    $question = $DB->get_record('videomarker_questions', [
        'id' => $qid,
        'videomarkerid' => $activity->id,
    ], '*', MUST_EXIST);
    $question->cmid = $cmid;
    $question->questiontext_editor = [
        'text' => $question->questiontext,
        'format' => $question->questiontextformat,
        'itemid' => 0,
    ];
    $question->targets = \mod_videomarker\marker_manager::targets_to_text(
        \mod_videomarker\marker_manager::targets((int)$question->id)
    );
    $form->set_data($question);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($qid ? get_string('editquestion', 'videomarker') : get_string('addquestion', 'videomarker'));
$form->display();
echo $OUTPUT->footer();
