param(
    [string]$Root = 'app/views',
    [string]$FixJson = 'scripts/mojibake-fixes.json',
    [switch]$DryRun
)

$ErrorActionPreference = 'Stop'

if (-not (Test-Path $Root)) {
    throw "Root path not found: $Root"
}
if (-not (Test-Path $FixJson)) {
    throw "Fix file not found: $FixJson"
}

$extensions = @('.php', '.html', '.js', '.css', '.md', '.sql', '.json')
$raw = Get-Content -Path $FixJson -Raw -Encoding UTF8
$mapObj = ConvertFrom-Json -InputObject $raw
$map = @{}
foreach ($prop in $mapObj.PSObject.Properties) {
    $map[$prop.Name] = $prop.Value
}

$files = Get-ChildItem -Path $Root -Recurse -File |
    Where-Object { $extensions -contains $_.Extension.ToLowerInvariant() }

$changed = 0
foreach ($f in $files) {
    $content = Get-Content -Path $f.FullName -Raw -Encoding UTF8
    $updated = $content

    foreach ($k in $map.Keys) {
        $v = [string]$map[$k]
        $updated = $updated.Replace([string]$k, $v)
    }

    if ($updated -ne $content) {
        $changed++
        if ($DryRun) {
            Write-Host "[DRY] would update: $($f.FullName)"
        } else {
            Set-Content -Path $f.FullName -Value $updated -Encoding UTF8
            Write-Host "updated: $($f.FullName)"
        }
    }
}

if ($DryRun) {
    Write-Host "Dry run complete. Files affected: $changed"
} else {
    Write-Host "Done. Files updated: $changed"
}
