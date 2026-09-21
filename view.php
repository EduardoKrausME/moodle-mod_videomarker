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
 * view.php
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
require_capability('mod/videomarker:view', $context);

$PAGE->set_url('/mod/videomarker/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($activity);
$PAGE->requires->js_call_amd('mod_videomarker/player', 'init');

$event = \mod_videomarker\event\course_module_viewed::create([
    'objectid' => $activity->id,
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('videomarker', $activity);
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$player = \mod_videomarker\video_source::player_context($activity, $context);
$progress = \mod_videomarker\progress_manager::get((int)$activity->id, (int)$USER->id);
$canattempt = has_capability('mod/videomarker:attempt', $context);
$questions = [];
$timeline = [];
foreach (array_values(\mod_videomarker\marker_manager::questions((int)$activity->id)) as $index => $question) {
    $targets = \mod_videomarker\marker_manager::targets((int)$question->id);
    $latest = \mod_videomarker\marker_manager::latest_attempt((int)$question->id, (int)$USER->id);
    $marks = [];
    if ($latest) {
        foreach (\mod_videomarker\marker_manager::attempt_marks((int)$latest->id) as $mark) {
            $display = \mod_videomarker\marker_manager::format_time((float)$mark->starttime);
            if ($mark->endtime !== null) {
                $display .= ' – ' . \mod_videomarker\marker_manager::format_time((float)$mark->endtime);
            }
            $marks[] = [
                'starttime' => (float)$mark->starttime,
                'display' => $display,
                'iscorrect' => !empty($mark->iscorrect),
            ];
            if ((float)$progress->duration > 0) {
                $timeline[] = [
                    'percent' => min(100, max(0, ((float)$mark->starttime / (float)$progress->duration) * 100)),
                    'time' => (float)$mark->starttime,
                    'label' => strip_tags($question->questiontext) . ' — ' . $display,
                    'iscorrect' => !empty($mark->iscorrect),
                ];
            }
        }
    }
    $locked = $latest && empty($question->allowretry);
    $questions[] = [
        'id' => (int)$question->id,
        'number' => $index + 1,
        'questionhtml' => format_text($question->questiontext, $question->questiontextformat, ['context' => $context]),
        'questiontype' => (string)$question->questiontype,
        'point' => $question->questiontype === 'point',
        'interval' => $question->questiontype === 'interval',
        'required' => !empty($question->required),
        'requiredcount' => count($targets),
        'pointsformatted' => format_float($question->points, 2),
        'canattempt' => $canattempt,
        'locked' => (bool)$locked,
        'latest' => $latest ? [
            'scoreformatted' => format_float($latest->score, 2),
            'maxscoreformatted' => format_float($latest->maxscore, 2),
            'marks' => $marks,
        ] : false,
    ];
}

$data = array_merge($player, [
    'cmid' => (int)$cm->id,
    'activityname' => format_string($activity->name),
    'intro' => trim((string)$activity->intro) !== '' ? format_module_intro('videomarker', $activity, $cm->id, false) : '',
    'resumeplayback' => (int)$activity->resumeplayback,
    'allowseek' => (int)$activity->allowseek,
    'maxplaybackrate' => (float)$activity->maxplaybackrate,
    'lastposition' => (float)$progress->lastposition,
    'maxwatched' => \mod_videomarker\progress_manager::max_watched($progress),
    'percentformatted' => format_float($progress->percent, 1),
    'questions' => $questions,
    'hasquestions' => (bool)$questions,
    'timeline' => $timeline,
]);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($activity->name));

$buttons = [];
if (has_capability('mod/videomarker:managequestions', $context)) {
    $buttons[] = html_writer::link(new moodle_url('/mod/videomarker/questions.php', ['id' => $cm->id]),
        get_string('managequestions', 'videomarker'), ['class' => 'btn btn-secondary']);
}
if (has_capability('mod/videomarker:viewreports', $context)) {
    $buttons[] = html_writer::link(new moodle_url('/mod/videomarker/report.php', ['id' => $cm->id]),
        get_string('viewreport', 'videomarker'), ['class' => 'btn btn-secondary']);
}
if ($buttons) {
    echo html_writer::div(implode(' ', $buttons), 'mb-3');
}

echo $OUTPUT->render_from_template('mod_videomarker/view', $data);
echo $OUTPUT->footer();
