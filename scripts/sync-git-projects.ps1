[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$workspaceRoot = Split-Path -Parent $PSScriptRoot
$configPath = Join-Path $workspaceRoot 'sync-projects.json'
$config = Get-Content -Raw -LiteralPath $configPath | ConvertFrom-Json
$timestamp = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'

function Invoke-Git {
    param([string[]]$Arguments)
    & git @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "Git-Befehl fehlgeschlagen: git $($Arguments -join ' ')"
    }
}

foreach ($entry in $config.projects) {
    $project = [IO.Path]::GetFullPath((Join-Path $workspaceRoot $entry))
    Write-Host "`n==> $project"

    if (-not (Test-Path -LiteralPath (Join-Path $project '.git'))) {
        Write-Warning 'Übersprungen: kein Git-Repository.'
        continue
    }

    $origin = git -C $project remote get-url origin 2>$null
    if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($origin)) {
        Write-Warning 'Übersprungen: GitHub-Remote "origin" ist noch nicht eingerichtet.'
        continue
    }

    try {
        Invoke-Git @('-C', $project, 'add', '-A')
        & git -C $project diff --cached --quiet
        if ($LASTEXITCODE -eq 1) {
            Invoke-Git @('-C', $project, 'commit', '-m', "Automatische Sicherung $timestamp")
        } elseif ($LASTEXITCODE -gt 1) {
            throw 'Git konnte den vorgemerkten Änderungsstand nicht prüfen.'
        }

        Invoke-Git @('-C', $project, 'pull', '--rebase', '--autostash')
        Invoke-Git @('-C', $project, 'push')
        Write-Host 'Synchronisiert.' -ForegroundColor Green
    }
    catch {
        Write-Warning "Nicht synchronisiert: $($_.Exception.Message)"
        Write-Warning 'Der Projektordner wurde nicht überschrieben. Prüfe bei Bedarf den Git-Status manuell.'
    }
}
