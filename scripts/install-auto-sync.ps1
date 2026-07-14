[CmdletBinding()]
param(
    [int]$IntervalMinutes = 15
)

$ErrorActionPreference = 'Stop'
if ($IntervalMinutes -lt 5) { throw 'Das Intervall muss mindestens 5 Minuten betragen.' }

$scriptPath = Join-Path $PSScriptRoot 'sync-git-projects.ps1'
$taskCommand = "powershell.exe -NoProfile -ExecutionPolicy Bypass -File `"$scriptPath`""
& schtasks.exe /Create /TN 'Codex Git Auto-Sync' /TR $taskCommand /SC MINUTE /MO $IntervalMinutes /F | Out-Null
if ($LASTEXITCODE -ne 0) { throw 'Die Windows-Aufgabe konnte nicht angelegt werden.' }
$driveScriptPath = Join-Path $PSScriptRoot 'sync-drive-data.ps1'
$driveTaskCommand = "powershell.exe -NoProfile -ExecutionPolicy Bypass -File `"$driveScriptPath`""
& schtasks.exe /Create /TN 'Codex Drive Data Backup' /TR $driveTaskCommand /SC HOURLY /MO 1 /F | Out-Null
if ($LASTEXITCODE -ne 0) { throw 'Die Drive-Sicherungsaufgabe konnte nicht angelegt werden.' }
Write-Host "GitHub-Abgleich: alle $IntervalMinutes Minuten; Drive-Datensicherung: stündlich." -ForegroundColor Green
