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

use stdClass;

/**
 * Gradebook calculations for Video Marker.
 *
 * @package   mod_videomarker
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_manager {
    /**
     * Calculate the best-attempt score for a user.
     *
     * @param stdClass $activity Activity record.
     * @param int $userid User ID.
     * @return array{rawgrade: float|null, score: float, maxscore: float}
     */
    public static function calculate(stdClass $activity, int $userid): array {
        global $DB;
        $questions = marker_manager::questions((int)$activity->id);
        $earned = 0.0;
        $possible = 0.0;
        foreach ($questions as $question) {
            $possible += (float)$question->points;
            $best = $DB->get_field_sql(
                'SELECT MAX(score) FROM {videomarker_attempts}
                  WHERE questionid = :questionid AND userid = :userid',
                ['questionid' => $question->id, 'userid' => $userid]
            );
            if ($best !== false && $best !== null) {
                $earned += min((float)$question->points, (float)$best);
            }
        }
        if ($possible <= 0 || (float)$activity->grade <= 0) {
            return ['rawgrade' => null, 'score' => $earned, 'maxscore' => $possible];
        }
        return [
            'rawgrade' => ((float)$activity->grade) * ($earned / $possible),
            'score' => $earned,
            'maxscore' => $possible,
        ];
    }

    /**
     * Push one user's calculated grade to Moodle gradebook.
     *
     * @param stdClass $activity Activity record.
     * @param int $userid User ID.
     * @return void
     */
    public static function update_user_grade(stdClass $activity, int $userid): void {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        $calculated = self::calculate($activity, $userid);
        $grades = [(object)[
            'userid' => $userid,
            'rawgrade' => $calculated['rawgrade'],
            'dategraded' => time(),
            'datesubmitted' => time(),
        ]];
        videomarker_grade_item_update($activity, $grades);
    }
}
