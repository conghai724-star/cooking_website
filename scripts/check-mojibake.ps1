param(
    [switch]$Staged,
    [string[]]$Roots = @('app', 'config', 'public', 'scripts', 'tests', 'docs')
)

$ErrorActionPreference = 'Stop'

$extensions = @('.php', '.sql', '.js', '.css', '.html', '.json', '.md', '.txt', '.yml', '.yaml', '.xml')

# Regexes for common mojibake patterns (ASCII-only source, Unicode escapes in regex).
$regexes = @(
    '[\u00C2\u00C3\u00C4\u0102][\u0080-\u00BF]', # sequences like Ã¡, Â±, Ä‘, Ă 
    '\uFFFD'                                       # replacement character: �
)

function Get-TargetFiles {
    param([switch]$OnlyStaged)

    if ($OnlyStaged) {
        $git = Get-Command git -ErrorAction SilentlyContinue
        if (-not $git) {
            throw 'git is required for -Staged mode but was not found in PATH.'
        }

        $files = git diff --cached --name-only --diff-filter=ACM
        return $files |
            Where-Object { $_ } |
            Where-Object { Test-Path $_ } |
            Where-Object { $extensions -contains ([System.IO.Path]::GetExtension($_).ToLowerInvariant()) }
    }

    $all = @()
    foreach ($root in $Roots) {
        if (-not (Test-Path $root)) {
            continue
        }

        $all += Get-ChildItem -Path $root -Recurse -File |
            Where-Object { $extensions -contains $_.Extension.ToLowerInvariant() } |
            ForEach-Object { $_.FullName }
    }

    return $all
}

$targets = @(Get-TargetFiles -OnlyStaged:$Staged)
if ($targets.Count -eq 0) {
    Write-Host 'Encoding check: no target files.'
    exit 0
}

$issues = @()
foreach ($file in $targets) {
    $lineNo = 0
    Get-Content -Path $file | ForEach-Object {
        $lineNo++
        $line = [string]$_
        foreach ($rx in $regexes) {
            if ($line -match $rx) {
                $issues += [pscustomobject]@{
                    File = $file
                    Line = $lineNo
                    Text = $line.Trim()
                }
                break
            }
        }
    }
}

if ($issues.Count -gt 0) {
    Write-Host 'Found possible mojibake/encoding issues:' -ForegroundColor Red
    $issues |
        Sort-Object File, Line -Unique |
        ForEach-Object {
            Write-Host ('- {0}:{1} -> {2}' -f $_.File, $_.Line, $_.Text)
        }

    Write-Host ''
    Write-Host 'Fix encoding to UTF-8 before commit.' -ForegroundColor Yellow
    Write-Host 'Tip: powershell -ExecutionPolicy Bypass -File scripts/fix_mojibake.ps1 -Apply' -ForegroundColor Yellow
    exit 1
}

Write-Host 'Encoding check: OK' -ForegroundColor Green
exit 0
