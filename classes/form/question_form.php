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
 * question_form.php
 *
 * @package   mod_videomarker
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videomarker\form;

use mod_videomarker\marker_manager;

/**
 * Form used by teachers to create and edit marker questions.
 */
class question_form extends \moodleform {
    /**
     * Defines form fields.
     */
    public function definition(): void {
        $mform = $this->_form;
        $cmid = (int)($this->_customdata['cmid'] ?? 0);

        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('editor', 'questiontext_editor', get_string('questiontext', 'videomarker'), null, [
            'maxfiles' => 0,
            'noclean' => false,
        ]);
        $mform->addRule('questiontext_editor', null, 'required', null, 'client');

        $mform->addElement('select', 'questiontype', get_string('questiontype', 'videomarker'), [
            'point' => get_string('questiontypepoint', 'videomarker'),
            'interval' => get_string('questiontypeinterval', 'videomarker'),
        ]);
        $mform->setDefault('questiontype', 'point');

        $mform->addElement('textarea', 'targets', get_string('targets', 'videomarker'), ['rows' => 6, 'cols' => 50]);
        $mform->addHelpButton('targets', 'targets', 'videomarker');
        $mform->addRule('targets', null, 'required', null, 'client');
        $mform->setType('targets', PARAM_RAW_TRIMMED);

        $mform->addElement('text', 'points', get_string('points', 'videomarker'), ['size' => 8]);
        $mform->setType('points', PARAM_FLOAT);
        $mform->setDefault('points', 1);
        $mform->addRule('points', null, 'numeric', null, 'client');

        $mform->addElement('advcheckbox', 'required', get_string('requiredquestion', 'videomarker'));
        $mform->setDefault('required', 1);
        $mform->addElement('advcheckbox', 'allowretry', get_string('allowretry', 'videomarker'));
        $mform->setDefault('allowretry', 1);
        $mform->addElement('advcheckbox', 'showfeedback', get_string('showfeedback', 'videomarker'));
        $mform->setDefault('showfeedback', 1);

        $mform->addElement('text', 'tolerance', get_string('tolerance', 'videomarker'), ['size' => 8]);
        $mform->setType('tolerance', PARAM_INT);
        $mform->setDefault('tolerance', 3);
        $mform->addHelpButton('tolerance', 'tolerance', 'videomarker');
        $mform->hideIf('tolerance', 'questiontype', 'neq', 'interval');

        $mform->addElement('textarea', 'feedbackcorrect',
            get_string('feedbackcorrect', 'videomarker'), ['rows' => 3, 'cols' => 60]);
        $mform->setType('feedbackcorrect', PARAM_TEXT);
        $mform->addElement('textarea', 'feedbackincorrect',
            get_string('feedbackincorrect', 'videomarker'), ['rows' => 3, 'cols' => 60]);
        $mform->setType('feedbackincorrect', PARAM_TEXT);

        $this->add_action_buttons(true);
    }

    /**
     * Validates target syntax and scoring options.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Validation errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $type = (string)($data['questiontype'] ?? 'point');
        $targets = marker_manager::parse_targets((string)($data['targets'] ?? ''), $type);
        if (!$targets) {
            $errors['targets'] = $type === 'interval'
                ? get_string('invalidintervaltargets', 'videomarker')
                : get_string('invalidtargets', 'videomarker');
        }
        if ((float)($data['points'] ?? 0) <= 0) {
            $errors['points'] = get_string('invalidpoints', 'videomarker');
        }
        $tolerance = (int)($data['tolerance'] ?? 0);
        if ($tolerance < 0 || $tolerance > 60) {
            $errors['tolerance'] = get_string('invalidtolerance', 'videomarker');
        }
        return $errors;
    }
}
