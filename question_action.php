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
 * question_action.php
 *
 * @package   mod_videomarker
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$qid = required_param('qid', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$cm = get_coursemodule_from_id('videomarker', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videomarker', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videomarker:managequestions', $context);
require_sesskey();

$question = $DB->get_record('videomarker_questions', [
    'id' => $qid,
    'videomarkerid' => $activity->id,
], '*', MUST_EXIST);

if ($action === 'delete') {
    \mod_videomarker\marker_manager::delete_question((int)$question->id, (int)$activity->id);
} else if ($action === 'up') {
    \mod_videomarker\marker_manager::move_question((int)$question->id, (int)$activity->id, -1);
} else if ($action === 'down') {
    \mod_videomarker\marker_manager::move_question((int)$question->id, (int)$activity->id, 1);
}
redirect(new moodle_url('/mod/videomarker/questions.php', ['id' => $cm->id]));
