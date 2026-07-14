[CmdletBinding()]
param(
    [string]$DocumentsRoot = (Split-Path -Parent (Split-Path -Parent $PSScriptRoot))
)

$ErrorActionPreference = 'Stop'

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

Get-OrCloneProject -Repository 'https://github.com/B1ck5n0w/sommer-party.git' -Target (Join-Path $DocumentsRoot 'Sommer Party')
Get-OrCloneProject -Repository 'https://github.com/B1ck5n0w/freizeitexperten-erp-dev.git' -Target (Join-Path $DocumentsRoot 'freizeitexperten.de\freizeitexperten-erp-dev-git')
Get-OrCloneProject -Repository 'https://github.com/B1ck5n0w/freizeitexperten-plugins-work.git' -Target (Join-Path $DocumentsRoot 'freizeitexperten.de\plugins-work')

& (Join-Path $PSScriptRoot 'install-auto-sync.ps1') -IntervalMinutes 15
Write-Host 'Notebook-Einrichtung abgeschlossen.' -ForegroundColor Green
