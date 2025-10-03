# Run all PowerShell test scripts and summarize
$tests = @(
    "test_pushInjuryData.ps1",
    "test_pushApirData.ps1",
    "test_webInjury.ps1",
    "test_oneiss.ps1",
    "test_dataSelect.ps1"
)

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Definition
Push-Location $scriptDir
$failures = 0

foreach ($t in $tests) {
    Write-Host "Running $t"
    & pwsh -NoProfile -NoLogo -ExecutionPolicy Bypass -File (Join-Path $scriptDir $t)
    $rc = $LASTEXITCODE
    if ($rc -ne 0) {
        Write-Host "$t FAILED (exit $rc)" -ForegroundColor Red
        $failures++
    } else {
        Write-Host "$t OK" -ForegroundColor Green
    }
}

if ($failures -gt 0) {
    Write-Host "Some tests failed: $failures" -ForegroundColor Red
    exit 1
} else {
    Write-Host "All tests passed" -ForegroundColor Green
    exit 0
}
