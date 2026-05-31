param(
    [int]$SourceCourseId = 2,
    [int]$TargetCategoryId = 1,
    [string]$Workspace = "C:\Users\Media Studio\Documents\MoodleDev",
    [string]$MoodleRoot = "D:\server\moodle",
    [string]$PhpExe = "D:\server\php\php.exe"
)

$ErrorActionPreference = "Stop"

$backupDir = Join-Path $Workspace "backup-smoke"
if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir | Out-Null
}

Write-Host "Running backup for course $SourceCourseId..."
& $PhpExe "$MoodleRoot\admin\cli\backup.php" --courseid=$SourceCourseId --destination="$backupDir"

$mbz = Get-ChildItem $backupDir -Filter *.mbz | Sort-Object LastWriteTime -Descending | Select-Object -First 1
if (-not $mbz) {
    throw "No backup file created."
}

Write-Host "Restoring $($mbz.FullName) into category $TargetCategoryId..."
& $PhpExe "$MoodleRoot\admin\cli\restore_backup.php" --file="$($mbz.FullName)" --categoryid=$TargetCategoryId

$checkScript = @'
<?php
define('CLI_SCRIPT', true);
require_once('D:/server/moodle/config.php');
global $DB;
$newcourseid = (int)$DB->get_field('course', 'MAX(id)', []);
$tour = $DB->get_record('local_unittours_tours', ['courseid' => $newcourseid], '*', IGNORE_MISSING);
if (!$tour) {
    echo "No restored unit tours found in course {$newcourseid}\n";
    exit(2);
}
$step = $DB->get_record('local_unittours_steps', ['tourid' => $tour->id], '*', IGNORE_MISSING);
if (!$step) {
    echo "No restored unit tour steps found in course {$newcourseid}\n";
    exit(3);
}
echo "Restored course={$newcourseid}\n";
echo "tour={$tour->id} name={$tour->name}\n";
echo "step={$step->id} type={$step->targettype} ref={$step->targetref} fallback={$step->fallbackselector}\n";
?>
'@

$tmpFile = Join-Path $Workspace "temp-unittours-smoke-check.php"
$checkScript | Set-Content -Path $tmpFile -Encoding ASCII
& $PhpExe $tmpFile
Remove-Item $tmpFile -Force

Write-Host "Smoke test completed."
