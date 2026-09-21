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
 * restore_videomarker_stepslib.php
 *
 * @package   mod_videomarker
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restore structure for Video Marker.
 */
class restore_videomarker_activity_structure_step extends restore_activity_structure_step {
    /**
     * Defines restore paths.
     */
    protected function define_structure(): array {
        $paths = [];
        $paths[] = new restore_path_element('videomarker', '/activity/videomarker');
        $paths[] = new restore_path_element('videomarker_question', '/activity/videomarker/questions/question');
        $paths[] = new restore_path_element('videomarker_target', '/activity/videomarker/questions/question/targets/target');
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('videomarker_attempt',
                '/activity/videomarker/questions/question/attempts/attempt');
            $paths[] = new restore_path_element('videomarker_mark',
                '/activity/videomarker/questions/question/attempts/attempt/marks/mark');
            $paths[] = new restore_path_element('videomarker_progress',
                '/activity/videomarker/progresses/progress');
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restores activity record.
     */
    protected function process_videomarker($data): void {
        global $DB;
        $data = (object)$data;
        $data->course = $this->get_courseid();
        $newitemid = $DB->insert_record('videomarker', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restores question.
     */
    protected function process_videomarker_question($data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->videomarkerid = $this->get_new_parentid('videomarker');
        $newitemid = $DB->insert_record('videomarker_questions', $data);
        $this->set_mapping('videomarker_question', $oldid, $newitemid);
    }

    /**
     * Restores expected target.
     */
    protected function process_videomarker_target($data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->questionid = $this->get_new_parentid('videomarker_question');
        $newitemid = $DB->insert_record('videomarker_targets', $data);
        $this->set_mapping('videomarker_target', $oldid, $newitemid);
    }

    /**
     * Restores attempt.
     */
    protected function process_videomarker_attempt($data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->videomarkerid = $this->get_new_parentid('videomarker');
        $data->questionid = $this->get_new_parentid('videomarker_question');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $newitemid = $DB->insert_record('videomarker_attempts', $data);
        $this->set_mapping('videomarker_attempt', $oldid, $newitemid);
    }

    /**
     * Restores one submitted mark.
     */
    protected function process_videomarker_mark($data): void {
        global $DB;
        $data = (object)$data;
        $data->attemptid = $this->get_new_parentid('videomarker_attempt');
        if (!empty($data->targetid)) {
            $data->targetid = $this->get_mappingid('videomarker_target', $data->targetid);
        }
        $DB->insert_record('videomarker_marks', $data);
    }

    /**
     * Restores user progress.
     */
    protected function process_videomarker_progress($data): void {
        global $DB;
        $data = (object)$data;
        $data->videomarkerid = $this->get_new_parentid('videomarker');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $DB->insert_record('videomarker_progress', $data);
    }

    /**
     * Restores activity files.
     */
    protected function after_execute(): void {
        $this->add_related_files('mod_videomarker', 'intro', null);
        $this->add_related_files('mod_videomarker', 'video', null);
        $this->add_related_files('mod_videomarker', 'poster', null);
    }
}
