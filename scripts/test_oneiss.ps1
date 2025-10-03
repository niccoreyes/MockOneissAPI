param(
    [string]$BaseUrl = 'http://localhost:8080'
)

$uri = "$BaseUrl/webservice/index.php"
Write-Host "POST $uri (oneiss ping)"

$body = @'
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="oneiss.doh.gov.ph/webservice/index.php?wsdl">
  <soapenv:Header/>
  <soapenv:Body>
    <urn:oneiss/>
  </soapenv:Body>
</soapenv:Envelope>
'@

try {
    $resp = Invoke-WebRequest -Uri $uri -Method Post -ContentType 'text/xml;charset=UTF-8' -Body $body -UseBasicParsing -TimeoutSec 30
} catch {
    Write-Host "Request failed: $_"
    exit 2
}

try {
    [xml]$xml = $resp.Content
} catch {
    Write-Host "Failed to parse SOAP response as XML"
    Write-Host $resp.Content
    exit 3
}

$returnNode = $xml.SelectSingleNode("//*[local-name()='return']")
if (-not $returnNode) {
    Write-Host "No <return> node found in SOAP response"
    Write-Host $resp.Content
    exit 3
}

$innerXml = $returnNode.'#text'
if (-not $innerXml) {
    Write-Host "<return> contained no inner XML/text"
    Write-Host $resp.Content
    exit 4
}

try {
    [xml]$inner = $innerXml
} catch {
    Write-Host "Failed to parse inner XML from <return>"
    Write-Host $innerXml
    exit 5
}

$code = $inner.oneiss.response_code
$desc = $inner.oneiss.response_desc

Write-Host "response_code: $code"
Write-Host "response_desc: $desc"

if ($code -eq '104') {
    Write-Host "OK: Test passed"
    exit 0
} else {
    Write-Host "FAIL: Response code not 104"
    exit 1
}
