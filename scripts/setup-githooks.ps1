param(
    [string]$HooksPath = '.githooks'
)

$ErrorActionPreference = 'Stop'

$git = Get-Command git -ErrorAction SilentlyContinue
if (-not $git) {
    Write-Host 'git not found in PATH. Please install git or run this in Git-enabled shell.' -ForegroundColor Red
    exit 1
}

if (-not (Test-Path $HooksPath)) {
    Write-Host "Hooks path '$HooksPath' not found." -ForegroundColor Red
    exit 1
}

git config core.hooksPath $HooksPath
if ($LASTEXITCODE -ne 0) {
    Write-Host 'Failed to set core.hooksPath.' -ForegroundColor Red
    exit 1
}

$current = git config --get core.hooksPath
Write-Host "core.hooksPath=$current" -ForegroundColor Green
Write-Host 'Done. Pre-commit mojibake check is now enforced for this repo.' -ForegroundColor Green
