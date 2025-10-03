<?php
// webservice/index.php
// Landing page + WSDL endpoint + SOAP POST handler + records viewer

require __DIR__ . '/../../Service.php';
$wsdlPath = __DIR__ . '/../../wsdl/oneiss.wsdl';
$dataFiles = [
    __DIR__ . '/../../../oneissWeb/oneiss-pushInjuryData.txt',
    __DIR__ . '/../../../oneissWeb/oneiss-pushApirData.txt'
];

// Helper: serve WSDL file
if (isset($_GET['wsdl'])) {
    header('Content-Type: application/wsdl+xml; charset=utf-8');
    readfile($wsdlPath);
    exit;
}

// View records endpoint: ?view=records
$view = isset($_GET['view']) ? $_GET['view'] : null;
if ($view === 'records') {
    // show persisted SQLite records
    $dbFile = __DIR__ . '/../../data/oneiss.db';
    if (!file_exists($dbFile)) {
        echo "<p>No records DB found.</p>";
        exit;
    }
    try {
        $pdo = new PDO('sqlite:' . $dbFile);
        $stmt = $pdo->query('SELECT id, operation, payload, created_at FROM records ORDER BY created_at DESC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo "<p>DB error: " . htmlspecialchars($e->getMessage()) . "</p>";
        exit;
    }
    ?><!doctype html>
    <html><head><meta charset="utf-8"><title>Mock OneISS - Records</title>
    <style>body{font-family:Arial;margin:16px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:8px;text-align:left}pre{white-space:pre-wrap}</style>
    </head><body>
    <h1>Stored Records</h1>
    <p><a href="/webservice/">Back to API docs</a></p>
    <table>
      <thead><tr><th>ID</th><th>Operation</th><th>Created At</th><th>Payload</th></tr></thead>
      <tbody>
    <?php
    foreach ($rows as $r) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($r['id']) . '</td>';
        echo '<td>' . htmlspecialchars($r['operation']) . '</td>';
        echo '<td>' . htmlspecialchars($r['created_at']) . '</td>';
        echo '<td><pre>' . htmlspecialchars($r['payload']) . '</pre></td>';
        echo '</tr>';
    }
    ?></tbody></table></body></html><?php
    exit;
}

// If POST => treat as SOAP POST to this endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle SOAP POST using WSDL in wsdlPath (RPC/encoded per official ONEISS WSDL)
    ini_set('soap.wsdl_cache_enabled', '0');
    try {
        $server = new SoapServer($wsdlPath);
        $server->setClass('Service');
        $server->handle();
    } catch (Exception $e) {
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(500);
        echo "SOAP server error: " . $e->getMessage();
    }
    exit;
}

function parseFieldTable($file)
{
    if (!is_readable($file)) return [];
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $rows = [];
    foreach ($lines as $line) {
        // Many lines contain tab-separated columns starting with a number
        $parts = preg_split('/\t+/', $line);
        if (count($parts) >= 2 && preg_match('/^\s*\d+\s*/', $parts[0])) {
            // remove leading number from first part
            $first = preg_replace('/^\s*\d+\s*/', '', $parts[0]);
            // If file uses columns separated differently, try splitting by multiple spaces
            $cols = array_merge([$first], array_slice($parts, 1));
            $rows[] = $cols;
        } else {
            // attempt to parse lines with leading number and tab using regex
            if (preg_match('/^\s*(\d+)\s+([^\t]+)\t([^\t]+)\t([^\t]+)/', $line, $m)) {
                $rows[] = [ $m[2], $m[3], $m[4] ];
            }
        }
    }
    return $rows;
}

$injFields = parseFieldTable($dataFiles[0]);
$apirFields = parseFieldTable($dataFiles[1]);

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mock OneISS - Web Service</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;margin:24px;color:#222}
    header{border-bottom:1px solid #ddd;padding-bottom:12px;margin-bottom:18px}
    h1{margin:0 0 6px}
    .container{max-width:980px}
    .card{border:1px solid #eee;padding:12px;margin-bottom:12px;border-radius:6px;background:#fafafa}
    pre{background:#111;color:#efe;padding:12px;border-radius:6px;overflow:auto}
    a{color:#0066cc}
    table{border-collapse:collapse;width:100%}
    th,td{border:1px solid #eee;padding:8px;text-align:left}
    .note{font-size:13px;color:#444}
  </style>
</head>
<body>
  <div class="container">
    <header>
      <h1>Mock OneISS - Web Service</h1>
      <div>Path: /webservice/ — This mock now serves the official ONEISS WSDL (RPC/encoded, single string parameter <code>Data</code>).</div>
      <div class="note">WSDL: <a href="/webservice/index.php?wsdl">/webservice/index.php?wsdl</a> · SOAP endpoint (POST): <code>/webservice/index.php</code> · Records: <a href="/webservice/admin.php">Admin</a></div>
    </header>

    <section class="card">
      <h2>About the WSDL</h2>
      <p>The official ONEISS WSDL defines RPC/encoded operations where each method takes a single <code>Data</code> parameter of type <code>xsd:string</code> and returns a string. This mock accepts either:</p>
      <ul>
        <li>WSDL-compliant string payloads (e.g., JSON or XML inside <code>Data</code>, often wrapped in CDATA)</li>
        <li>Convenience structured XML inside <code>&lt;Data&gt;...&lt;/Data&gt;</code> for local testing</li>
      </ul>
    </section>

    <section class="card" id="samples">
      <h2>Samples</h2>
      <h3>WSDL-compliant (string) — pushApirData</h3>
      <pre><?php echo htmlentities('<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="oneiss.doh.gov.ph/webservice/index.php?wsdl">
  <soapenv:Header/>
  <soapenv:Body>
    <urn:pushApirData>
      <Data><![CDATA[{"Pat_Facility_No":"DOH000000000000877","reg_no":"NM201600100001","inj_date":"2016-03-30","inj_time":"07:38:02","Pat_Last_Name":"Platon","Pat_First_Name":"Jonathan","Pat_Middle_Name":"Elec","Pat_Sex":"M","Pat_Current_Address_StreetName":"main street","Pat_Current_Address_Region":"04","Pat_Current_Address_Province":"0434","Pat_Current_Address_City":"043411","involve_code":"10","typeof_injurycode":"10","diagnosis":"INGESTION","liquor":"Y","disposition_code":"TRASH"}]]></Data>
    </urn:pushApirData>
  </soapenv:Body>
</soapenv:Envelope>'); ?></pre>

      <h3>WSDL-compliant (string) — pushInjuryData</h3>
      <pre><?php echo htmlentities('<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="oneiss.doh.gov.ph/webservice/index.php?wsdl">
  <soapenv:Header/>
  <soapenv:Body>
    <urn:pushInjuryData>
      <Data><![CDATA[{"Pat_Facility_No":"DOH000000000000877","Status":"E","rstatuscode":"V","date_report":"2016-03-29","time_report":"08:30:00","reg_no":"NM201600100001","tempreg_no":"TN0000000100001","hosp_no":"1065","hosp_reg_no":"1445","hosp_cas_no":"1926","ptype_code":"i"}]]></Data>
    </urn:pushInjuryData>
  </soapenv:Body>
</soapenv:Envelope>'); ?></pre>

      <h3>Convenience (structured XML) — webInjury</h3>
      <pre><?php echo htmlentities(file_get_contents(__DIR__ . '/../../request-samples/webInjury.xml')); ?></pre>
    </section>

    <section class="card">
      <h2>Available operations</h2>
      <table>
        <thead><tr><th>Operation</th><th>Description</th><th>Sample</th></tr></thead>
        <tbody>
          <tr><td>pushInjuryData</td><td>Submit injury case data.</td><td><a href="#samples">see above</a></td></tr>
          <tr><td>pushApirData</td><td>Submit APIR/sentinel event data.</td><td><a href="#samples">see above</a></td></tr>
          <tr><td>webInjury</td><td>Alternate entry for injury data.</td><td><a href="#samples">see above</a></td></tr>
        </tbody>
      </table>
    </section>

    <section class="card">
      <h3>pushInjuryData — field descriptions</h3>
      <?php if (empty($injFields)): ?>
        <p>No field table parsed from pushInjuryData spec file.</p>
      <?php else: ?>
        <table>
          <thead><tr><th>Field</th><th>Description</th><th>Type/Notes</th></tr></thead>
          <tbody>
          <?php foreach ($injFields as $r): ?>
            <?php $f = $r[0] ?? ''; $desc = $r[1] ?? ($r[2] ?? ''); $notes = $r[2] ?? ''; ?>
            <tr><td><?php echo htmlspecialchars($f); ?></td><td><?php echo htmlspecialchars($desc); ?></td><td><?php echo htmlspecialchars($notes); ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>

    <section class="card">
      <h3>pushApirData — field descriptions</h3>
      <?php if (empty($apirFields)): ?>
        <p>No field table parsed from pushApirData spec file.</p>
      <?php else: ?>
        <table>
          <thead><tr><th>Field</th><th>Description</th><th>Type/Notes</th></tr></thead>
          <tbody>
          <?php foreach ($apirFields as $r): ?>
            <?php $f = $r[0] ?? ''; $desc = $r[1] ?? ($r[2] ?? ''); $notes = $r[2] ?? ''; ?>
            <tr><td><?php echo htmlspecialchars($f); ?></td><td><?php echo htmlspecialchars($desc); ?></td><td><?php echo htmlspecialchars($notes); ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>

    <footer style="margin-top:18px;color:#666;font-size:13px">Mock OneISS &copy; <?php echo date('Y'); ?> — For local testing only</footer>
  </div>
</body>
</html>
