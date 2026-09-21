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

namespace mod_videomarker\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_videomarker\event\answer_submitted;
use mod_videomarker\marker_manager;

/**
 * AJAX service used to submit timestamp and interval answers.
 *
 * @package mod_videomarker
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_answer extends external_api {
    /** @return external_function_parameters Parameters definition. */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'questionid' => new external_value(PARAM_INT, 'Question id'),
            'marksjson' => new external_value(PARAM_RAW, 'JSON encoded marker values'),
        ]);
    }

    /**
     * Grades and stores one complete question attempt.
     *
     * @param int $cmid Course module id.
     * @param int $questionid Question id.
     * @param string $marksjson Marker JSON.
     * @return array Graded result.
     */
    public static function execute(int $cmid, int $questionid, string $marksjson): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'questionid' => $questionid,
            'marksjson' => $marksjson,
        ]);

        $cm = get_coursemodule_from_id('videomarker', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/videomarker:attempt', $context);

        $activity = $DB->get_record('videomarker', ['id' => $cm->instance], '*', MUST_EXIST);
        $question = $DB->get_record('videomarker_questions', ['id' => $params['questionid']], '*', MUST_EXIST);
        if ((int)$question->videomarkerid !== (int)$activity->id) {
            throw new \moodle_exception('invalidquestion', 'videomarker');
        }

        $marks = json_decode($params['marksjson'], true);
        if (!is_array($marks)) {
            throw new \moodle_exception('invalidmarkdata', 'videomarker');
        }

        $result = (new marker_manager())->submit_attempt($activity, $question, (int)$USER->id, $marks);

        $event = answer_submitted::create([
            'objectid' => $result->id,
            'context' => $context,
            'userid' => $USER->id,
            'other' => ['questionid' => (int)$question->id],
        ]);
        $event->trigger();

        $feedback = '';
        if (!empty($question->showfeedback)) {
            $feedback = $result->correctcount === $result->markcount
                ? (string)$question->feedbackcorrect
                : (string)$question->feedbackincorrect;
        }

        return [
            'attemptid' => (int)$result->id,
            'attemptno' => (int)$result->attemptno,
            'score' => (float)$result->score,
            'maxscore' => (float)$result->maxscore,
            'correctcount' => (int)$result->correctcount,
            'markcount' => (int)$result->markcount,
            'avgdistance' => (float)$result->avgdistance,
            'feedback' => $feedback,
            'locked' => empty($question->allowretry),
        ];
    }

    /** @return external_single_structure Return definition. */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'attemptid' => new external_value(PARAM_INT, 'Attempt id'),
            'attemptno' => new external_value(PARAM_INT, 'Attempt number'),
            'score' => new external_value(PARAM_FLOAT, 'Attempt score'),
            'maxscore' => new external_value(PARAM_FLOAT, 'Maximum score'),
            'correctcount' => new external_value(PARAM_INT, 'Correct markers'),
            'markcount' => new external_value(PARAM_INT, 'Total markers'),
            'avgdistance' => new external_value(PARAM_FLOAT, 'Average temporal distance in seconds'),
            'feedback' => new external_value(PARAM_RAW, 'Configured feedback'),
            'locked' => new external_value(PARAM_BOOL, 'Whether further attempts are locked'),
        ]);
    }
}
