param([string]$Name = ('release-' + (Get-Date -Format 'yyyyMMdd-HHmmss')))
$ErrorActionPreference = 'Stop'
$releaseRoot = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '../..'))
if ($Name -notmatch '^[a-zA-Z0-9_-]+$') { throw 'Use a simple directory name.' }
$releaseTarget = Join-Path $releaseRoot ('artifacts/' + $Name)
if (Test-Path -LiteralPath $releaseTarget) { throw 'Output already exists; choose another name.' }
New-Item -ItemType Directory -Path $releaseTarget | Out-Null
Push-Location $releaseRoot
try {
    # Working tree snapshot, including reviewed new files. Never copy live data,
    # tracked test datadirs, credentials, editor state, or development tools.
    $releaseFiles = & git -c core.quotepath=false ls-files --cached --others --exclude-standard
    if ($LASTEXITCODE -ne 0) { throw 'git ls-files failed' }
    $releaseCount = 0
    foreach ($releaseFile in ($releaseFiles | Sort-Object -Unique)) {
        if ($releaseFile -match '^(\.|dev/|tests/|artifacts/|data/|plans/|admin-menu-rework/)' -or $releaseFile -match '^(PRODUCT|QADATA|AGENTS)\.md$|^skills-lock\.json$') { continue }
        $releaseSource = Join-Path $releaseRoot $releaseFile
        if (!(Test-Path -LiteralPath $releaseSource -PathType Leaf)) { continue }
        $releaseDestination = Join-Path $releaseTarget $releaseFile
        New-Item -ItemType Directory -Force -Path ([IO.Path]::GetDirectoryName($releaseDestination)) | Out-Null
        Copy-Item -LiteralPath $releaseSource -Destination $releaseDestination
        $releaseCount++
    }
    New-Item -ItemType Directory -Path (Join-Path $releaseTarget 'data') | Out-Null
    Write-Output "$releaseTarget ($releaseCount runtime files; data directory is empty)"
} finally { Pop-Location }
