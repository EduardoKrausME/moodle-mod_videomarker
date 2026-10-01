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
use stdClass;

/**
 * Tracks real watched video ranges and resume position.
 *
 * @package   mod_videomarker
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class progress_manager {
    /**
     * Return progress for a user, or an empty progress object.
     *
     * @param int $activityid Activity ID.
     * @param int $userid User ID.
     * @return stdClass
     */
    public static function get(int $activityid, int $userid): stdClass {
        global $DB;
        $record = $DB->get_record('videomarker_progress', [
            'videomarkerid' => $activityid,
            'userid' => $userid,
        ]);
        if ($record) {
            return $record;
        }
        return (object)[
            'id' => 0,
            'videomarkerid' => $activityid,
            'userid' => $userid,
            'duration' => 0.0,
            'watchedranges' => '[]',
            'watchedseconds' => 0.0,
            'percent' => 0.0,
            'lastposition' => 0.0,
            'timecreated' => 0,
            'timemodified' => 0,
        ];
    }

    /**
     * Persist one playback sample and merge genuine played ranges.
     *
     * @param stdClass $activity Activity record.
     * @param int $userid User ID.
     * @param float $duration Video duration.
     * @param float $currenttime Current player position.
     * @param float $segmentstart Start of continuously played sample.
     * @param float $segmentend End of continuously played sample.
     * @return stdClass Updated progress.
     */
    public static function update(
        stdClass $activity,
        int $userid,
        float $duration,
        float $currenttime,
        float $segmentstart,
        float $segmentend
    ): stdClass {
        global $DB;

        $now = time();
        $progress = self::get((int)$activity->id, $userid);
        $duration = max(0.0, min(86400.0, $duration));
        $currenttime = max(0.0, $duration > 0 ? min($duration, $currenttime) : $currenttime);
        $segmentstart = max(0.0, $segmentstart);
        $segmentend = max(0.0, $segmentend);

        $ranges = self::decode_ranges((string)$progress->watchedranges);
        $segmentlength = $segmentend - $segmentstart;
        if ($duration > 0 && $segmentlength >= 0 && $segmentlength <= 15.0) {
            $start = min($duration, $segmentstart);
            $end = min($duration, $segmentend);
            if ($end >= $start) {
                $ranges[] = [$start, $end];
            }
        }
        $ranges = self::merge_ranges($ranges);
        $watched = 0.0;
        foreach ($ranges as $range) {
            $watched += max(0.0, $range[1] - $range[0]);
        }
        $percent = $duration > 0 ? min(100.0, ($watched / $duration) * 100.0) : 0.0;

        $record = (object)[
            'videomarkerid' => (int)$activity->id,
            'userid' => $userid,
            'duration' => $duration,
            'watchedranges' => json_encode($ranges, JSON_UNESCAPED_SLASHES),
            'watchedseconds' => $watched,
            'percent' => $percent,
            'lastposition' => $currenttime,
            'timemodified' => $now,
        ];

        if (!empty($progress->id)) {
            $record->id = (int)$progress->id;
            $DB->update_record('videomarker_progress', $record);
        } else {
            $record->timecreated = $now;
            $record->id = $DB->insert_record('videomarker_progress', $record);
        }

        $cm = get_coursemodule_from_instance('videomarker', $activity->id, $activity->course, false, IGNORE_MISSING);
        if ($cm) {
            $completion = new completion_info(get_course($activity->course));
            if ($completion->is_enabled($cm)) {
                $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);
            }
        }

        return self::get((int)$activity->id, $userid);
    }

    /**
     * Get the furthest contiguous or isolated watched endpoint.
     *
     * @param stdClass $progress Progress record.
     * @return float
     */
    public static function max_watched(stdClass $progress): float {
        $max = 0.0;
        foreach (self::decode_ranges((string)$progress->watchedranges) as $range) {
            $max = max($max, (float)$range[1]);
        }
        return $max;
    }

    /**
     * Decode stored ranges safely.
     *
     * @param string $json JSON ranges.
     * @return array<int, array{0: float, 1: float}>
     */
    private static function decode_ranges(string $json): array {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        $ranges = [];
        foreach ($decoded as $range) {
            if (!is_array($range) || count($range) !== 2 || !is_numeric($range[0]) || !is_numeric($range[1])) {
                continue;
            }
            $ranges[] = [(float)$range[0], (float)$range[1]];
        }
        return $ranges;
    }

    /**
     * Merge overlapping and nearly adjacent ranges.
     *
     * @param array<int, array{0: float, 1: float}> $ranges Ranges.
     * @return array<int, array{0: float, 1: float}>
     */
    private static function merge_ranges(array $ranges): array {
        if (!$ranges) {
            return [];
        }
        usort($ranges, static fn(array $a, array $b): int => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($ranges as $range) {
            $start = max(0.0, (float)$range[0]);
            $end = max($start, (float)$range[1]);
            if (!$merged) {
                $merged[] = [$start, $end];
                continue;
            }
            $last = count($merged) - 1;
            if ($start <= $merged[$last][1] + 1.0) {
                $merged[$last][1] = max($merged[$last][1], $end);
            } else {
                $merged[] = [$start, $end];
            }
        }
        return $merged;
    }
}
