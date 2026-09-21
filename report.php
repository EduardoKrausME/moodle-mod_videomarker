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
 * report.php
 *
 * @package   mod_videomarker
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videomarker\grade_manager;
use mod_videomarker\marker_manager;
use mod_videomarker\progress_manager;

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$cm = get_coursemodule_from_id('videomarker', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videomarker', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/videomarker:viewreports', $context);

$PAGE->set_url('/mod/videomarker/report.php', ['id' => $cm->id, 'userid' => $userid]);
$PAGE->set_title(get_string('reports', 'videomarker'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($activity->name));
echo html_writer::div(html_writer::link(new moodle_url('/mod/videomarker/view.php', ['id' => $cm->id]),
    get_string('backtoactivity', 'videomarker'), ['class' => 'btn btn-secondary']), 'mb-3');

if ($userid) {
    $user = core_user::get_user($userid, '*', MUST_EXIST);
    if (!is_enrolled($context, $user)) {
        throw new moodle_exception('notenrolled', 'core_enrol');
    }
    echo $OUTPUT->heading(get_string('reportstudent', 'videomarker') . ': ' . fullname($user), 3);
    echo html_writer::div(html_writer::link(new moodle_url('/mod/videomarker/report.php', ['id' => $cm->id]),
        get_string('backtoreport', 'videomarker'), ['class' => 'btn btn-secondary']), 'mb-3');

    $progress = progress_manager::get((int)$activity->id, (int)$user->id);
    $grade = grade_manager::calculate($activity, (int)$user->id);
    $required = marker_manager::required_status((int)$activity->id, (int)$user->id);
    $summary = new html_table();
    $summary->data = [
        [
            get_string('watched', 'videomarker'),
            format_float($progress->percent, 1) . '%',
        ],
        [
            get_string('requiredanswered', 'videomarker'),
            $required['answered'] . ' / ' . $required['total'],
        ],
        [
            get_string('overallgrade', 'videomarker'),
            $grade['rawgrade'] === null ? get_string('notgraded', 'videomarker') : format_float($grade['rawgrade'], 2),
        ],
    ];
    echo html_writer::table($summary);

    $attempts = $DB->get_records_sql(
        'SELECT a.*, q.questiontext, q.questiontextformat, q.sortorder, q.questiontype
           FROM {videomarker_attempts} a
           JOIN {videomarker_questions} q ON q.id = a.questionid
          WHERE a.videomarkerid = :activityid AND a.userid = :userid
       ORDER BY q.sortorder ASC, a.attemptno ASC',
        ['activityid' => $activity->id, 'userid' => $user->id]
    );
    if (!$attempts) {
        echo $OUTPUT->notification(get_string('noattempts', 'videomarker'), 'info');
    } else {
        $table = new html_table();
        $table->head = [get_string('questiontext', 'videomarker'), get_string('attempt', 'videomarker', ''),
            get_string('studentmark', 'videomarker'), get_string('expected', 'videomarker'),
            get_string('correct', 'videomarker'), get_string('distance', 'videomarker'), get_string('grade', 'videomarker')];
        foreach ($attempts as $attempt) {
            $marks = marker_manager::attempt_marks((int)$attempt->id);
            foreach ($marks as $index => $mark) {
                $studentmark = marker_manager::format_time((float)$mark->starttime);
                if ($mark->endtime !== null) {
                    $studentmark .= ' – ' . marker_manager::format_time((float)$mark->endtime);
                }
                $expected = marker_manager::format_time((float)$mark->expectedstart);
                if ($attempt->questiontype === 'interval' || (float)$mark->expectedend !== (float)$mark->expectedstart) {
                    $expected .= ' – ' . marker_manager::format_time((float)$mark->expectedend);
                }
                $table->data[] = [
                    $index === 0 ? format_text($attempt->questiontext, $attempt->questiontextformat, ['context' => $context]) : '',
                    $attempt->attemptno,
                    $studentmark,
                    $expected,
                    $mark->iscorrect ? get_string('yes', 'videomarker') : get_string('no', 'videomarker'),
                    get_string('seconds', 'videomarker', format_float($mark->distance, 2)),
                    $index === 0 ? format_float($attempt->score, 2) . ' / ' . format_float($attempt->maxscore, 2) : '',
                ];
            }
        }
        echo html_writer::table($table);
    }
} else {
    echo $OUTPUT->heading(get_string('reports', 'videomarker'), 3);
    $users = get_enrolled_users($context, 'mod/videomarker:attempt', 0,
        "u.id,u.firstname,u.lastname,u.email,u.picture,u.imagealt,u.firstnamephonetic," .
        "u.lastnamephonetic,u.middlename,u.alternatename",
        "u.lastname,u.firstname");
    $table = new html_table();
    $table->head = [get_string('student', 'videomarker'), get_string('markings', 'videomarker'),
        get_string('correct', 'videomarker'), get_string('incorrect', 'videomarker'), get_string('averagedistance', 'videomarker'),
        get_string('grade', 'videomarker'), get_string('watched', 'videomarker'), get_string('requiredanswered', 'videomarker'),
        get_string('lastaccess', 'videomarker')];
    foreach ($users as $user) {
        $stats = $DB->get_record_sql(
            'SELECT COUNT(m.id) AS markings,
                    COALESCE(SUM(m.iscorrect), 0) AS correctcount,
                    COALESCE(AVG(m.distance), 0) AS avgdistance,
                    COALESCE(MAX(a.timecreated), 0) AS lastattempt
               FROM {videomarker_attempts} a
          LEFT JOIN {videomarker_marks} m ON m.attemptid = a.id
              WHERE a.videomarkerid = :activityid AND a.userid = :userid',
            ['activityid' => $activity->id, 'userid' => $user->id]
        );
        $markings = (int)($stats->markings ?? 0);
        $correct = (int)($stats->correctcount ?? 0);
        $progress = progress_manager::get((int)$activity->id, (int)$user->id);
        $grade = grade_manager::calculate($activity, (int)$user->id);
        $required = marker_manager::required_status((int)$activity->id, (int)$user->id);
        $last = max((int)($stats->lastattempt ?? 0), (int)$progress->timemodified);
        $table->data[] = [
            html_writer::link(new moodle_url('/mod/videomarker/report.php',
                ['id' => $cm->id, 'userid' => $user->id]), fullname($user)),
            $markings,
            $correct,
            max(0, $markings - $correct),
            get_string('seconds', 'videomarker', format_float((float)($stats->avgdistance ?? 0), 2)),
            $grade['rawgrade'] === null ? '-' : format_float($grade['rawgrade'], 2),
            format_float($progress->percent, 1) . '%',
            $required['answered'] . ' / ' . $required['total'],
            $last ? userdate($last) : '-',
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
