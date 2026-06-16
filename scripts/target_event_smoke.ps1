param(
    [int]$CourseId = 2,
    [int]$UserId = 2,
    [string]$Workspace = "C:\Users\Media Studio\Documents\MoodleDev",
    [string]$MoodleRoot = "D:\server\moodle",
    [string]$PhpExe = "D:\server\php\php.exe"
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path (Join-Path $MoodleRoot "config.php"))) {
    throw "Moodle config.php was not found under $MoodleRoot."
}

if (-not (Test-Path $PhpExe)) {
    throw "PHP executable was not found at $PhpExe."
}

$checkScript = @'
<?php
define('CLI_SCRIPT', true);

[$script, $moodleroot, $courseid, $userid, $statefile] = $argv;
$courseid = (int) $courseid;
$userid = (int) $userid;

require_once($moodleroot . '/config.php');

global $DB;

$course = get_course($courseid);
$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
\core\session\manager::set_user($user);

$context = \context_course::instance($courseid);
$repo = \local_unittours\local\tour_repository::class;
$target = \local_unittours\local\target::class;
$resolver = \local_unittours\local\target_resolver::class;
$tourid = null;
$eventnames = [
    '\local_unittours\event\tour_started',
    '\local_unittours\event\tour_skipped',
    '\local_unittours\event\tour_completed',
];

function smoke_fail(string $message): void {
    echo "FAIL: {$message}\n";
    exit(2);
}

function smoke_assert($condition, string $message): void {
    if (!$condition) {
        smoke_fail($message);
    }
}

function smoke_write_state(string $statefile, array $state): void {
    file_put_contents($statefile, json_encode($state, JSON_PRETTY_PRINT));
}

function smoke_step_data(int $tourid, string $title, string $targettype, ?string $targetref, ?string $fallbackselector = null): \stdClass {
    return (object) [
        'tourid' => $tourid,
        'title' => $title,
        'content' => [
            'text' => '<p>' . s($title) . '</p>',
            'format' => FORMAT_HTML,
        ],
        'targettype' => $targettype,
        'targetref' => $targetref,
        'fallbackselector' => $fallbackselector,
        'placement' => 'bottom',
        'showiftargetmissing' => 1,
        'backdrop' => 0,
        'audioenabled' => 0,
        'audioautoplay' => 0,
        'audiotext' => '',
        'audiolang' => '',
    ];
}

$tourid = $repo::save_tour((object) [
        'name' => 'Smoke test tour ' . time(),
        'description' => [
            'text' => '<p>Temporary smoke test tour.</p>',
            'format' => FORMAT_HTML,
        ],
        'enabled' => 1,
        'audience' => 'all',
        'showmode' => 'untilcomplete',
], $courseid);
    smoke_write_state($statefile, [
        'courseid' => $courseid,
        'userid' => $userid,
        'tourid' => $tourid,
        'before' => [],
        'eventsready' => false,
    ]);

    $modinfo = get_fast_modinfo($course);
    $cm = null;
    foreach ($modinfo->get_cms() as $candidate) {
        if (!$candidate->deletioninprogress) {
            $cm = $candidate;
            break;
        }
    }
    smoke_assert($cm !== null, 'Course has no activity/resource to test course_module targeting.');

    $sectioninfo = null;
    foreach ($modinfo->get_section_info_all() as $candidate) {
        if (!empty($candidate->id) && (int) $candidate->section > 0) {
            $sectioninfo = $candidate;
            break;
        }
    }
    smoke_assert($sectioninfo !== null, 'Course has no section to test section targeting.');

    $block = $DB->get_record('block_instances', ['parentcontextid' => $context->id], '*', IGNORE_MULTIPLE);

    $steps = [];
    $steps['activity'] = $repo::get_step($repo::save_step(
        smoke_step_data($tourid, 'Smoke activity target', $target::COURSE_MODULE, (string) $cm->id),
        $courseid
    ), $courseid);
    $steps['section'] = $repo::get_step($repo::save_step(
        smoke_step_data($tourid, 'Smoke section target', $target::SECTION, (string) $sectioninfo->id),
        $courseid
    ), $courseid);
    $steps['navigation'] = $repo::get_step($repo::save_step(
        smoke_step_data($tourid, 'Smoke navigation target', $target::COURSE_NAVIGATION, 'grades'),
        $courseid
    ), $courseid);
    $steps['missing'] = $repo::get_step($repo::save_step(
        smoke_step_data($tourid, 'Smoke missing target', $target::COURSE_MODULE, '999999999'),
        $courseid
    ), $courseid);

    if ($block) {
        $steps['block'] = $repo::get_step($repo::save_step(
            smoke_step_data($tourid, 'Smoke block target', $target::BLOCK, $block->blockname),
            $courseid
        ), $courseid);
    }

    smoke_assert($resolver::describe($steps['activity'], $course)->found === true, 'Activity target did not resolve.');
    smoke_assert($resolver::describe($steps['section'], $course)->found === true, 'Section target did not resolve.');
    smoke_assert($resolver::describe($steps['navigation'], $course)->found === true, 'Navigation target did not resolve.');
    smoke_assert($resolver::describe($steps['missing'], $course)->found === false, 'Missing target unexpectedly resolved.');

    if (isset($steps['block'])) {
        smoke_assert($resolver::describe($steps['block'], $course)->found === true, 'Block target did not resolve.');
    } else {
        echo "SKIP: No course-level block instance found for block target resolution.\n";
    }

    $before = [];
    foreach ($eventnames as $eventname) {
        $before[$eventname] = $DB->count_records('logstore_standard_log', [
            'component' => 'local_unittours',
            'courseid' => $courseid,
            'userid' => $userid,
            'eventname' => $eventname,
        ]);
    }
    smoke_write_state($statefile, [
        'courseid' => $courseid,
        'userid' => $userid,
        'tourid' => $tourid,
        'before' => $before,
        'eventsready' => false,
    ]);

    $repo::mark_started($tourid, $courseid, $userid);
    $repo::mark_completion($tourid, $courseid, $userid, 'skipped');
    $repo::mark_completion($tourid, $courseid, $userid, 'complete');

    $completion = $DB->get_record('local_unittours_completion', ['tourid' => $tourid, 'userid' => $userid], '*', MUST_EXIST);
    smoke_assert($completion->status === 'complete', 'Completion status was not updated to complete.');
    smoke_write_state($statefile, [
        'courseid' => $courseid,
        'userid' => $userid,
        'tourid' => $tourid,
        'before' => $before,
        'eventsready' => true,
    ]);

echo "PASS: Target resolver smoke completed; event log verification pending shutdown for tour {$tourid}.\n";
'@

$verifyScript = @'
<?php
define('CLI_SCRIPT', true);

[$script, $moodleroot, $statefile] = $argv;
require_once($moodleroot . '/config.php');

global $DB;

$repo = \local_unittours\local\tour_repository::class;
$state = file_exists($statefile) ? json_decode(file_get_contents($statefile), true) : null;
if (!$state || empty($state['tourid'])) {
    echo "No smoke state found; nothing to verify or clean.\n";
    exit(2);
}

$courseid = (int) $state['courseid'];
$userid = (int) $state['userid'];
$tourid = (int) $state['tourid'];

function smoke_fail(string $message): void {
    echo "FAIL: {$message}\n";
    exit(2);
}

try {
    if (!empty($state['eventsready'])) {
        foreach ($state['before'] as $eventname => $before) {
            $after = $DB->count_records('logstore_standard_log', [
                'component' => 'local_unittours',
                'courseid' => $courseid,
                'userid' => $userid,
                'eventname' => $eventname,
            ]);
            if ($after <= (int) $before) {
                smoke_fail("{$eventname} was not logged.");
            }
        }
        echo "PASS: Event log verification completed for tour {$tourid}.\n";
    } else {
        smoke_fail('Resolver smoke did not reach the event trigger phase.');
    }
} finally {
    if ($DB->record_exists('local_unittours_tours', ['id' => $tourid, 'courseid' => $courseid])) {
        $repo::delete_tour($tourid, $courseid);
        echo "Cleaned temporary tour {$tourid}.\n";
    }
}
'@

$tmpFile = Join-Path $Workspace "temp-unittours-target-event-smoke.php"
$verifyFile = Join-Path $Workspace "temp-unittours-target-event-verify.php"
$stateFile = Join-Path $Workspace "temp-unittours-target-event-state.json"
try {
    $checkScript | Set-Content -Path $tmpFile -Encoding ASCII
    $verifyScript | Set-Content -Path $verifyFile -Encoding ASCII
    if (Test-Path $stateFile) {
        Remove-Item $stateFile -Force
    }
    & $PhpExe $tmpFile $MoodleRoot $CourseId $UserId $stateFile
    $phaseExitCode = $LASTEXITCODE
    $verifyExitCode = 0
    if (Test-Path $stateFile) {
        & $PhpExe $verifyFile $MoodleRoot $stateFile
        $verifyExitCode = $LASTEXITCODE
    } else {
        $verifyExitCode = 2
    }
    if ($phaseExitCode -ne 0) {
        throw "Target/event smoke test failed with exit code $phaseExitCode."
    }
    if ($verifyExitCode -ne 0) {
        throw "Target/event smoke verification failed with exit code $verifyExitCode."
    }
} finally {
    if (Test-Path $tmpFile) {
        Remove-Item $tmpFile -Force
    }
    if (Test-Path $verifyFile) {
        Remove-Item $verifyFile -Force
    }
    if (Test-Path $stateFile) {
        Remove-Item $stateFile -Force
    }
}
