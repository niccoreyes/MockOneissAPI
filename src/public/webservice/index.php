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
    // Handle SOAP POST using WSDL in wsdlPath
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

// Otherwise render landing page with API docs and per-field descriptions
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
  </style>
</head>
<body>
  <div class="container">
    <header>
      <h1>Mock OneISS - Web Service</h1>
      <div>Path: /webservice/ &nbsp; — &nbsp; This mock provides a WSDL and mock SOAP operations that mirror the OneISS API.</div>
    </header>

    <section class="card">
      <h2>Service endpoints</h2>
      <ul>
        <li>WSDL: <a href="/webservice/index.php?wsdl">/webservice/index.php?wsdl</a></li>
        <li>Auto-generated WSDL (laminas AutoDiscover): <a href="/webservice/autodiscover.php?wsdl">/webservice/autodiscover.php?wsdl</a></li>
        <li>SOAP endpoint: <code>/webservice/index.php</code> (POST SOAP)</li>
        <li>Records viewer: <a href="/webservice/index.php?view=records">/webservice/index.php?view=records</a></li>
      </ul>
    </section>

    <section class="card">
      <h2>Available operations</h2>
      <table>
        <thead><tr><th>Operation</th><th>Description</th><th>Sample</th></tr></thead>
        <tbody>
          <tr><td>pushInjuryData</td><td>Submit injury case data (full payload).</td><td><a href="#pushInjurySample">sample</a></td></tr>
          <tr><td>pushApirData</td><td>Submit APIR/sentinel event data.</td><td><a href="#pushApirSample">sample</a></td></tr>
          <tr><td>webInjury</td><td>Alternate entry for injury data.</td><td><a href="#webInjurySample">sample</a></td></tr>
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

    <section class="card" id="pushInjurySample">
      <h3>pushInjuryData — sample SOAP request</h3>
      <p>POST to <code>/webservice/index.php</code> with header <code>Content-Type: text/xml;charset=UTF-8</code></p>
      <pre><?php echo htmlentities('<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="http://oneiss.doh.gov.ph/webservice">
  <soapenv:Header/>
  <soapenv:Body>
    <urn:pushInjuryData>
      <Data>
        <Pat_Facility_No>DOH000000000000877</Pat_Facility_No>
        <Status>E</Status>
        <rstatuscode>V</rstatuscode>
        <date_report>2016-03-29</date_report>
        <time_report>08:30:00</time_report>
        <!-- ... many other fields ... -->
      </Data>
    </urn:pushInjuryData>
  </soapenv:Body>
</soapenv:Envelope>'); ?></pre>
    </section>

    <section class="card" id="pushApirSample">
      <h3>pushApirData — sample SOAP request</h3>
      <pre><?php echo htmlentities('<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="http://oneiss.doh.gov.ph/webservice">
  <soapenv:Header/>
  <soapenv:Body>
    <urn:pushApirData>
      <Data>
        <Pat_Facility_No>DOH000000000000877</Pat_Facility_No>
        <reg_no>NM201600100001</reg_no>
        <!-- ... many other fields ... -->
      </Data>
    </urn:pushApirData>
  </soapenv:Body>
</soapenv:Envelope>'); ?></pre>
    </section>

    <section class="card" id="webInjurySample">
      <h3>webInjury — sample SOAP request</h3>
      <pre><?php echo htmlentities('<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="http://oneiss.doh.gov.ph/webservice">
  <soapenv:Header/>
  <soapenv:Body>
    <urn:webInjury>
      <Data>
        <Pat_Facility_No>DOH000000000000877</Pat_Facility_No>
        <!-- ... -->
      </Data>
    </urn:webInjury>
  </soapenv:Body>
</soapenv:Envelope>'); ?></pre>
    </section>

    <footer style="margin-top:18px;color:#666;font-size:13px">Mock OneISS &copy; <?php echo date('Y'); ?> — For local testing only</footer>
  </div>
</body>
</html>
