[CmdletBinding()]
param(
    [string]$DocumentsRoot = (Split-Path -Parent (Split-Path -Parent $PSScriptRoot))
)

$ErrorActionPreference = 'Stop'

function Initialize-GitHubCredentials {
    # Git Credential Manager opens the GitHub sign-in flow when no credential
    # is present. GitHub passwords must never be entered at a Git prompt.
    & git credential-manager configure
    if ($LASTEXITCODE -ne 0) { throw 'Git Credential Manager konnte nicht eingerichtet werden.' }
}

function Get-OrCloneProject {
    param(
        [string]$Repository,
        [string]$Target
    )

    if (Test-Path -LiteralPath (Join-Path $Target '.git')) {
        Write-Host "Bereits vorhanden: $Target" -ForegroundColor Yellow
        return
    }
    if (Test-Path -LiteralPath $Target) {
        $items = Get-ChildItem -LiteralPath $Target -Force
        if ($items.Count -gt 0) {
            throw "Zielordner ist nicht leer und kein Git-Repository: $Target"
        }
    }

    $parent = Split-Path -Parent $Target
    New-Item -ItemType Directory -Path $parent -Force | Out-Null
    git clone $Repository $Target
    if ($LASTEXITCODE -ne 0) { throw "Klonen fehlgeschlagen: $Repository" }
}

Initialize-GitHubCredentials

 $config = Get-Content -Raw -LiteralPath (Join-Path $DocumentsRoot 'Chris Derix Privat\sync-projects.json') | ConvertFrom-Json
foreach ($projectDefinition in $config.projects) {
    if ($projectDefinition -is [string] -or [string]::IsNullOrWhiteSpace($projectDefinition.repository)) { continue }
    $entry = $projectDefinition.path
    $target = if ($entry.StartsWith('drive:', [StringComparison]::OrdinalIgnoreCase)) {
        $driveRelativePath = $entry.Substring(6)
        Get-PSDrive -PSProvider FileSystem |
            ForEach-Object { Join-Path $_.Root $driveRelativePath } |
            Where-Object { Test-Path -LiteralPath $_ } |
            Select-Object -First 1
    } elseif ([IO.Path]::IsPathRooted($entry)) {
        [IO.Path]::GetFullPath($entry)
    } else {
        [IO.Path]::GetFullPath((Join-Path $DocumentsRoot 'Chris Derix Privat' $entry))
    }
    if ([string]::IsNullOrWhiteSpace($target)) {
        Write-Warning "Projektstandort nicht verfügbar: $entry"
        continue
    }
    Get-OrCloneProject -Repository $projectDefinition.repository -Target $target
}

& (Join-Path $PSScriptRoot 'install-auto-sync.ps1') -IntervalMinutes 15
Write-Host 'Notebook-Einrichtung abgeschlossen.' -ForegroundColor Green
