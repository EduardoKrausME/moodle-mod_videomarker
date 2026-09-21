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

namespace mod_videomarker\completion;

use core_completion\activity_custom_completion;
use core_completion\cm_completion_details;
use mod_videomarker\marker_manager;
use mod_videomarker\progress_manager;

/**
 * Custom completion rules for Video Marker.
 *
 * @package mod_videomarker
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /**
     * Returns the state of a custom completion rule.
     *
     * @param string $rule Rule name.
     * @return int Completion state.
     */
    public function get_state(string $rule): int {
        global $DB;

        $activity = $DB->get_record('videomarker', ['id' => $this->cm->instance], '*', MUST_EXIST);

        switch ($rule) {
            case 'completionpercent':
                $required = max(0, min(100, (int)$activity->completionpercent));
                if ($required <= 0) {
                    return COMPLETION_COMPLETE;
                }
                $progress = (new progress_manager())->get((int)$activity->id, (int)$this->userid);
                return (float)$progress->percent >= $required ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;

            case 'completionmarkers':
                if (empty($activity->completionmarkers)) {
                    return COMPLETION_COMPLETE;
                }
                $status = (new marker_manager())->required_status((int)$activity->id, (int)$this->userid);
                return $status['answered'] >= $status['total'] ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;

            default:
                throw new \coding_exception('Unknown completion rule: ' . $rule);
        }
    }

    /**
     * Lists the custom rules exposed by this activity.
     *
     * @return array Rule names.
     */
    public static function get_defined_custom_rules(): array {
        return ['completionpercent', 'completionmarkers'];
    }

    /**
     * Returns human-readable rule descriptions.
     *
     * @return array Rule descriptions keyed by rule name.
     */
    public function get_custom_rule_descriptions(): array {
        global $DB;

        $activity = $DB->get_record('videomarker', ['id' => $this->cm->instance], '*', MUST_EXIST);
        return [
            'completionpercent' => get_string('completiondetail:percent', 'videomarker', (int)$activity->completionpercent),
            'completionmarkers' => get_string('completiondetail:markers', 'videomarker'),
        ];
    }

    /**
     * Defines completion rule display order.
     *
     * @return array Rule order.
     */
    public function get_sort_order(): array {
        return ['completionview', 'completionpercent', 'completionmarkers'];
    }
}
