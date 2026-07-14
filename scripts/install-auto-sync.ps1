[CmdletBinding()]
param(
    [int]$IntervalMinutes = 15
)

$ErrorActionPreference = 'Stop'
if ($IntervalMinutes -lt 5) { throw 'Das Intervall muss mindestens 5 Minuten betragen.' }

$scriptPath = Join-Path $PSScriptRoot 'sync-git-projects.ps1'
$action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$scriptPath`""
$trigger = New-ScheduledTaskTrigger -Daily -At '00:00'
$trigger.Repetition.Interval = "PT$IntervalMinutes`M"
$trigger.Repetition.Duration = 'P1D'
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -MultipleInstances IgnoreNew
Register-ScheduledTask -TaskName 'Codex Git Auto-Sync' -Action $action -Trigger $trigger -Settings $settings -Description 'Synchronisiert Codex-Projekte mit GitHub.' -Force | Out-Null
Write-Host "Auto-Sync eingerichtet: alle $IntervalMinutes Minuten." -ForegroundColor Green
