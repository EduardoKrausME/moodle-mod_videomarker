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
 * provider.php
 *
 * @package   mod_videomarker
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videomarker\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Privacy provider for Video Marker.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * get_metadata
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('videomarker_attempts', [
            'userid' => 'privacy:metadata:videomarker_attempts:userid',
            'questionid' => 'privacy:metadata:videomarker_attempts:questionid',
            'score' => 'privacy:metadata:videomarker_attempts:score',
            'timecreated' => 'privacy:metadata:videomarker_attempts:timecreated',
        ], 'privacy:metadata:videomarker_attempts');
        $collection->add_database_table('videomarker_marks', [
            'starttime' => 'privacy:metadata:videomarker_marks:starttime',
            'endtime' => 'privacy:metadata:videomarker_marks:endtime',
            'iscorrect' => 'privacy:metadata:videomarker_marks:iscorrect',
            'distance' => 'privacy:metadata:videomarker_marks:distance',
        ], 'privacy:metadata:videomarker_marks');
        $collection->add_database_table('videomarker_progress', [
            'userid' => 'privacy:metadata:videomarker_progress:userid',
            'percent' => 'privacy:metadata:videomarker_progress:percent',
            'lastposition' => 'privacy:metadata:videomarker_progress:lastposition',
            'watchedranges' => 'privacy:metadata:videomarker_progress:watchedranges',
            'timemodified' => 'privacy:metadata:videomarker_progress:timemodified',
        ], 'privacy:metadata:videomarker_progress');
        return $collection;
    }

    /**
     * get_contexts_for_userid
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {videomarker} v ON v.id = cm.instance
             LEFT JOIN {videomarker_progress} p ON p.videomarkerid = v.id AND p.userid = :puserid
             LEFT JOIN {videomarker_attempts} a ON a.videomarkerid = v.id AND a.userid = :auserid
                 WHERE p.id IS NOT NULL OR a.id IS NOT NULL";
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'videomarker',
            'puserid' => $userid,
            'auserid' => $userid,
        ]);
        return $contextlist;
    }

    /**
     * export_user_data
     *
     * @param approved_contextlist $contextlist
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('videomarker', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $progress = $DB->get_record('videomarker_progress', [
                'videomarkerid' => $cm->instance,
                'userid' => $userid,
            ]);
            $attempts = $DB->get_records('videomarker_attempts', [
                'videomarkerid' => $cm->instance,
                'userid' => $userid,
            ], 'timecreated ASC');

            $attemptdata = [];
            foreach ($attempts as $attempt) {
                $marks = $DB->get_records('videomarker_marks', ['attemptid' => $attempt->id], 'markerindex ASC');
                $attemptdata[] = [
                    'questionid' => $attempt->questionid,
                    'attemptno' => $attempt->attemptno,
                    'score' => $attempt->score,
                    'maxscore' => $attempt->maxscore,
                    'correctcount' => $attempt->correctcount,
                    'markcount' => $attempt->markcount,
                    'avgdistance' => $attempt->avgdistance,
                    'timecreated' => transform::datetime($attempt->timecreated),
                    'marks' => array_values(array_map(static function ($mark): array {
                        return [
                            'starttime' => $mark->starttime,
                            'endtime' => $mark->endtime,
                            'iscorrect' => $mark->iscorrect,
                            'distance' => $mark->distance,
                        ];
                    }, $marks)),
                ];
            }

            writer::with_context($context)->export_data([], (object)[
                'progress' => $progress ? [
                    'percent' => $progress->percent,
                    'lastposition' => $progress->lastposition,
                    'watchedranges' => $progress->watchedranges,
                    'timemodified' => transform::datetime($progress->timemodified),
                ] : null,
                'attempts' => $attemptdata,
            ]);
        }
    }

    /**
     * delete_data_for_all_users_in_context
     *
     * @param \context $context
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('videomarker', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $attemptids = $DB->get_fieldset_select('videomarker_attempts', 'id', 'videomarkerid = :id', ['id' => $cm->instance]);
        self::delete_marks_for_attempts($attemptids);
        $DB->delete_records('videomarker_attempts', ['videomarkerid' => $cm->instance]);
        $DB->delete_records('videomarker_progress', ['videomarkerid' => $cm->instance]);
    }

    /**
     * delete_data_for_user
     *
     * @param approved_contextlist $contextlist
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('videomarker', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $attemptids = $DB->get_fieldset_select('videomarker_attempts', 'id',
                'videomarkerid = :activity AND userid = :userid', ['activity' => $cm->instance, 'userid' => $userid]);
            self::delete_marks_for_attempts($attemptids);
            $DB->delete_records('videomarker_attempts', ['videomarkerid' => $cm->instance, 'userid' => $userid]);
            $DB->delete_records('videomarker_progress', ['videomarkerid' => $cm->instance, 'userid' => $userid]);
        }
    }

    /**
     * delete_marks_for_attempts
     *
     * @param array $attemptids
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private static function delete_marks_for_attempts(array $attemptids): void {
        global $DB;
        if (!$attemptids) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($attemptids, SQL_PARAMS_NAMED, 'attempt');
        $DB->delete_records_select('videomarker_marks', "attemptid {$insql}", $params);
    }
}
