param(
    [switch]$Apply,
    [string[]]$Roots = @('app', 'config', 'public')
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$exts = @('.php', '.js', '.css', '.html', '.txt')
$encoding1252 = [System.Text.Encoding]::GetEncoding(1252)
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

# Typical mojibake markers from UTF-8 text decoded as Windows-1252 then re-saved.
$markerRegex = '(Ã[\x80-\xBF]|Â[\x80-\xBF]|Ä[\x80-\xBF]|ï¿½|�)'

function Get-MojibakeScore {
    param([string]$Text)
    return ([regex]::Matches($Text, $markerRegex)).Count
}

function Try-FixLine {
    param([string]$Line)

    if ([string]::IsNullOrEmpty($Line) -or ($Line -notmatch $markerRegex)) {
        return [pscustomobject]@{
            Changed = $false
            Line = $Line
        }
    }

    $bytes = $encoding1252.GetBytes($Line)
    $candidate = [System.Text.Encoding]::UTF8.GetString($bytes)

    if ($candidate -eq $Line) {
        return [pscustomobject]@{
            Changed = $false
            Line = $Line
        }
    }

    $oldScore = Get-MojibakeScore -Text $Line
    $newScore = Get-MojibakeScore -Text $candidate

    if ($newScore -lt $oldScore) {
        return [pscustomobject]@{
            Changed = $true
            Line = $candidate
        }
    }

    return [pscustomobject]@{
        Changed = $false
        Line = $Line
    }
}

$files = foreach ($root in $Roots) {
    if (-not (Test-Path $root)) { continue }
    Get-ChildItem -Path $root -Recurse -File |
        Where-Object { $exts -contains $_.Extension.ToLowerInvariant() }
}

$scanned = 0
$changedFiles = 0
$changedLines = 0
$details = New-Object System.Collections.Generic.List[object]

foreach ($file in $files) {
    $scanned++
    $raw = Get-Content -Raw -Path $file.FullName

    if ([string]::IsNullOrEmpty($raw)) {
        continue
    }

    $newline = if ($raw.Contains("`r`n")) { "`r`n" } else { "`n" }
    $lines = $raw -split "`r?`n", 0

    $lineChanged = 0
    for ($i = 0; $i -lt $lines.Length; $i++) {
        $result = Try-FixLine -Line $lines[$i]
        if ($result.Changed) {
            $lines[$i] = $result.Line
            $lineChanged++
        }
    }

    if ($lineChanged -eq 0) {
        continue
    }

    $changedFiles++
    $changedLines += $lineChanged
    $details.Add([pscustomobject]@{
        File = $file.FullName
        LinesFixed = $lineChanged
    }) | Out-Null

    if ($Apply) {
        $newRaw = [string]::Join($newline, $lines)
        [System.IO.File]::WriteAllText($file.FullName, $newRaw, $utf8NoBom)
    }
}

Write-Output "Scanned files: $scanned"
Write-Output "Files with fixable mojibake lines: $changedFiles"
Write-Output "Total lines fixable: $changedLines"

if ($details.Count -gt 0) {
    $details | Sort-Object LinesFixed -Descending | Format-Table -AutoSize
}

if (-not $Apply) {
    Write-Output ''
    Write-Output 'Dry run only. Re-run with -Apply to write changes.'
}
