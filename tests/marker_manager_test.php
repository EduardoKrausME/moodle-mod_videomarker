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
 * marker_manager_test.php
 *
 * @package   mod_videomarker
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videomarker;

/**
 * Unit tests for timecode and target parsing.
 */
final class marker_manager_test extends \advanced_testcase {
    /**
     * Tests common video timecodes.
     */
    public function test_parse_timecode(): void {
        $this->assertSame(218.0, marker_manager::parse_timecode('03:38'));
        $this->assertSame(3723.0, marker_manager::parse_timecode('01:02:03'));
        $this->assertNull(marker_manager::parse_timecode('03:99'));
        $this->assertNull(marker_manager::parse_timecode('invalid'));
    }

    /**
     * Tests point windows and multiple expected markers.
     */
    public function test_parse_point_targets(): void {
        $targets = marker_manager::parse_targets("03:38-03:47\n08:10-08:20", 'point');
        $this->assertCount(2, $targets);
        $this->assertSame(218.0, $targets[0]['start']);
        $this->assertSame(227.0, $targets[0]['end']);
    }

    /**
     * Tests interval questions require a complete interval.
     */
    public function test_interval_requires_end(): void {
        $this->assertNull(marker_manager::parse_targets('08:10', 'interval'));
        $targets = marker_manager::parse_targets('08:10-08:45', 'interval');
        $this->assertCount(1, $targets);
        $this->assertSame(490.0, $targets[0]['start']);
        $this->assertSame(525.0, $targets[0]['end']);
    }
}
