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

function Get-SensitivePaths {
    param([string]$Project)

    $paths = & git -C $Project diff --cached --name-only
    if ($LASTEXITCODE -ne 0) { throw 'Vorgemerkte Dateien konnten nicht geprüft werden.' }

    # Protect common secret formats even if a repository's .gitignore is
    # incomplete. Existing tracked secrets still stop the sync for review.
    return @($paths | Where-Object {
        $_ -match '(^|/)\.env($|\.)' -or
        $_ -match '(?i)(^|/)(id_rsa|credentials)(\.|$)' -or
        $_ -match '(?i)\.(pem|key|pfx|p12|crt)$'
    })
}

foreach ($entry in $config.projects) {
    $project = if ($entry.StartsWith('drive:', [StringComparison]::OrdinalIgnoreCase)) {
        $driveRelativePath = $entry.Substring(6)
        Get-PSDrive -PSProvider FileSystem |
            ForEach-Object { Join-Path $_.Root $driveRelativePath } |
            Where-Object { Test-Path -LiteralPath $_ } |
            Select-Object -First 1
    } elseif ([IO.Path]::IsPathRooted($entry)) {
        [IO.Path]::GetFullPath($entry)
    } else {
        [IO.Path]::GetFullPath((Join-Path $workspaceRoot $entry))
    }
    if ([string]::IsNullOrWhiteSpace($project)) {
        Write-Warning "Übersprungen: Drive-Projekt wurde nicht gefunden: $entry"
        continue
    }
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
        $sensitivePaths = Get-SensitivePaths -Project $project
        if ($sensitivePaths.Count -gt 0) {
            & git -C $project restore --staged -- $sensitivePaths
            if ($LASTEXITCODE -ne 0) { throw 'Sensible Dateien konnten nicht aus dem Staging entfernt werden.' }
            Write-Warning ('Nicht eingecheckt (sensible Dateien): ' + ($sensitivePaths -join ', '))
        }
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
