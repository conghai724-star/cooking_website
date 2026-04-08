param(
    [string]$BaseUrl = 'http://localhost/cooking_website/public'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Get-CsrfToken {
    param(
        [string]$Html
    )

    $match = [regex]::Match($Html, 'data-csrf-token="([a-f0-9]{64})"')
    if (-not $match.Success) {
        throw 'Khong lay duoc CSRF token tu trang chu.'
    }
    return $match.Groups[1].Value
}

function Get-JsonValue {
    param(
        [Parameter(Mandatory = $true)]
        $Obj,
        [Parameter(Mandatory = $true)]
        [string]$Name,
        [string]$Default = ''
    )

    if ($null -eq $Obj) {
        return $Default
    }

    $prop = $Obj.PSObject.Properties[$Name]
    if ($null -eq $prop -or $null -eq $prop.Value) {
        return $Default
    }

    return [string]$prop.Value
}

function Get-JsonBool {
    param(
        [Parameter(Mandatory = $true)]
        $Obj,
        [Parameter(Mandatory = $true)]
        [string]$Name,
        [bool]$Default = $false
    )

    if ($null -eq $Obj) {
        return $Default
    }

    $prop = $Obj.PSObject.Properties[$Name]
    if ($null -eq $prop -or $null -eq $prop.Value) {
        return $Default
    }

    return [bool]$prop.Value
}

$tests = @(
    @{ question = 'toi muon vao tai khoan'; expected = 'auth_login' },
    @{ question = 'toi quen mat khau'; expected = 'auth_forgot_password' },
    @{ question = 'xem ke hoach bua an cua toi o dau'; expected = 'mealplan_view' },
    @{ question = 'an gi giam can'; expected = 'diet_weight_loss' },
    @{ question = 'co mon it calo khong'; expected = 'diet_low_calorie' }
)

$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$homeResponse = Invoke-WebRequest -Uri "$BaseUrl/" -WebSession $session -UseBasicParsing
$csrf = Get-CsrfToken -Html $homeResponse.Content

$results = @()
foreach ($t in $tests) {
    $question = [string]$t.question
    $expected = [string]$t.expected
    $ok = $false
    $intent = ''
    $code = ''
    $message = ''
    $latencyMs = ''
    $httpStatus = 0

    try {
        $resp = Invoke-WebRequest -Uri "$BaseUrl/chat" -Method Post -WebSession $session -Headers @{
            'X-CSRF-Token' = $csrf
            'X-Requested-With' = 'XMLHttpRequest'
            'Accept' = 'application/json'
        } -Body @{
            message = $question
            _csrf = $csrf
        } -UseBasicParsing

        $httpStatus = [int]$resp.StatusCode
        $data = $resp.Content | ConvertFrom-Json
        $intent = Get-JsonValue -Obj $data -Name 'intent'
        $code = Get-JsonValue -Obj $data -Name 'code'
        $message = Get-JsonValue -Obj $data -Name 'message'
        $latencyMs = Get-JsonValue -Obj $data -Name 'latency_ms'
        $success = Get-JsonBool -Obj $data -Name 'success' -Default $false
        $ok = ($httpStatus -eq 200) -and $success -and ($intent -eq $expected)
    }
    catch {
        $message = $_.Exception.Message
        $ok = $false
    }

    $shortMessage = $message
    if ($shortMessage.Length -gt 80) {
        $shortMessage = $shortMessage.Substring(0, 80) + '...'
    }

    $results += [PSCustomObject]@{
        pass = if ($ok) { 'PASS' } else { 'FAIL' }
        expected_intent = $expected
        actual_intent = $intent
        status = $httpStatus
        code = $code
        latency_ms = $latencyMs
        question = $question
        message = $shortMessage
    }
}

$results | Format-Table -AutoSize

$failed = @($results | Where-Object { $_.pass -eq 'FAIL' })
if ($failed.Count -gt 0) {
    Write-Host ''
    Write-Host "Tong ket: $($failed.Count)/$($results.Count) test FAIL." -ForegroundColor Red
    exit 1
}

Write-Host ''
Write-Host "Tong ket: $($results.Count)/$($results.Count) test PASS." -ForegroundColor Green
exit 0

