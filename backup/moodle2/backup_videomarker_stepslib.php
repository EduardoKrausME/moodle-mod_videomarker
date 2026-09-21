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
 * backup_videomarker_stepslib.php
 *
 * @package   mod_videomarker
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Backup structure for Video Marker.
 */
class backup_videomarker_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines activity XML structure.
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $activity = new backup_nested_element('videomarker', ['id'], [
            'course', 'name', 'intro', 'introformat', 'videosource', 'videourl', 'resumeplayback', 'allowseek',
            'maxplaybackrate', 'disabledownload', 'completionpercent', 'completionmarkers', 'grade', 'timecreated', 'timemodified',
        ]);
        $questions = new backup_nested_element('questions');
        $question = new backup_nested_element('question', ['id'], [
            'sortorder', 'questiontext', 'questiontextformat', 'questiontype', 'points', 'required', 'allowretry',
            'showfeedback', 'tolerance', 'feedbackcorrect', 'feedbackincorrect', 'timecreated', 'timemodified',
        ]);
        $targets = new backup_nested_element('targets');
        $target = new backup_nested_element('target', ['id'], ['targetindex', 'starttime', 'endtime']);
        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt', ['id'], [
            'userid', 'attemptno', 'score', 'maxscore', 'correctcount', 'markcount', 'avgdistance', 'timecreated',
        ]);
        $marks = new backup_nested_element('marks');
        $mark = new backup_nested_element('mark', ['id'], [
            'markerindex', 'starttime', 'endtime', 'targetid', 'expectedstart',
            'expectedend', 'iscorrect', 'distance', 'timecreated',
        ]);
        $progresses = new backup_nested_element('progresses');
        $progress = new backup_nested_element('progress', ['id'], [
            'userid', 'duration', 'watchedranges', 'watchedseconds', 'percent', 'lastposition', 'timecreated', 'timemodified',
        ]);

        $activity->add_child($questions);
        $questions->add_child($question);
        $question->add_child($targets);
        $targets->add_child($target);
        $question->add_child($attempts);
        $attempts->add_child($attempt);
        $attempt->add_child($marks);
        $marks->add_child($mark);
        $activity->add_child($progresses);
        $progresses->add_child($progress);

        $activity->set_source_table('videomarker', ['id' => backup::VAR_ACTIVITYID]);
        $question->set_source_table('videomarker_questions', ['videomarkerid' => backup::VAR_PARENTID], 'sortorder ASC, id ASC');
        $target->set_source_table('videomarker_targets', ['questionid' => backup::VAR_PARENTID], 'targetindex ASC, id ASC');
        if ($userinfo) {
            $attempt->set_source_table('videomarker_attempts', ['questionid' => backup::VAR_PARENTID], 'attemptno ASC, id ASC');
            $mark->set_source_table('videomarker_marks', ['attemptid' => backup::VAR_PARENTID], 'markerindex ASC, id ASC');
            $progress->set_source_table('videomarker_progress', ['videomarkerid' => backup::VAR_ACTIVITYID]);
            $attempt->annotate_ids('user', 'userid');
            $progress->annotate_ids('user', 'userid');
        }

        $activity->annotate_files('mod_videomarker', 'intro', null);
        $activity->annotate_files('mod_videomarker', 'video', null);
        $activity->annotate_files('mod_videomarker', 'poster', null);

        return $this->prepare_activity_structure($activity);
    }
}
