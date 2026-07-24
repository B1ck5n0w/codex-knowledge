[CmdletBinding()]
param(
    [int]$IntervalMinutes = 15
)

$ErrorActionPreference = 'Stop'
if ($IntervalMinutes -lt 5) { throw 'Das Intervall muss mindestens 5 Minuten betragen.' }

$gitTask = Get-ScheduledTask -TaskName 'Codex Git Auto-Sync' -ErrorAction SilentlyContinue
if ($gitTask) {
    Disable-ScheduledTask -TaskName 'Codex Git Auto-Sync' | Out-Null
}
$driveScriptPath = Join-Path $PSScriptRoot 'sync-drive-data.ps1'
$driveTaskCommand = "powershell.exe -NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$driveScriptPath`""
& schtasks.exe /Create /TN 'Codex Drive Data Backup' /TR $driveTaskCommand /SC HOURLY /MO 1 /F | Out-Null
if ($LASTEXITCODE -ne 0) { throw 'Die Drive-Sicherungsaufgabe konnte nicht angelegt werden.' }
Write-Host 'GitHub-Abgleich: durch Codex pro Arbeitssitzung; Drive-Datensicherung: stündlich.' -ForegroundColor Green
