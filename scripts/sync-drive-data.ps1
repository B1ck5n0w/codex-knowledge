[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$workspaceRoot = Split-Path -Parent $PSScriptRoot
$documentsRoot = Split-Path -Parent $workspaceRoot
$config = Get-Content -Raw -LiteralPath (Join-Path $workspaceRoot 'drive-data-sync.json') | ConvertFrom-Json

$driveRoot = Get-PSDrive -PSProvider FileSystem |
    ForEach-Object { Join-Path $_.Root $config.drive_relative_path } |
    Where-Object { Test-Path -LiteralPath $_ } |
    Select-Object -First 1

if (-not $driveRoot) {
    throw "Google-Drive-Ordner '$($config.drive_relative_path)' wurde nicht gefunden."
}

foreach ($relativeSource in $config.sources) {
    $source = Join-Path $documentsRoot $relativeSource
    if (-not (Test-Path -LiteralPath $source)) {
        Write-Warning "Übersprungen: Quelle fehlt: $source"
        continue
    }

    $target = Join-Path (Join-Path $driveRoot $config.backup_root) (Split-Path -Leaf $source)
    New-Item -ItemType Directory -Path $target -Force | Out-Null
    Write-Host "Sichere: $source" -ForegroundColor Cyan
    & robocopy $source $target /E /COPY:DAT /DCOPY:DAT /R:1 /W:1 /XD '.git' /XF '*.key' '*.pem' '*.crt' '*.pfx' '*.p12' '*.pubkey'
    if ($LASTEXITCODE -ge 8) {
        throw "Drive-Sicherung fehlgeschlagen (Robocopy-Code $LASTEXITCODE): $source"
    }
}
