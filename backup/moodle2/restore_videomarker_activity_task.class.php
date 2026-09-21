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
 * restore_videomarker_activity_task.class.php
 *
 * @package   mod_videomarker
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/videomarker/backup/moodle2/restore_videomarker_stepslib.php');

/**
 * Restore task for Video Marker.
 */
class restore_videomarker_activity_task extends restore_activity_task {
    /**
     * Defines activity-specific settings.
     */
    protected function define_my_settings(): void {
    }

    /**
     * Defines restore steps.
     */
    protected function define_my_steps(): void {
        $this->add_step(new restore_videomarker_activity_structure_step('videomarker_structure', 'videomarker.xml'));
    }

    /**
     * Defines decoded link rules.
     */
    public static function define_decode_contents(): array {
        return [new restore_decode_content('videomarker', ['intro'], 'videomarker')];
    }

    /**
     * Defines decoded link rules for activity URLs.
     */
    public static function define_decode_rules(): array {
        return [new restore_decode_rule('VIDEOMARKERVIEWBYID', '/mod/videomarker/view.php?id=$1', 'course_module')];
    }
}
