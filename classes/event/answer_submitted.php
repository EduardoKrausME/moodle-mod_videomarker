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
 * answer_submitted.php
 *
 * @package   mod_videomarker
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videomarker\event;

/**
 * Event emitted when a student submits a marker answer.
 */
class answer_submitted extends \core\event\base {
    /**
     * Initialises event metadata.
     */
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'videomarker_attempts';
    }

    /** @return string Event name. */
    public static function get_name(): string {
        return get_string('eventanswersubmitted', 'videomarker');
    }

    /** @return string Event description. */
    public function get_description(): string {
        return "The user with id '{$this->userid}' submitted Video Marker question '{$this->other['questionid']}' " .
            "in course module '{$this->contextinstanceid}'.";
    }
}
