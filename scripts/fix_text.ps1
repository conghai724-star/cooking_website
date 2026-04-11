param(
    [string[]]$Roots = @("app/views"),
    [string]$FixesFile = "scripts/mojibake-fixes.json",
    [switch]$Apply,
    [switch]$LintPhp
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

if ($Roots.Count -eq 1 -and ($Roots[0].Contains(',') -or $Roots[0].Contains(';'))) {
    $Roots = $Roots[0] -split '[,;]' | ForEach-Object { $_.Trim() } | Where-Object { $_ -ne '' }
}

if (-not (Test-Path -LiteralPath $FixesFile)) {
    throw "Fixes file not found: $FixesFile"
}

$lines = Get-Content -Encoding UTF8 -LiteralPath $FixesFile
$pairs = New-Object System.Collections.Generic.List[object]

foreach ($line in $lines) {
    if ($line -match '"(?<k>.+?)"\s*:\s*"(?<v>.*?)"\s*,?\s*$') {
        $k = [string]$matches['k']
        $v = [string]$matches['v']
        if ($k -ne "") {
            $pairs.Add([pscustomobject]@{ Key = $k; Value = $v }) | Out-Null
        }
    }
}

$sortedPairs = $pairs | Sort-Object { $_.Key.Length } -Descending
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$extensions = @(".php", ".html", ".js", ".css", ".md", ".sql", ".json")
$changedFiles = New-Object System.Collections.Generic.List[string]
$changedCount = 0
$originalMap = @{}

function Should-ProcessFile {
    param([string]$Path)
    $ext = [System.IO.Path]::GetExtension($Path).ToLowerInvariant()
    return $extensions -contains $ext
}

$targets = New-Object System.Collections.Generic.List[string]
foreach ($root in $Roots) {
    if (Test-Path -LiteralPath $root) {
        if (Test-Path -LiteralPath $root -PathType Leaf) {
            if (Should-ProcessFile -Path $root) {
                $targets.Add((Resolve-Path -LiteralPath $root).Path) | Out-Null
            }
            continue
        }
        Get-ChildItem -LiteralPath $root -Recurse -File | ForEach-Object {
            if (Should-ProcessFile -Path $_.FullName) {
                $targets.Add($_.FullName) | Out-Null
            }
        }
    }
}

foreach ($file in $targets) {
    $content = Get-Content -Raw -Encoding UTF8 -LiteralPath $file
    if ($null -eq $content) { continue }
    $original = $content

    foreach ($pair in $sortedPairs) {
        if ($null -eq $pair) { continue }
        $k = if ($null -eq $pair.Key) { "" } else { [string]$pair.Key }
        if ($k -eq "") { continue }
        $v = if ($null -eq $pair.Value) { "" } else { [string]$pair.Value }
        if ($content.Contains($k)) {
            $content = $content.Replace($k, $v)
        }
    }

    if ($content -ne $original) {
        $changedCount++
        $changedFiles.Add($file) | Out-Null
        if ($Apply) {
            $originalMap[$file] = $original
            [System.IO.File]::WriteAllText($file, $content, $utf8NoBom)
        }
    }
}

Write-Output ("Loaded pairs: {0}" -f $pairs.Count)
Write-Output ("Checked files: {0}" -f $targets.Count)
Write-Output ("Changed files: {0}" -f $changedCount)
if ($changedCount -gt 0) {
    $changedFiles | ForEach-Object { Write-Output (" - " + $_) }
}

if ($LintPhp -and $Apply -and $changedCount -gt 0) {
    $php = "D:\xampp\php\php.exe"
    if (-not (Test-Path -LiteralPath $php)) {
        Write-Warning "php.exe not found at $php, skip lint."
    } else {
        foreach ($f in $changedFiles) {
            if ([System.IO.Path]::GetExtension($f).ToLowerInvariant() -ne ".php") { continue }
            & $php -l $f | Out-Host
            if ($LASTEXITCODE -ne 0) {
                if ($originalMap.ContainsKey($f)) {
                    [System.IO.File]::WriteAllText($f, [string]$originalMap[$f], $utf8NoBom)
                    Write-Warning "Reverted due to lint fail: $f"
                }
                exit 1
            }
        }
    }
}

if (-not $Apply) {
    Write-Output "Dry run only. Add -Apply to write changes."
}

