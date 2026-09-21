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

use context_module;
use moodle_exception;
use moodle_url;
use stdClass;

/**
 * Video source handling shared by the form and player.
 *
 * @package   mod_videomarker
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class video_source {
    /**
     * Supported source options.
     *
     * @return array<string, string>
     */
    public static function options(): array {
        return [
            'upload' => get_string('sourceupload', 'videomarker'),
            'url' => get_string('sourceurl', 'videomarker'),
            'youtube' => get_string('sourceyoutube', 'videomarker'),
            'vimeo' => get_string('sourcevimeo', 'videomarker'),
        ];
    }

    /**
     * Validate a source value submitted by the activity form.
     *
     * @param string $source Source identifier.
     * @param string $url Submitted URL.
     * @return bool
     */
    public static function validate_url(string $source, string $url): bool {
        if ($source === 'upload') {
            return true;
        }
        try {
            self::normalise_url($source, $url);
            return true;
        } catch (moodle_exception $e) {
            return false;
        }
    }

    /**
     * Normalise and validate a source URL.
     *
     * @param string $source Source identifier.
     * @param string $url Submitted URL.
     * @return string
     * @throws moodle_exception
     */
    public static function normalise_url(string $source, string $url): string {
        $url = trim($url);
        if ($source === 'youtube') {
            return 'https://www.youtube.com/watch?v=' . self::extract_youtube_id($url);
        }
        if ($source === 'vimeo') {
            $config = self::extract_vimeo_config($url);
            return 'https://vimeo.com/' . $config['id'] . ($config['hash'] !== '' ? '/' . $config['hash'] : '');
        }
        if ($source === 'url') {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new moodle_exception('invalidvideourl', 'videomarker');
            }
            $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
            if (!in_array($scheme, ['http', 'https'], true)) {
                throw new moodle_exception('invalidvideourl', 'videomarker');
            }
            return $url;
        }
        if ($source !== 'upload') {
            throw new moodle_exception('sourceunsupported', 'videomarker');
        }
        return '';
    }

    /**
     * Save draft video and poster files into the activity context.
     *
     * @param stdClass $data Activity form data.
     * @param context_module $context Module context.
     * @return void
     */
    public static function save_files(stdClass $data, context_module $context): void {
        $fs = get_file_storage();
        if (($data->videosource ?? '') === 'upload') {
            if (!empty($data->videofile)) {
                file_save_draft_area_files(
                    (int)$data->videofile,
                    $context->id,
                    'mod_videomarker',
                    'video',
                    0,
                    ['subdirs' => 0, 'maxfiles' => 1]
                );
            }
        } else {
            $fs->delete_area_files($context->id, 'mod_videomarker', 'video', 0);
        }

        if (in_array(($data->videosource ?? ''), ['upload', 'url'], true)) {
            if (!empty($data->poster)) {
                file_save_draft_area_files(
                    (int)$data->poster,
                    $context->id,
                    'mod_videomarker',
                    'poster',
                    0,
                    ['subdirs' => 0, 'maxfiles' => 1]
                );
            }
        } else {
            $fs->delete_area_files($context->id, 'mod_videomarker', 'poster', 0);
        }
    }

    /**
     * Prepare file draft IDs when editing an existing activity.
     *
     * @param array $defaultvalues Form default values.
     * @param context_module $context Module context.
     * @return void
     */
    public static function prepare_form_data(array &$defaultvalues, context_module $context): void {
        $videodraftid = file_get_submitted_draft_itemid('videofile');
        file_prepare_draft_area(
            $videodraftid,
            $context->id,
            'mod_videomarker',
            'video',
            0,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
        $defaultvalues['videofile'] = $videodraftid;

        $posterdraftid = file_get_submitted_draft_itemid('poster');
        file_prepare_draft_area(
            $posterdraftid,
            $context->id,
            'mod_videomarker',
            'poster',
            0,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
        $defaultvalues['poster'] = $posterdraftid;
    }

    /**
     * Build browser-facing player configuration.
     *
     * @param stdClass $activity Activity record.
     * @param context_module $context Module context.
     * @return array<string, mixed>
     * @throws moodle_exception
     */
    public static function player_context(stdClass $activity, context_module $context): array {
        $source = (string)$activity->videosource;
        $config = [
            'source' => $source,
            'html5' => false,
            'youtube' => false,
            'vimeo' => false,
            'videourl' => '',
            'posterurl' => self::first_file_url($context, 'poster'),
            'youtubeid' => '',
            'vimeoembedurl' => '',
            'disabledownload' => !empty($activity->disabledownload),
        ];

        if ($source === 'upload') {
            $url = self::first_file_url($context, 'video');
            if ($url === '') {
                throw new moodle_exception('videofilemissing', 'videomarker');
            }
            $config['html5'] = true;
            $config['videourl'] = $url;
            return $config;
        }

        if ($source === 'url') {
            $config['html5'] = true;
            $config['videourl'] = self::normalise_url('url', (string)$activity->videourl);
            return $config;
        }

        if ($source === 'youtube') {
            $config['youtube'] = true;
            $config['youtubeid'] = self::extract_youtube_id((string)$activity->videourl);
            return $config;
        }

        if ($source === 'vimeo') {
            $vimeo = self::extract_vimeo_config((string)$activity->videourl);
            $query = ['dnt' => 1, 'title' => 0, 'byline' => 0, 'portrait' => 0];
            if ($vimeo['hash'] !== '') {
                $query['h'] = $vimeo['hash'];
            }
            $config['vimeo'] = true;
            $config['vimeoembedurl'] = (new moodle_url('https://player.vimeo.com/video/' . $vimeo['id'], $query))->out(false);
            return $config;
        }

        throw new moodle_exception('sourceunsupported', 'videomarker');
    }

    /**
     * Get a pluginfile URL for the first file in an area.
     *
     * @param context_module $context Module context.
     * @param string $filearea File area.
     * @return string
     */
    private static function first_file_url(context_module $context, string $filearea): string {
        $files = get_file_storage()->get_area_files(
            $context->id,
            'mod_videomarker',
            $filearea,
            0,
            'itemid, filepath, filename',
            false
        );
        if (!$files) {
            return '';
        }
        $file = reset($files);
        return moodle_url::make_pluginfile_url(
            $context->id,
            'mod_videomarker',
            $filearea,
            0,
            $file->get_filepath(),
            $file->get_filename()
        )->out(false);
    }

    /**
     * Extract a YouTube video ID.
     *
     * @param string $url YouTube URL.
     * @return string
     * @throws moodle_exception
     */
    public static function extract_youtube_id(string $url): string {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new moodle_exception('invalidvideourl', 'videomarker');
        }
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        $path = trim((string)parse_url($url, PHP_URL_PATH), '/');
        $id = '';
        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $id = explode('/', $path)[0] ?? '';
        } else if (in_array($host, [
            'youtube.com', 'www.youtube.com', 'm.youtube.com',
            'youtube-nocookie.com', 'www.youtube-nocookie.com',
        ], true)) {
            parse_str((string)parse_url($url, PHP_URL_QUERY), $query);
            $id = (string)($query['v'] ?? '');
            if ($id === '' && preg_match('~(?:embed|shorts)/([A-Za-z0-9_-]{6,20})~', $path, $match)) {
                $id = $match[1];
            }
        }
        if (!preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id)) {
            throw new moodle_exception('invalidvideourl', 'videomarker');
        }
        return $id;
    }

    /**
     * Extract a Vimeo video ID and optional unlisted privacy hash.
     *
     * @param string $url Vimeo URL.
     * @return array{id: string, hash: string}
     * @throws moodle_exception
     */
    public static function extract_vimeo_config(string $url): array {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new moodle_exception('invalidvideourl', 'videomarker');
        }
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        if (!in_array($host, ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], true)) {
            throw new moodle_exception('invalidvideourl', 'videomarker');
        }
        $path = trim((string)parse_url($url, PHP_URL_PATH), '/');
        if (!preg_match('~^(?:video/)?(\d+)(?:/([A-Za-z0-9]+))?$~', $path, $match)) {
            throw new moodle_exception('invalidvideourl', 'videomarker');
        }
        parse_str((string)parse_url($url, PHP_URL_QUERY), $query);
        $hash = (string)($match[2] ?? '');
        if ($hash === '' && !empty($query['h']) && preg_match('/^[A-Za-z0-9]+$/', (string)$query['h'])) {
            $hash = (string)$query['h'];
        }
        return ['id' => $match[1], 'hash' => $hash];
    }
}
