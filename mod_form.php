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

use mod_videomarker\video_source;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Main activity settings form for Video Marker.
 *
 * @package   mod_videomarker
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_videomarker_mod_form extends moodleform_mod {
    /**
     * Define form fields.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('videomarkername', 'videomarker'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements();

        $mform->addElement('html', '<h3>' . get_string('sourceheader', 'videomarker') . '</h3>');
        $mform->addElement('select', 'videosource', get_string('videosource', 'videomarker'), video_source::options());
        $mform->setDefault('videosource', 'url');
        $mform->setType('videosource', PARAM_ALPHA);

        $mform->addElement(
            'url',
            'videourl',
            get_string('videourl', 'videomarker'),
            ['size' => 80],
            ['usefilepicker' => false]
        );
        $mform->setType('videourl', PARAM_URL);
        $mform->addHelpButton('videourl', 'videourl', 'videomarker');
        $mform->hideIf('videourl', 'videosource', 'eq', 'upload');

        $mform->addElement('filemanager', 'videofile', get_string('videofile', 'videomarker'), null, [
            'subdirs' => 0,
            'accepted_types' => ['video'],
        ]);
        $mform->hideIf('videofile', 'videosource', 'neq', 'upload');

        $mform->addElement('filemanager', 'poster', get_string('poster', 'videomarker'), null, [
            'subdirs' => 0,
            'accepted_types' => ['image'],
        ]);
        $mform->hideIf('poster', 'videosource', 'in', ['youtube', 'vimeo']);

        $mform->addElement('html', '<h3>' . get_string('playbackheader', 'videomarker') . '</h3>');
        $mform->addElement('select', 'resumeplayback', get_string('resumeplayback', 'videomarker'), [
            1 => get_string('resumeautomatic', 'videomarker'),
            0 => get_string('resumefromstart', 'videomarker'),
        ]);
        $mform->setDefault('resumeplayback', 1);
        $mform->addElement('selectyesno', 'allowseek', get_string('allowseek', 'videomarker'));
        $mform->setDefault('allowseek', 1);
        $mform->addElement('select', 'maxplaybackrate', get_string('maxplaybackrate', 'videomarker'), [
            '1' => '1x',
            '1.25' => '1.25x',
            '1.5' => '1.5x',
            '1.75' => '1.75x',
            '2' => '2x',
        ]);
        $mform->setDefault('maxplaybackrate', '2');
        $mform->addElement('selectyesno', 'disabledownload', get_string('disabledownload', 'videomarker'));
        $mform->setDefault('disabledownload', 0);
        $mform->addElement('static', 'trackingnotice', '', get_string('trackingnotice', 'videomarker'));

        $this->standard_grading_coursemodule_elements();
        $mform->setDefault('grade', 100);
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Validate activity form data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $source = (string)($data['videosource'] ?? '');
        if ($source === 'upload') {
            $draftid = (int)($data['videofile'] ?? 0);
            $draftinfo = $draftid > 0 ? file_get_draft_area_info($draftid) : null;
            if (!$draftinfo || empty($draftinfo['filecount'])) {
                $errors['videofile'] = get_string('videofilemissing', 'videomarker');
            }
        } else if (!video_source::validate_url($source, (string)($data['videourl'] ?? ''))) {
            $errors['videourl'] = get_string('invalidvideourl', 'videomarker');
        }
        $percentfield = $this->get_suffixed_name('completionpercent');
        if (isset($data[$percentfield])) {
            $percent = (int)$data[$percentfield];
            if ($percent < 1 || $percent > 100) {
                $errors[$percentfield] = get_string('errorpercent', 'videomarker');
            }
        }
        foreach (['videofile', 'poster'] as $field) {
            $draftid = (int)($data[$field] ?? 0);
            if ($draftid > 0) {
                $draftinfo = file_get_draft_area_info($draftid);
                if ((int)$draftinfo['filecount'] > 1) {
                    $errors[$field] = get_string('errormaxfiles', 'videomarker');
                }
            }
        }
        return $errors;
    }

    /**
     * Prepare stored files and completion settings for edit mode.
     *
     * @param array $defaultvalues Form values.
     * @return void
     */
    public function data_preprocessing(&$defaultvalues): void {
        foreach (['completionpercent', 'completionmarkers'] as $field) {
            if (array_key_exists($field, $defaultvalues)) {
                $defaultvalues[$this->get_suffixed_name($field)] = $defaultvalues[$field];
            }
        }
        if (!empty($this->current->instance)) {
            video_source::prepare_form_data($defaultvalues, $this->context);
        }
    }

    /**
     * Add custom completion rules.
     *
     * @return array
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;
        $percentfield = $this->get_suffixed_name('completionpercent');
        $markersfield = $this->get_suffixed_name('completionmarkers');
        $mform->addElement('text', $percentfield, get_string('completionpercent', 'videomarker'), ['size' => 5]);
        $mform->setType($percentfield, PARAM_INT);
        $mform->setDefault($percentfield, 80);
        $mform->addHelpButton($percentfield, 'completionpercent', 'videomarker');
        $mform->addRule($percentfield, null, 'numeric', null, 'client');
        $mform->addElement('selectyesno', $markersfield, get_string('completionmarkers', 'videomarker'));
        $mform->setDefault($markersfield, 1);
        $mform->addHelpButton($markersfield, 'completionmarkers', 'videomarker');
        return [$percentfield, $markersfield];
    }

    /**
     * Determine if custom completion rules are enabled.
     *
     * @param array $data Submitted data.
     * @return bool
     */
    public function completion_rule_enabled($data): bool {
        return !empty($data[$this->get_suffixed_name('completionpercent')])
            || !empty($data[$this->get_suffixed_name('completionmarkers')]);
    }

    /**
     * Return submitted data using unsuffixed database field names.
     *
     * @return stdClass|false
     */
    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return $data;
        }
        foreach (['completionpercent', 'completionmarkers'] as $field) {
            $suffixed = $this->get_suffixed_name($field);
            if (property_exists($data, $suffixed)) {
                $data->{$field} = $data->{$suffixed};
                unset($data->{$suffixed});
            }
        }
        return $data;
    }

    /**
     * Build a completion rule field name unique to this module.
     *
     * @param string $field Base field.
     * @return string
     */
    private function get_suffixed_name(string $field): string {
        return $field . '_videomarker';
    }
}
