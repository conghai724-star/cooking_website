param()
$ErrorActionPreference = 'Stop'

$git = Get-Command git -ErrorAction SilentlyContinue
if (-not $git) {
    throw 'git was not found in PATH.'
}

git config core.hooksPath .githooks
Write-Host 'Configured git hooks path: .githooks'
