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
 * Core callbacks for Video Marker.
 *
 * @package   mod_videomarker
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videomarker\grade_manager;
use mod_videomarker\marker_manager;
use mod_videomarker\video_source;

/**
 * Declare Moodle features supported by Video Marker.
 *
 * @param string $feature Moodle feature constant.
 * @return bool|string|null
 */
function videomarker_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        default:
            return null;
    }
}

/**
 * Create a Video Marker instance.
 *
 * @param stdClass $data Activity form data.
 * @param mod_videomarker_mod_form|null $mform Activity form.
 * @return int New activity ID.
 */
function videomarker_add_instance(stdClass $data, ?mod_videomarker_mod_form $mform = null): int {
    global $DB;
    $now = time();
    $data->timecreated = $now;
    $data->timemodified = $now;
    if (($data->videosource ?? '') !== 'upload') {
        $data->videourl = video_source::normalise_url((string)$data->videosource, (string)$data->videourl);
    } else {
        $data->videourl = '';
    }
    $id = $DB->insert_record('videomarker', $data);
    $data->id = $id;
    $context = context_module::instance((int)$data->coursemodule);
    video_source::save_files($data, $context);
    videomarker_grade_item_update($data);
    return $id;
}

/**
 * Update a Video Marker instance.
 *
 * @param stdClass $data Activity form data.
 * @param mod_videomarker_mod_form|null $mform Activity form.
 * @return bool
 */
function videomarker_update_instance(stdClass $data, ?mod_videomarker_mod_form $mform = null): bool {
    global $DB;
    $data->id = $data->instance;
    $data->timemodified = time();
    if (($data->videosource ?? '') !== 'upload') {
        $data->videourl = video_source::normalise_url((string)$data->videosource, (string)$data->videourl);
    } else {
        $data->videourl = '';
    }
    $result = $DB->update_record('videomarker', $data);
    $context = context_module::instance((int)$data->coursemodule);
    video_source::save_files($data, $context);
    videomarker_grade_item_update($data);
    return $result;
}

/**
 * Delete an activity and all associated user data.
 *
 * @param int $id Activity ID.
 * @return bool
 */
function videomarker_delete_instance(int $id): bool {
    global $DB;
    $activity = $DB->get_record('videomarker', ['id' => $id]);
    if (!$activity) {
        return false;
    }

    $cm = get_coursemodule_from_instance('videomarker', $id, $activity->course, false, IGNORE_MISSING);
    $context = $cm ? context_module::instance($cm->id) : null;

    $transaction = $DB->start_delegated_transaction();
    $questionids = $DB->get_fieldset_select('videomarker_questions', 'id', 'videomarkerid = :id', ['id' => $id]);
    if ($questionids) {
        [$questionsql, $questionparams] = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED, 'question');
        $attemptids = $DB->get_fieldset_select('videomarker_attempts', 'id', "questionid {$questionsql}", $questionparams);
        if ($attemptids) {
            [$attemptsql, $attemptparams] = $DB->get_in_or_equal($attemptids, SQL_PARAMS_NAMED, 'attempt');
            $DB->delete_records_select('videomarker_marks', "attemptid {$attemptsql}", $attemptparams);
        }
        $DB->delete_records_select('videomarker_attempts', "questionid {$questionsql}", $questionparams);
        $DB->delete_records_select('videomarker_targets', "questionid {$questionsql}", $questionparams);
        $DB->delete_records_select('videomarker_questions', "id {$questionsql}", $questionparams);
    }
    $DB->delete_records('videomarker_progress', ['videomarkerid' => $id]);
    $DB->delete_records('videomarker', ['id' => $id]);
    $transaction->allow_commit();

    if ($context) {
        get_file_storage()->delete_area_files($context->id, 'mod_videomarker');
    }
    videomarker_grade_item_delete($activity);
    return true;
}

/**
 * Serve protected uploaded videos and poster images.
 *
 * @param stdClass $course Course record.
 * @param stdClass $cm Course module record.
 * @param context $context Module context.
 * @param string $filearea File area.
 * @param array $args File path arguments.
 * @param bool $forcedownload Whether to force download.
 * @param array $options File serving options.
 * @return bool
 */
function mod_videomarker_pluginfile($course, $cm, $context, string $filearea, array $args,
                                    bool $forcedownload, array $options = []): bool {
    if ($context->contextlevel !== CONTEXT_MODULE || !in_array($filearea, ['intro', 'video', 'poster'], true)) {
        return false;
    }
    require_login($course, true, $cm);
    require_capability('mod/videomarker:view', $context);
    $itemid = (int)array_shift($args);
    if ($itemid !== 0) {
        return false;
    }
    $filename = array_pop($args);
    $filepath = '/' . ($args ? implode('/', $args) . '/' : '');
    $file = get_file_storage()->get_file(
        $context->id,
        'mod_videomarker',
        $filearea,
        0,
        $filepath,
        $filename
    );
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * File areas visible to Moodle file browsing.
 *
 * @param stdClass $course Course record.
 * @param stdClass $cm Course module record.
 * @param context $context Module context.
 * @return array
 */
function videomarker_get_file_areas($course, $cm, $context): array {
    return [
        'video' => get_string('videofile', 'videomarker'),
        'poster' => get_string('poster', 'videomarker'),
    ];
}

/**
 * Create or update the grade item.
 *
 * @param stdClass $activity Activity record.
 * @param array|stdClass|null $grades Optional grades.
 * @return int
 */
function videomarker_grade_item_update(stdClass $activity, array|stdClass|null $grades = null): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    $item = [
        'itemname' => clean_param($activity->name, PARAM_NOTAGS),
        'gradetype' => GRADE_TYPE_VALUE,
        'grademin' => 0,
        'grademax' => max(0, (float)$activity->grade),
    ];
    if ((float)$activity->grade <= 0) {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }
    return grade_update('mod/videomarker', $activity->course, 'mod', 'videomarker', $activity->id, 0, $grades, $item);
}

/**
 * Delete the grade item.
 *
 * @param stdClass $activity Activity record.
 * @return int
 */
function videomarker_grade_item_delete(stdClass $activity): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    return grade_update(
        'mod/videomarker',
        $activity->course,
        'mod',
        'videomarker',
        $activity->id,
        0,
        null,
        ['deleted' => 1]
    );
}

/**
 * Return grade records calculated from each question's best attempt.
 *
 * @param stdClass $activity Activity record.
 * @param int $userid Optional user id.
 * @return array Grade records keyed by user id.
 */
function videomarker_get_user_grades(stdClass $activity, int $userid = 0): array {
    global $DB;
    $params = ['activityid' => $activity->id];
    $where = 'videomarkerid = :activityid';
    if ($userid > 0) {
        $where .= ' AND userid = :userid';
        $params['userid'] = $userid;
    }
    $userids = $DB->get_fieldset_select('videomarker_attempts', 'DISTINCT userid', $where, $params);
    $grades = [];
    foreach ($userids as $id) {
        $calculated = grade_manager::calculate($activity, (int)$id);
        if ($calculated['rawgrade'] !== null) {
            $grades[(int)$id] = (object)[
                'userid' => (int)$id,
                'rawgrade' => $calculated['rawgrade'],
            ];
        }
    }
    return $grades;
}

/**
 * Update all or one user's grade.
 *
 * @param stdClass $activity Activity record.
 * @param int $userid User ID, or 0 for all users.
 * @param bool $nullifnone Ignored Moodle callback compatibility flag.
 * @return void
 */
function videomarker_update_grades(stdClass $activity, int $userid = 0, bool $nullifnone = true): void {
    global $DB;
    if ($userid > 0) {
        grade_manager::update_user_grade($activity, $userid);
        return;
    }
    $userids = $DB->get_fieldset_select('videomarker_attempts', 'DISTINCT userid', 'videomarkerid = :id', [
        'id' => $activity->id,
    ]);
    foreach ($userids as $id) {
        grade_manager::update_user_grade($activity, (int)$id);
    }
}

/**
 * Provide lightweight course module information for course listings.
 *
 * @param stdClass $coursemodule Course module database record.
 * @return cached_cm_info|null
 */
function videomarker_get_coursemodule_info($coursemodule): ?cached_cm_info {
    global $DB;
    $activity = $DB->get_record('videomarker', ['id' => $coursemodule->instance], 'id,name', IGNORE_MISSING);
    if (!$activity) {
        return null;
    }
    $info = new cached_cm_info();
    $info->name = $activity->name;
    return $info;
}

/**
 * Reset user data for this activity type.
 *
 * @param stdClass $data Reset request.
 * @return array Status messages.
 */
function videomarker_reset_userdata(stdClass $data): array {
    global $DB;
    $status = [];
    if (!empty($data->reset_videomarker_attempts)) {
        $activityids = $DB->get_fieldset_select('videomarker', 'id', 'course = :course', ['course' => $data->courseid]);
        if ($activityids) {
            [$insql, $params] = $DB->get_in_or_equal($activityids, SQL_PARAMS_NAMED, 'activity');
            $attemptids = $DB->get_fieldset_select('videomarker_attempts', 'id', "videomarkerid {$insql}", $params);
            if ($attemptids) {
                [$attemptsql, $attemptparams] = $DB->get_in_or_equal($attemptids, SQL_PARAMS_NAMED, 'attempt');
                $DB->delete_records_select('videomarker_marks', "attemptid {$attemptsql}", $attemptparams);
            }
            $DB->delete_records_select('videomarker_attempts', "videomarkerid {$insql}", $params);
            $DB->delete_records_select('videomarker_progress', "videomarkerid {$insql}", $params);
        }
        $status[] = [
            'component' => get_string('modulenameplural', 'videomarker'),
            'item' => get_string('resetuserdata', 'videomarker'),
            'error' => false,
        ];
    }
    return $status;
}

/**
 * Add reset options for Video Marker user data.
 *
 * @param MoodleQuickForm $mform Reset form.
 * @return void
 */
function videomarker_reset_course_form_definition(&$mform): void {
    $mform->addElement('html', '<h3>' . get_string('modulenameplural', 'videomarker') . '</h3>');
    $mform->addElement('advcheckbox', 'reset_videomarker_attempts', get_string('resetuserdata', 'videomarker'));
}

/**
 * Apply defaults to the reset form.
 *
 * @param stdClass $course Course record.
 * @return array
 */
function videomarker_reset_course_form_defaults($course): array {
    return ['reset_videomarker_attempts' => 0];
}
