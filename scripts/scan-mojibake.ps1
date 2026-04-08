param(
    [string]$Root = 'app/views',
    [string]$OutputCsv = 'storage/logs/mojibake_scan.csv',
    [switch]$NoCsv
)

$ErrorActionPreference = 'Stop'

$extensions = @('.php', '.html', '.js', '.css', '.md', '.sql', '.json')
# Unicode regex fragments typically seen in mojibake text.
$patterns = @('\u00C3', '\u00E2\u20AC', '\u00E1\u00BB', '\u00C4', '\u00C6', '\u00C2\s')

if (-not (Test-Path $Root)) {
    throw "Root path not found: $Root"
}

$files = Get-ChildItem -Path $Root -Recurse -File |
    Where-Object { $extensions -contains $_.Extension.ToLowerInvariant() }

$results = @()
foreach ($f in $files) {
    foreach ($p in $patterns) {
        $hits = Select-String -Path $f.FullName -Pattern $p
        foreach ($h in $hits) {
            $results += [pscustomobject]@{
                File = $f.FullName
                Line = $h.LineNumber
                Pattern = $p
                Text = $h.Line.Trim()
            }
        }
    }
}

$results = $results | Sort-Object File, Line, Pattern -Unique

if ($results.Count -eq 0) {
    Write-Host "No mojibake patterns found under $Root" -ForegroundColor Green
    exit 0
}

Write-Host "Found $($results.Count) suspicious lines under $Root" -ForegroundColor Yellow
$results | Select-Object -First 120 | Format-Table -AutoSize

if (-not $NoCsv) {
    $dir = Split-Path -Parent $OutputCsv
    if ($dir -and -not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
    $results | Export-Csv -Path $OutputCsv -NoTypeInformation -Encoding UTF8
    Write-Host "Saved report: $OutputCsv"
}
