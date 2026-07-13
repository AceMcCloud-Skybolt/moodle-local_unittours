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
 * Unit tours plugin.
 *
 * @package    local_unittours
 * @copyright  2026 Murdoch University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addstep'] = 'Add step';
$string['audience'] = 'Audience';
$string['audience_all'] = 'Everyone in the unit';
$string['audience_group'] = 'Selected groups';
$string['audience_staff'] = 'Teaching staff';
$string['audience_student'] = 'Students';
$string['audiencegroups'] = 'Audience groups';
$string['audiencegroups_help'] = 'When the audience is Selected groups, only users in one of these course groups will see the tour. Teaching staff can still preview group tours.';
$string['audioautoplay'] = 'Attempt auto-play audio';
$string['audioenabled'] = 'Enable audio';
$string['audiolang'] = 'Audio language code';
$string['audiolang_help'] = 'Optional BCP-47 language code such as en-AU. Leave blank to use browser defaults.';
$string['audioneedstext'] = 'Needs audio text';
$string['audiooff'] = 'Off';
$string['audioonbrowserdependent'] = 'On (browser-dependent)';
$string['audiostatus'] = 'Audio';
$string['audiotext'] = 'Audio text';
$string['audiotext_help'] = 'Text read aloud when the user presses Play audio. Keep this concise and direct.';
$string['audiounavailable'] = 'Audio is not available in this browser.';
$string['backdrop'] = 'Show backdrop';
$string['createtour'] = 'Create tour';
$string['deletestepconfirm'] = 'Are you sure you want to delete the step \'{$a}\'?';
$string['deletetourconfirm'] = 'Are you sure you want to delete the tour \'{$a}\'? This also deletes its steps and all completion records.';
$string['disabled'] = 'Disabled';
$string['done'] = 'Done';
$string['editstep'] = 'Edit step';
$string['edittour'] = 'Edit tour';
$string['enabled'] = 'Enabled';
$string['event_tour_completed'] = 'Unit tour completed';
$string['event_tour_skipped'] = 'Unit tour skipped';
$string['event_tour_started'] = 'Unit tour started';
$string['fallbackselector'] = 'Fallback CSS selector';
$string['fallbackselector_help'] = 'Optional CSS selector to use if a semantic target cannot yet be resolved. This is intended as an escape hatch, not the main authoring method.';
$string['local/unittours:manage'] = 'Create and manage unit tours';
$string['local/unittours:view'] = 'View unit tours';
$string['manageunittours'] = 'Manage unit tours';
$string['movedown'] = 'Move down';
$string['moveup'] = 'Move up';
$string['mycompletionreset'] = 'Your completion for this tour has been reset.';
$string['navtarget_activities'] = 'Activities';
$string['navtarget_course'] = 'Course';
$string['navtarget_grades'] = 'Grades';
$string['navtarget_more'] = 'More';
$string['navtarget_participants'] = 'Participants';
$string['navtarget_settings'] = 'Settings';
$string['nosteps'] = 'No steps have been created for this tour yet.';
$string['notours'] = 'No unit tours have been created yet.';
$string['picktarget'] = 'Pick a tour target';
$string['picktargetbutton'] = 'Pick target on course page';
$string['picktargetinstructions'] = 'Click an activity, section, block, navigation item, or page region to use it as this step target.';
$string['placement'] = 'Placement';
$string['placement_bottom'] = 'Bottom';
$string['placement_left'] = 'Left';
$string['placement_right'] = 'Right';
$string['placement_top'] = 'Top';
$string['playaudio'] = 'Play audio';
$string['pluginname'] = 'Unit tours';
$string['privacy:metadata'] = 'The Unit tours plugin stores tour completion state for users.';
$string['privacy:metadata:completion'] = 'Stores user completion state for unit tours.';
$string['privacy:metadata:completion:status'] = 'The completion status.';
$string['privacy:metadata:completion:timemodified'] = 'The time the completion state was last changed.';
$string['privacy:metadata:completion:tourid'] = 'The tour completed by the user.';
$string['privacy:metadata:completion:userid'] = 'The user whose completion state is stored.';
$string['resetmycompletion'] = 'Reset my completion';
$string['resettingshort'] = 'Resetting...';
$string['resettourcompletion'] = 'Reset completion';
$string['showiftargetmissing'] = 'Show if target is missing';
$string['showmode'] = 'Show mode';
$string['showmode_always'] = 'Show every visit';
$string['showmode_untilcomplete'] = 'Show until completed';
$string['showtour'] = 'Show unit tour';
$string['skip'] = 'Skip';
$string['status'] = 'Status';
$string['stepcontent'] = 'Step content';
$string['stepcounter'] = 'Step {$a->current} of {$a->total}';
$string['stepdeleted'] = 'Step deleted.';
$string['steporderupdated'] = 'Step order updated.';
$string['steps'] = 'Steps';
$string['steptitle'] = 'Step title';
$string['stepupdated'] = 'Step updated.';
$string['stopaudio'] = 'Stop audio';
$string['target_block'] = 'Block';
$string['target_course_module'] = 'Course activity or resource';
$string['target_course_navigation'] = 'Course navigation item';
$string['target_missing'] = 'The target could not be found in this unit.';
$string['target_missingref'] = 'This step does not have a target reference yet.';
$string['target_page_region'] = 'Page region';
$string['target_section'] = 'Course section';
$string['target_selector'] = 'CSS selector';
$string['target_selectorunchecked'] = 'CSS selectors are checked in the browser during playback.';
$string['target_unattached'] = 'Middle of page';
$string['target_unknown'] = 'Unknown target type';
$string['targetfound'] = 'Target found';
$string['targethealth'] = 'Health';
$string['targetlabel'] = 'Target';
$string['targetneedsattention'] = 'Needs attention';
$string['targetref'] = 'Target reference';
$string['targetref_help'] = 'For semantic targets, this will store the Moodle object reference, such as a course module id, section id, block name, navigation key, or page region key.';
$string['targettype'] = 'Target type';
$string['targetunchecked'] = 'Unchecked';
$string['tourcreated'] = 'Tour created.';
$string['tourdeleted'] = 'Tour deleted.';
$string['tourname'] = 'Tour';
$string['tourupdated'] = 'Tour updated.';
$string['unittours'] = 'Unit tours';
