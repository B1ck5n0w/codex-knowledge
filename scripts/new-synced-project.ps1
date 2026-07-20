[CmdletBinding()]
param(
    [Parameter(Mandatory)]
    [string]$Name,
    [ValidateSet('Documents', 'GoogleDrive')]
    [string]$Location = 'Documents'
)

$ErrorActionPreference = 'Stop'
$workspaceRoot = Split-Path -Parent $PSScriptRoot
$documentsRoot = Split-Path -Parent $workspaceRoot
$owner = 'B1ck5n0w'
$slug = ($Name.Trim().ToLowerInvariant() -replace '[^a-z0-9]+', '-') -replace '^-|-$', ''
if ([string]::IsNullOrWhiteSpace($slug)) { throw 'Der Projektname enthält keine verwendbaren Zeichen.' }

& gh auth status *> $null
if ($LASTEXITCODE -ne 0) {
    throw 'GitHub CLI ist noch nicht angemeldet. Einmalig ausführen: gh auth login --web'
}

if ($Location -eq 'GoogleDrive') {
    $driveRelativePath = 'Meine Ablage\Projekte\KI Projekte'
    $projectRoot = Get-PSDrive -PSProvider FileSystem |
        ForEach-Object { Join-Path $_.Root $driveRelativePath } |
        Where-Object { Test-Path -LiteralPath $_ } |
        Select-Object -First 1
    if (-not $projectRoot) { throw 'Google Drive wurde nicht gefunden.' }
    $target = Join-Path $projectRoot $Name
    $configPath = "drive:$driveRelativePath\\$Name"
} else {
    $target = Join-Path $documentsRoot $Name
    $configPath = "..\\$Name"
}

if (Test-Path -LiteralPath (Join-Path $target '.git')) { throw "Bereits ein Git-Projekt: $target" }
New-Item -ItemType Directory -Path $target -Force | Out-Null
if ((Get-ChildItem -LiteralPath $target -Force | Measure-Object).Count -gt 0) {
    throw "Der Zielordner ist nicht leer: $target. Verschiebe die Dateien zuerst oder richte Git dort bewusst manuell ein."
}

git -C $target init -b main
@'
.env
.env.*
*.key
*.pem
*.pfx
*.p12
node_modules/
dist/
__pycache__/
.venv/
'@ | Set-Content -LiteralPath (Join-Path $target '.gitignore') -NoNewline
git -C $target add .gitignore
git -C $target commit -m 'Initial project setup'
gh repo create "$owner/$slug" --private --source $target --remote origin --push
if ($LASTEXITCODE -ne 0) { throw "GitHub-Repository konnte nicht erstellt werden: $owner/$slug" }

$configFile = Join-Path $workspaceRoot 'sync-projects.json'
$config = Get-Content -Raw -LiteralPath $configFile | ConvertFrom-Json
$config.projects += [pscustomobject]@{ path = $configPath; repository = "https://github.com/$owner/$slug.git" }
$config | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath $configFile

git -C $workspaceRoot add sync-projects.json
git -C $workspaceRoot commit -m "register synced project $slug"
git -C $workspaceRoot pull --rebase --autostash
git -C $workspaceRoot push

Write-Host "Fertig: $Name wird auf dem anderen Gerät beim nächsten Git-Abgleich geklont." -ForegroundColor Green
