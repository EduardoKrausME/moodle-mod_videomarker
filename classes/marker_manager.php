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

namespace mod_videomarker;

use completion_info;
use moodle_exception;
use stdClass;

/**
 * Manages marker questions, expected targets, attempts, and scoring.
 *
 * @package   mod_videomarker
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class marker_manager {
    /**
     * Parse MM:SS or HH:MM:SS into seconds.
     *
     * @param string $value Timecode.
     * @return float|null
     */
    public static function parse_timecode(string $value): ?float {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (is_numeric($value)) {
            $seconds = (float)$value;
            return $seconds >= 0 ? $seconds : null;
        }
        $parts = explode(':', $value);
        if (count($parts) < 2 || count($parts) > 3) {
            return null;
        }
        foreach ($parts as $part) {
            if (!preg_match('/^\d+(?:\.\d+)?$/', $part)) {
                return null;
            }
        }
        if (count($parts) === 2) {
            [$minutes, $seconds] = array_map('floatval', $parts);
            if ($seconds >= 60) {
                return null;
            }
            return ($minutes * 60) + $seconds;
        }
        [$hours, $minutes, $seconds] = array_map('floatval', $parts);
        if ($minutes >= 60 || $seconds >= 60) {
            return null;
        }
        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }

    /**
     * Convert seconds to a readable video timecode.
     *
     * @param float $seconds Seconds.
     * @return string
     */
    public static function format_time(float $seconds): string {
        $seconds = max(0, (int)round($seconds));
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;
        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        }
        return sprintf('%02d:%02d', $minutes, $secs);
    }

    /**
     * Parse teacher target lines.
     *
     * @param string $text Target text.
     * @param string $type point or interval.
     * @return array<int, array{start: float, end: float}>|null
     */
    public static function parse_targets(string $text, string $type): ?array {
        $lines = preg_split('/\R+/', trim($text));
        if (!$lines) {
            return null;
        }
        $targets = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = preg_split('/\s*(?:-|–|—|\.\.|\bto\b|\baté\b)\s*/ui', $line, 2);
            $start = self::parse_timecode((string)($parts[0] ?? ''));
            $hasend = count($parts) === 2;
            $end = $hasend ? self::parse_timecode((string)$parts[1]) : $start;
            if ($start === null || $end === null || $end < $start) {
                return null;
            }
            if ($type === 'interval' && !$hasend) {
                return null;
            }
            $targets[] = ['start' => $start, 'end' => $end];
        }
        if (!$targets || count($targets) > 20) {
            return null;
        }
        usort($targets, static fn(array $a, array $b): int => $a['start'] <=> $b['start']);
        return $targets;
    }

    /**
     * Convert stored targets back into form text.
     *
     * @param array $targets Target records.
     * @return string
     */
    public static function targets_to_text(array $targets): string {
        $lines = [];
        foreach ($targets as $target) {
            $lines[] = self::format_time((float)$target->starttime) . '-' . self::format_time((float)$target->endtime);
        }
        return implode("\n", $lines);
    }

    /**
     * Return questions ordered for an activity.
     *
     * @param int $activityid Activity ID.
     * @return array
     */
    public static function questions(int $activityid): array {
        global $DB;
        return $DB->get_records('videomarker_questions', ['videomarkerid' => $activityid], 'sortorder ASC, id ASC');
    }

    /**
     * Return expected targets for a question.
     *
     * @param int $questionid Question ID.
     * @return array
     */
    public static function targets(int $questionid): array {
        global $DB;
        return $DB->get_records('videomarker_targets', ['questionid' => $questionid], 'targetindex ASC, id ASC');
    }

    /**
     * Save a question and replace its targets.
     *
     * @param stdClass $data Form data.
     * @param int $activityid Activity ID.
     * @return int Question ID.
     */
    public static function save_question(stdClass $data, int $activityid): int {
        global $DB;
        $targets = self::parse_targets((string)$data->targets, (string)$data->questiontype);
        if (!$targets) {
            throw new moodle_exception('invalidtargets', 'videomarker');
        }
        $now = time();
        $record = (object)[
            'videomarkerid' => $activityid,
            'questiontext' => (string)$data->questiontext,
            'questiontextformat' => isset($data->questiontextformat) ? (int)$data->questiontextformat : FORMAT_HTML,
            'questiontype' => (string)$data->questiontype,
            'points' => (float)$data->points,
            'required' => empty($data->required) ? 0 : 1,
            'allowretry' => empty($data->allowretry) ? 0 : 1,
            'showfeedback' => empty($data->showfeedback) ? 0 : 1,
            'tolerance' => (int)$data->tolerance,
            'feedbackcorrect' => (string)($data->feedbackcorrect ?? ''),
            'feedbackincorrect' => (string)($data->feedbackincorrect ?? ''),
            'timemodified' => $now,
        ];

        $transaction = $DB->start_delegated_transaction();
        if (!empty($data->id)) {
            $existing = $DB->get_record('videomarker_questions', [
                'id' => (int)$data->id,
                'videomarkerid' => $activityid,
            ], '*', MUST_EXIST);
            $record->id = (int)$existing->id;
            $record->sortorder = (int)$existing->sortorder;
            $DB->update_record('videomarker_questions', $record);
            $questionid = (int)$existing->id;
            $DB->delete_records('videomarker_targets', ['questionid' => $questionid]);
        } else {
            $maxorder = (int)$DB->get_field_sql(
                'SELECT COALESCE(MAX(sortorder), 0) FROM {videomarker_questions} WHERE videomarkerid = :id',
                ['id' => $activityid]
            );
            $record->sortorder = $maxorder + 1;
            $record->timecreated = $now;
            $questionid = $DB->insert_record('videomarker_questions', $record);
        }

        foreach ($targets as $index => $target) {
            $DB->insert_record('videomarker_targets', (object)[
                'questionid' => $questionid,
                'targetindex' => $index + 1,
                'starttime' => $target['start'],
                'endtime' => $target['end'],
            ]);
        }
        $transaction->allow_commit();

        $activity = $DB->get_record('videomarker', ['id' => $activityid], '*', MUST_EXIST);
        videomarker_update_grades($activity);
        return $questionid;
    }

    /**
     * Delete a question and all dependent data.
     *
     * @param int $questionid Question ID.
     * @param int $activityid Activity ID.
     * @return void
     */
    public static function delete_question(int $questionid, int $activityid): void {
        global $DB;
        $question = $DB->get_record('videomarker_questions', [
            'id' => $questionid,
            'videomarkerid' => $activityid,
        ], '*', MUST_EXIST);
        $attemptids = $DB->get_fieldset_select('videomarker_attempts', 'id', 'questionid = :qid', ['qid' => $question->id]);
        $userids = $DB->get_fieldset_select('videomarker_attempts',
            'DISTINCT userid', 'questionid = :qid', ['qid' => $question->id]);
        $transaction = $DB->start_delegated_transaction();
        if ($attemptids) {
            [$insql, $params] = $DB->get_in_or_equal($attemptids, SQL_PARAMS_NAMED, 'attempt');
            $DB->delete_records_select('videomarker_marks', "attemptid {$insql}", $params);
        }
        $DB->delete_records('videomarker_attempts', ['questionid' => $question->id]);
        $DB->delete_records('videomarker_targets', ['questionid' => $question->id]);
        $DB->delete_records('videomarker_questions', ['id' => $question->id]);
        $transaction->allow_commit();

        $activity = $DB->get_record('videomarker', ['id' => $activityid], '*', MUST_EXIST);
        foreach ($userids as $userid) {
            grade_manager::update_user_grade($activity, (int)$userid);
        }
    }

    /**
     * Move a question up or down while preserving a stable sequence.
     *
     * @param int $questionid Question ID.
     * @param int $activityid Activity ID.
     * @param int $direction -1 for up, 1 for down.
     * @return void
     */
    public static function move_question(int $questionid, int $activityid, int $direction): void {
        global $DB;
        $questions = array_values(self::questions($activityid));
        $position = null;
        foreach ($questions as $index => $question) {
            if ((int)$question->id === $questionid) {
                $position = $index;
                break;
            }
        }
        if ($position === null) {
            return;
        }
        $target = $position + ($direction < 0 ? -1 : 1);
        if ($target < 0 || $target >= count($questions)) {
            return;
        }
        [$questions[$position], $questions[$target]] = [$questions[$target], $questions[$position]];
        foreach ($questions as $index => $question) {
            if ((int)$question->sortorder !== $index + 1) {
                $DB->set_field('videomarker_questions', 'sortorder', $index + 1, ['id' => $question->id]);
            }
        }
    }

    /**
     * Get the latest attempt for a question and user.
     *
     * @param int $questionid Question ID.
     * @param int $userid User ID.
     * @return stdClass|false
     */
    public static function latest_attempt(int $questionid, int $userid): stdClass|false {
        global $DB;
        $records = $DB->get_records_sql(
            'SELECT * FROM {videomarker_attempts}
              WHERE questionid = :questionid AND userid = :userid
           ORDER BY attemptno DESC',
            ['questionid' => $questionid, 'userid' => $userid],
            0,
            1
        );
        return $records ? reset($records) : false;
    }

    /**
     * Get marks belonging to an attempt.
     *
     * @param int $attemptid Attempt ID.
     * @return array
     */
    public static function attempt_marks(int $attemptid): array {
        global $DB;
        return $DB->get_records('videomarker_marks', ['attemptid' => $attemptid], 'markerindex ASC, id ASC');
    }

    /**
     * Evaluate and persist one question attempt.
     *
     * @param stdClass $activity Activity record.
     * @param stdClass $question Question record.
     * @param int $userid User ID.
     * @param array<int, array{start: float, end: float|null}> $marks Submitted marks.
     * @return stdClass Attempt record.
     * @throws moodle_exception
     */
    public static function submit_attempt(stdClass $activity, stdClass $question, int $userid, array $marks): stdClass {
        global $DB;
        $targets = array_values(self::targets((int)$question->id));
        if (count($marks) !== count($targets)) {
            throw new moodle_exception('invalidmarkcount', 'videomarker');
        }
        $latest = self::latest_attempt((int)$question->id, $userid);
        if ($latest && empty($question->allowretry)) {
            throw new moodle_exception('retrynotallowed', 'videomarker');
        }

        usort($marks, static fn(array $a, array $b): int => $a['start'] <=> $b['start']);
        $results = [];
        $correctcount = 0;
        $distancesum = 0.0;
        foreach ($targets as $index => $target) {
            $mark = $marks[$index] ?? null;
            if (!$mark || !isset($mark['start']) || !is_numeric($mark['start'])) {
                throw new moodle_exception('invalidmarkdata', 'videomarker');
            }
            $start = max(0.0, (float)$mark['start']);
            $end = $mark['end'] === null ? null : max(0.0, (float)$mark['end']);
            if ($question->questiontype === 'interval') {
                if ($end === null || $end < $start) {
                    throw new moodle_exception('invalidmarkdata', 'videomarker');
                }
                $startdistance = abs($start - (float)$target->starttime);
                $enddistance = abs($end - (float)$target->endtime);
                $distance = ($startdistance + $enddistance) / 2.0;
                $iscorrect = $startdistance <= (int)$question->tolerance && $enddistance <= (int)$question->tolerance;
            } else {
                $end = null;
                if ($start < (float)$target->starttime) {
                    $distance = (float)$target->starttime - $start;
                } else if ($start > (float)$target->endtime) {
                    $distance = $start - (float)$target->endtime;
                } else {
                    $distance = 0.0;
                }
                $iscorrect = $distance <= 0.0001;
            }
            if ($iscorrect) {
                $correctcount++;
            }
            $distancesum += $distance;
            $results[] = [
                'start' => $start,
                'end' => $end,
                'targetid' => (int)$target->id,
                'expectedstart' => (float)$target->starttime,
                'expectedend' => (float)$target->endtime,
                'iscorrect' => $iscorrect ? 1 : 0,
                'distance' => $distance,
            ];
        }

        $markcount = count($targets);
        $score = $markcount > 0 ? ((float)$question->points * ($correctcount / $markcount)) : 0.0;
        $attemptno = $latest ? ((int)$latest->attemptno + 1) : 1;
        $now = time();
        $transaction = $DB->start_delegated_transaction();
        $attemptid = $DB->insert_record('videomarker_attempts', (object)[
            'videomarkerid' => (int)$activity->id,
            'questionid' => (int)$question->id,
            'userid' => $userid,
            'attemptno' => $attemptno,
            'score' => $score,
            'maxscore' => (float)$question->points,
            'correctcount' => $correctcount,
            'markcount' => $markcount,
            'avgdistance' => $markcount > 0 ? $distancesum / $markcount : 0,
            'timecreated' => $now,
        ]);
        foreach ($results as $index => $result) {
            $DB->insert_record('videomarker_marks', (object)[
                'attemptid' => $attemptid,
                'markerindex' => $index + 1,
                'starttime' => $result['start'],
                'endtime' => $result['end'],
                'targetid' => $result['targetid'],
                'expectedstart' => $result['expectedstart'],
                'expectedend' => $result['expectedend'],
                'iscorrect' => $result['iscorrect'],
                'distance' => $result['distance'],
                'timecreated' => $now,
            ]);
        }
        $transaction->allow_commit();

        grade_manager::update_user_grade($activity, $userid);
        $cm = get_coursemodule_from_instance('videomarker', $activity->id, $activity->course, false, IGNORE_MISSING);
        if ($cm) {
            $completion = new completion_info(get_course($activity->course));
            if ($completion->is_enabled($cm)) {
                $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);
            }
        }
        return $DB->get_record('videomarker_attempts', ['id' => $attemptid], '*', MUST_EXIST);
    }

    /**
     * Count mandatory questions and mandatory questions attempted by a user.
     *
     * @param int $activityid Activity ID.
     * @param int $userid User ID.
     * @return array{answered: int, total: int}
     */
    public static function required_status(int $activityid, int $userid): array {
        global $DB;
        $total = $DB->count_records('videomarker_questions', [
            'videomarkerid' => $activityid,
            'required' => 1,
        ]);
        if ($total === 0) {
            return ['answered' => 0, 'total' => 0];
        }
        $answered = (int)$DB->count_records_sql(
            'SELECT COUNT(DISTINCT q.id)
               FROM {videomarker_questions} q
               JOIN {videomarker_attempts} a ON a.questionid = q.id
              WHERE q.videomarkerid = :activityid
                AND q.required = 1
                AND a.userid = :userid',
            ['activityid' => $activityid, 'userid' => $userid]
        );
        return ['answered' => $answered, 'total' => $total];
    }
}
