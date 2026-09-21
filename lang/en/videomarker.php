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
 * English strings for Video Marker.
 *
 * @package   mod_videomarker
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activitynotfound'] = 'The Video Marker activity could not be found.';
$string['addquestion'] = 'Add question';
$string['allowretry'] = 'Allow new attempts';
$string['allowseek'] = 'Allow seeking to unwatched positions';
$string['answercorrect'] = 'Correct';
$string['answerincorrect'] = 'Incorrect';
$string['answerlocked'] = 'This question does not allow another attempt.';
$string['answerpartial'] = 'Partially correct';
$string['attempt'] = 'Attempt {$a}';
$string['averagedistance'] = 'Average distance';
$string['backtoactivity'] = 'Back to activity';
$string['backtoreport'] = 'Back to report';
$string['completiondetail:markers'] = 'Submit all required marker questions';
$string['completiondetail:percent'] = 'Watch at least {$a}% of the video';
$string['completionmarkers'] = 'Require mandatory marker questions';
$string['completionmarkers_help'] = 'All questions marked as required must have at least one submitted attempt.';
$string['completionpercent'] = 'Require watched percentage';
$string['completionpercent_help'] = 'The student must actually watch at least this percentage of the video. Large seek jumps are not counted as watched time.';
$string['correct'] = 'Correct';
$string['deletequestion'] = 'Delete question';
$string['deletequestionconfirm'] = 'Delete this question and all of its student attempts?';
$string['disabledownload'] = 'Discourage video download';
$string['distance'] = 'Distance';
$string['downloadblockednotice'] = 'Download controls are hidden when supported by the browser. This is not DRM protection.';
$string['editquestion'] = 'Edit question';
$string['endinterval'] = 'End interval';
$string['errorpercent'] = 'Enter a percentage between 1 and 100.';
$string['eventanswersubmitted'] = 'Video Marker answer submitted';
$string['eventcoursemoduleviewed'] = 'Video Marker activity viewed';
$string['expected'] = 'Expected';
$string['feedback'] = 'Feedback';
$string['feedbackcorrect'] = 'Feedback when fully correct';
$string['feedbackincorrect'] = 'Feedback when partially correct or incorrect';
$string['grade'] = 'Grade';
$string['incorrect'] = 'Incorrect';
$string['interval'] = 'Interval';
$string['invalidintervaltargets'] = 'Interval questions require a start and an end time on every target line.';
$string['invalidmarkcount'] = 'Submit exactly the number of markers required by this question.';
$string['invalidmarkdata'] = 'One or more submitted markers are invalid.';
$string['invalidpoints'] = 'Points must be greater than zero.';
$string['invalidquestion'] = 'The requested marker question does not belong to this activity.';
$string['invalidtargets'] = 'Enter at least one valid target. Use MM:SS-MM:SS or HH:MM:SS-HH:MM:SS.';
$string['invalidtolerance'] = 'Tolerance must be between 0 and 60 seconds.';
$string['invalidvideourl'] = 'Enter a valid supported video URL.';
$string['lastaccess'] = 'Last activity';
$string['latestattempt'] = 'Latest attempt';
$string['managequestions'] = 'Manage questions';
$string['mark'] = 'Marker';
$string['markers'] = 'Markers';
$string['markersneeded'] = '{$a->current} of {$a->required} markers selected';
$string['markings'] = 'Markings';
$string['markmoment'] = 'Mark moment';
$string['maxplaybackrate'] = 'Maximum playback speed';
$string['modulename'] = 'Video Marker';
$string['modulenameplural'] = 'Video Markers';
$string['movedown'] = 'Move down';
$string['moveup'] = 'Move up';
$string['newattempt'] = 'New attempt';
$string['no'] = 'No';
$string['noattempts'] = 'No attempts yet';
$string['noquestions'] = 'No marker questions have been created yet.';
$string['notgraded'] = 'Not graded';
$string['overallgrade'] = 'Overall grade';
$string['playbackheader'] = 'Playback and tracking';
$string['pluginadministration'] = 'Video Marker administration';
$string['pluginname'] = 'Video Marker';
$string['points'] = 'Points';
$string['poster'] = 'Poster image';
$string['privacy:metadata:videomarker_attempts'] = 'Stores submitted Video Marker question attempts.';
$string['privacy:metadata:videomarker_attempts:questionid'] = 'The question answered by the user.';
$string['privacy:metadata:videomarker_attempts:score'] = 'The score awarded to the attempt.';
$string['privacy:metadata:videomarker_attempts:timecreated'] = 'The time when the attempt was submitted.';
$string['privacy:metadata:videomarker_attempts:userid'] = 'The user who submitted the attempt.';
$string['privacy:metadata:videomarker_marks'] = 'Stores the individual video timestamps and intervals submitted inside an attempt.';
$string['privacy:metadata:videomarker_marks:distance'] = 'The temporal distance from the expected target.';
$string['privacy:metadata:videomarker_marks:endtime'] = 'The optional marked end time in the video.';
$string['privacy:metadata:videomarker_marks:iscorrect'] = 'Whether the marker was inside the accepted target.';
$string['privacy:metadata:videomarker_marks:starttime'] = 'The marked start time in the video.';
$string['privacy:metadata:videomarker_progress'] = 'Stores video viewing progress for Video Marker activities.';
$string['privacy:metadata:videomarker_progress:lastposition'] = 'The last playback position used for resume.';
$string['privacy:metadata:videomarker_progress:percent'] = 'The percentage of the video actually watched.';
$string['privacy:metadata:videomarker_progress:timemodified'] = 'The time when progress was last updated.';
$string['privacy:metadata:videomarker_progress:userid'] = 'The user whose viewing progress is stored.';
$string['privacy:metadata:videomarker_progress:watchedranges'] = 'The watched time ranges used to calculate progress.';
$string['progress'] = 'Progress';
$string['questioncount'] = '{$a} question(s)';
$string['questionnotfound'] = 'The Video Marker question could not be found.';
$string['questionoptional'] = 'Optional';
$string['questionrequired'] = 'Required';
$string['questions'] = 'Marker questions';
$string['questiontext'] = 'Question';
$string['questiontype'] = 'Answer type';
$string['questiontypeinterval'] = 'Interval marker';
$string['questiontypepoint'] = 'Moment marker';
$string['removemarker'] = 'Remove';
$string['reportattempts'] = 'Attempt history';
$string['reports'] = 'Reports';
$string['reportstudent'] = 'Student details';
$string['requiredanswered'] = 'Required questions';
$string['requiredmarkers'] = '{$a} marker(s) required';
$string['requiredquestion'] = 'Required for completion';
$string['resetuserdata'] = 'Delete Video Marker attempts and viewing progress';
$string['resumeautomatic'] = 'Automatically resume from the last position';
$string['resumefromstart'] = 'Always start from the beginning';
$string['resumeplayback'] = 'Resume playback';
$string['retrynotallowed'] = 'Another attempt is not allowed for this question.';
$string['scorelabel'] = 'Score: {$a->score} / {$a->max}';
$string['seconds'] = '{$a} s';
$string['showfeedback'] = 'Show feedback after submission';
$string['sourceheader'] = 'Video source';
$string['sourceunsupported'] = 'Unsupported video source.';
$string['sourceupload'] = 'Upload to Moodle';
$string['sourceurl'] = 'Direct video URL';
$string['sourceurlhint'] = 'Use an HTTPS video URL whenever possible.';
$string['sourcevimeo'] = 'Vimeo';
$string['sourceyoutube'] = 'YouTube';
$string['startinterval'] = 'Start interval';
$string['student'] = 'Student';
$string['studentmark'] = 'Student marker';
$string['submitmarkers'] = 'Submit markers';
$string['targets'] = 'Expected target(s)';
$string['targets_help'] = 'Enter one expected target per line. For moment questions, use a correctness window such as 03:38-03:47. For interval questions, enter the expected interval such as 08:10-08:45.';
$string['targetsexampleinterval'] = 'Example:
08:10-08:45';
$string['targetsexamplepoint'] = 'Example:
03:38-03:47
08:12-08:20
14:00-14:08';
$string['timeformathelp'] = 'Accepted timecodes: MM:SS or HH:MM:SS.';
$string['timeline'] = 'Timeline';
$string['tolerance'] = 'Interval boundary tolerance (seconds)';
$string['tolerance_help'] = 'For interval questions, both the start and end must be within this many seconds of the expected boundaries.';
$string['trackingnotice'] = 'Progress is based on video ranges actually played. Large jumps are not counted as watched time.';
$string['videofile'] = 'Video file';
$string['videofilemissing'] = 'Upload a video file for this source.';
$string['videomarker:addinstance'] = 'Add a new Video Marker activity';
$string['videomarker:attempt'] = 'Submit Video Marker answers';
$string['videomarker:managequestions'] = 'Manage Video Marker questions';
$string['videomarker:view'] = 'View Video Marker activities';
$string['videomarker:viewreports'] = 'View Video Marker reports';
$string['videomarkername'] = 'Video Marker name';
$string['videosource'] = 'Video source';
$string['videourl'] = 'Video URL';
$string['videourl_help'] = 'For direct URL, enter a playable video address. For YouTube or Vimeo, enter the normal public or unlisted video URL.';
$string['viewreport'] = 'View report';
$string['watched'] = 'Watched';
$string['yes'] = 'Yes';
