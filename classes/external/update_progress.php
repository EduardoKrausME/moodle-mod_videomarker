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
use mod_videomarker\progress_manager;

/**
 * AJAX service used by the video player to persist watched ranges.
 *
 * @package mod_videomarker
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_progress extends external_api {
    /** @return external_function_parameters Parameters definition. */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'duration' => new external_value(PARAM_FLOAT, 'Video duration'),
            'currenttime' => new external_value(PARAM_FLOAT, 'Current playback time'),
            'segmentstart' => new external_value(PARAM_FLOAT, 'Played segment start'),
            'segmentend' => new external_value(PARAM_FLOAT, 'Played segment end'),
        ]);
    }

    /**
     * Saves one played segment.
     *
     * @param int $cmid Course module id.
     * @param float $duration Video duration.
     * @param float $currenttime Current time.
     * @param float $segmentstart Segment start.
     * @param float $segmentend Segment end.
     * @return array Progress payload.
     */
    public static function execute(int $cmid, float $duration, float $currenttime,
                                   float $segmentstart, float $segmentend): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'duration' => $duration,
            'currenttime' => $currenttime,
            'segmentstart' => $segmentstart,
            'segmentend' => $segmentend,
        ]);

        $cm = get_coursemodule_from_id('videomarker', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/videomarker:attempt', $context);
        $activity = $DB->get_record('videomarker', ['id' => $cm->instance], '*', MUST_EXIST);

        $progress = (new progress_manager())->update(
            $activity,
            (int)$USER->id,
            (float)$params['duration'],
            (float)$params['currenttime'],
            (float)$params['segmentstart'],
            (float)$params['segmentend']
        );

        return [
            'percent' => (float)$progress->percent,
            'lastposition' => (float)$progress->lastposition,
            'maxwatched' => (float)(new progress_manager())->max_watched($progress),
        ];
    }

    /** @return external_single_structure Return definition. */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'percent' => new external_value(PARAM_FLOAT, 'Actually watched percentage'),
            'lastposition' => new external_value(PARAM_FLOAT, 'Saved resume position'),
            'maxwatched' => new external_value(PARAM_FLOAT, 'Farthest actually watched position'),
        ]);
    }
}
