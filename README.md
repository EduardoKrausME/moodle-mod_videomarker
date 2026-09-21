# Video Marker — mod_videomarker

Video Marker is a Moodle activity for asking students to identify exact moments or intervals inside a video.

It is designed from the same concepts used by `mod_videoprogress`: protected upload support, URL/YouTube/Vimeo sources,
resume playback, real watched-time tracking, gradebook integration, custom completion, reports, and a timeline-oriented
student experience.

Teachers create point questions such as “Mark when the first security failure occurs” or interval questions such as
“Mark the complete segment where the incorrect procedure occurs”. Expected point answers can be correctness windows (for
example 03:38–03:47). Interval answers use expected start/end boundaries with a configurable tolerance. A question can
require one or several markers.

The activity records attempts, correct and incorrect markers, temporal distance from the expected target, grade, watched
percentage, and required-question completion. Retry and feedback behavior are configurable per question.

Supported video sources:

- Moodle protected upload;
- direct video URL;
- YouTube;
- Vimeo.

## Installation

Copy the `videomarker` directory to `mod/videomarker` and complete the Moodle upgrade process.

## Requirements

Moodle 4.5 or later.

## License

GNU GPL v3 or later.
