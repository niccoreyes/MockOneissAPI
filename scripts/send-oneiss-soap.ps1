<#
PowerShell script to send a SOAP RPC-encoded request with a <Data> struct read from JSON.
Usage:
  pwsh .\scripts\send-oneiss-soap.ps1 -JsonPath .\examples\payload_pushApirData.json -Method pushApirData -Endpoint https://oneiss.doh.gov.ph/webservice/index.php
#>
param(
    [string] $JsonPath = "./examples/payload_pushApirData.json",
    [string] $Endpoint = "http://localhost:8080/webservice/index.php",
    [ValidateSet('pushApirData','pushInjuryData','webInjury')]
    [string] $Method = "pushApirData",
    [string] $SoapAction = ''
)

function Escape-Xml([string] $s) {
    if ($null -eq $s) { return "" }
    return $s.Replace('&','&amp;').Replace('<','&lt;').Replace('>','&gt;').Replace('"','&quot;').Replace("'","&apos;")
}

if (-not (Test-Path $JsonPath)) {
    Write-Error "JSON file not found: $JsonPath"
    exit 2
}

$json = Get-Content -Raw -Path $JsonPath | ConvertFrom-Json
$dataObj = if ($json.PSObject.Properties.Name -contains 'Data') { $json.Data } else { $json }

# Build Data element children
$dataElements = @()
foreach ($p in $dataObj.PSObject.Properties) {
    $val = $p.Value
    if ($val -is [System.Collections.IEnumerable] -and -not ($val -is [string])) {
        $val = ($val | ForEach-Object { $_.ToString() }) -join ","
    } elseif ($val -is [PSCustomObject]) {
        $val = (ConvertTo-Json $val -Compress)
    }
    $dataElements += "<" + $p.Name + ">" + (Escape-Xml ($val -as [string])) + "</" + $p.Name + ">"
}

# Use the urn namespace that matches recorded requests and the WSDL examples
$urn = 'http://oneiss.doh.gov.ph/webservice'
$soapEnvNs = 'http://schemas.xmlsoap.org/soap/envelope/'
$soapEnc = 'http://schemas.xmlsoap.org/soap/encoding/'

# Send Data as structured XML elements (no CDATA) to match mock server examples
$bodyInner = "<Data>`n" + ($dataElements -join "`n") + "`n</Data>"

# Use urn prefix in the method element to match earlier requests
$envelope = @"
<soapenv:Envelope xmlns:soapenv=\"$soapEnvNs\" xmlns:urn=\"$urn\" xmlns:SOAP-ENC=\"$soapEnc\">
  <soapenv:Header/>
  <soapenv:Body>
    <urn:$Method>
      $bodyInner
    </urn:$Method>
  </soapenv:Body>
</soapenv:Envelope>
"@

# Show the exact SOAP envelope that will be sent (helpful for debugging)
Write-Host "----- SOAP ENVELOPE (sending) -----" -ForegroundColor Cyan
Write-Host $envelope
# Save a copy for later inspection
$sentPath = Join-Path (Get-Location) "logs\last_sent_envelope.xml"
# Ensure directory exists
$sentDir = Split-Path $sentPath -Parent
if (-not (Test-Path $sentDir)) { New-Item -ItemType Directory -Path $sentDir -Force | Out-Null }
Set-Content -Path $sentPath -Value $envelope -Encoding UTF8
Write-Host "Saved envelope to: $sentPath" -ForegroundColor Yellow

if ([string]::IsNullOrWhiteSpace($SoapAction)) {
    # default SOAPAction per WSDL pattern (uses https in the official WSDL but endpoint may be local)
    $SoapAction = "https://oneiss.doh.gov.ph/webservice/index.php/$Method"
}

# Some SOAP servers expect the SOAPAction header value to be quoted
$soapActionQuoted = '"' + $SoapAction + '"'
$headers = @{ 'SOAPAction' = $soapActionQuoted; 'Accept' = 'text/xml' }

Write-Host "Sending $Method to $Endpoint with JSON file $JsonPath"
$lastResponsePath = Join-Path (Get-Location) "logs\last_response.txt"

# Prefer curl.exe as primary HTTP client
$curlCmd = Get-Command curl.exe -ErrorAction SilentlyContinue
if ($null -eq $curlCmd) {
    Write-Error "curl.exe not found on PATH. Please install curl or add it to PATH."
    exit 3
}

# Ensure the envelope file exists
$bodyFile = $sentPath
if (-not (Test-Path $bodyFile)) { Write-Error "Envelope file not found: $bodyFile"; exit 3 }

# Build curl arguments. Use --silent and --show-error, include response headers (-i) so we can inspect status.
# Ensure SOAPAction header is quoted as required by some servers.
$soapActionHeader = "SOAPAction: $soapActionQuoted"
$curlArgs = @('--silent','--show-error','-i','-H','Content-Type: text/xml; charset=utf-8','-H',$soapActionHeader,'--data-binary',"@$bodyFile",$Endpoint)

try {
    $curlOut = & curl.exe @curlArgs 2>&1
    $curlOutStr = ($curlOut -join "`n")
    Write-Host "curl output:`n$curlOutStr"
    # Save response for inspection
    if (-not (Test-Path (Split-Path $lastResponsePath -Parent))) { New-Item -ItemType Directory -Path (Split-Path $lastResponsePath -Parent) -Force | Out-Null }
    Set-Content -Path $lastResponsePath -Value $curlOutStr -Encoding UTF8
    # Attempt to extract HTTP status code from headers (first line like HTTP/1.1 200 OK)
    if ($curlOutStr -match 'HTTP/[0-9\.]+\s+(\d{3})') {
        $status = $matches[1]
        Write-Host "HTTP status: $status"
    }
} catch {
    Write-Error "curl.exe request failed: $($_.Exception.Message)"
    exit 3
}
