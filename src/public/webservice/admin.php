<?php
// Admin UI for records: search, filter, CSV export
$dbFile = __DIR__ . '/../../data/oneiss.db';
if (!file_exists($dbFile)) {
    echo "<p>No records DB found. Submit some requests first.</p>";
    exit;
}
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Params
$operation = isset($_GET['operation']) ? $_GET['operation'] : '';
$q = isset($_GET['q']) ? $_GET['q'] : '';
$from = isset($_GET['from']) ? $_GET['from'] : '';
$to = isset($_GET['to']) ? $_GET['to'] : '';
$export = isset($_GET['export']) ? $_GET['export'] : '';

$where = [];
$params = [];
if ($operation !== '') { $where[] = 'operation = :op'; $params[':op'] = $operation; }
if ($q !== '') { $where[] = '(payload LIKE :q OR response LIKE :q)'; $params[':q'] = '%' . $q . '%'; }
if ($from !== '') { $where[] = 'created_at >= :from'; $params[':from'] = $from; }
if ($to !== '') { $where[] = 'created_at <= :to'; $params[':to'] = $to; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

if ($export === 'csv') {
    // Export matching rows as CSV
    $stmt = $pdo->prepare("SELECT id, operation, payload, response, created_at FROM records $whereSql ORDER BY created_at DESC");
    $stmt->execute($params);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="oneiss_records.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id','operation','created_at','payload','response']);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [$row['id'],$row['operation'],$row['created_at'],$row['payload'],$row['response']]);
    }
    fclose($out);
    exit;
}

// ---------- Helpers ----------
function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function shortPreview($s, $limit = 140) {
    $t = trim((string)$s);
    if ($t === '') return '<span class="muted">empty</span>';
    if (mb_strlen($t) > $limit) { $t = mb_substr($t, 0, $limit) . '…'; }
    return esc($t);
}
function tryParseJson($txt) {
    $data = json_decode((string)$txt, true);
    return (json_last_error() === JSON_ERROR_NONE && is_array($data)) ? $data : null;
}
function tryParseXml($txt) {
    if (strpos(ltrim((string)$txt), '<') !== 0) return null;
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string((string)$txt);
    return $xml ?: null;
}
function renderKV($data) {
    if (!is_array($data)) return '<code>'.esc(var_export($data, true)).'</code>';
    $out = '<ul class="kv">';
    foreach ($data as $k => $v) {
        $key = esc((string)$k);
        if (is_array($v)) {
            $out .= '<li><span class="k">'.$key.'</span>'.renderKV($v).'</li>';
        } else if (is_bool($v)) {
            $out .= '<li><span class="k">'.$key.'</span><span class="v v-bool">'.($v?'true':'false').'</span></li>';
        } else if (is_null($v)) {
            $out .= '<li><span class="k">'.$key.'</span><span class="v v-null">null</span></li>';
        } else if (is_numeric($v)) {
            $out .= '<li><span class="k">'.$key.'</span><span class="v v-num">'.esc((string)$v).'</span></li>';
        } else {
            $out .= '<li><span class="k">'.$key.'</span><span class="v">'.esc((string)$v).'</span></li>';
        }
    }
    $out .= '</ul>';
    return $out;
}
function xmlToArray($xml) {
    $arr = [];
    foreach ((array)$xml as $k => $v) {
        if ($v instanceof SimpleXMLElement) {
            $arr[$k] = xmlToArray($v);
        } else if (is_array($v)) {
            $arr[$k] = array_map(function($item){ return $item instanceof SimpleXMLElement ? xmlToArray($item) : $item; }, $v);
        } else {
            $arr[$k] = $v;
        }
    }
    // include text content if no children
    if (empty($arr)) {
        $str = trim((string)$xml);
        if ($str !== '') return $str; else return [];
    }
    return $arr;
}
function prettyXml($txt) {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    if (@$dom->loadXML((string)$txt)) {
        return $dom->saveXML($dom->documentElement);
    }
    return null;
}
function opClass($op) {
    $map = [
        'pushApirData' => 'op-apir',
        'pushInjuryData' => 'op-injury',
        'webInjury' => 'op-web',
    ];
    return $map[$op] ?? 'op-other';
}

// ---------- Query ----------
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 25;
$offset = ($page-1)*$per;

$stmt = $pdo->prepare("SELECT id, operation, payload, response, created_at FROM records $whereSql ORDER BY created_at DESC LIMIT :lim OFFSET :off");
foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
$stmt->bindValue(':lim',$per,PDO::PARAM_INT);
$stmt->bindValue(':off',$offset,PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $pdo->prepare("SELECT COUNT(1) as c FROM records $whereSql");
$stmt2->execute($params);
$total = (int)$stmt2->fetch(PDO::FETCH_ASSOC)['c'];
$totalPages = max(1, ceil($total / $per));

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ONEISS Admin - Records</title>
  <style>
    :root {
      --bg: #0b1215; --surface-0: #0e161a; --surface-1: #121c21; --surface-2: #17242b;
      --text: #e6edf3; --muted: #9fb1bd; --border: #23333c; --accent: #4fb3ff;
      --success: #1fca7b; --warning: #ffb020; --danger: #ff6b6b;
      --shadow: 0 1px 2px rgba(0,0,0,0.12), 0 6px 16px rgba(0,0,0,0.18);
    }
    @media (prefers-color-scheme: light) {
      :root { --bg:#f6fafc; --surface-0:#fff; --surface-1:#fff; --surface-2:#f1f6f9; --text:#0b2230; --muted:#5b7a8a; --border:#dde7ee; --accent:#0b8bd9; --success:#0fa968; --warning:#c07b00; --danger:#d64545; }
    }
    html, body { height: 100%; }
    body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial; color: var(--text); background: linear-gradient(180deg, var(--bg), var(--surface-2)); }
    .container { max-width: 1200px; margin: 0 auto; padding: 24px 16px 48px; }
    header h1 { margin: 0 0 16px; font-size: 24px; letter-spacing: .2px; }
    .card { background: var(--surface-1); border: 1px solid var(--border); border-radius: 12px; box-shadow: var(--shadow); }
    .card + .card { margin-top: 16px; }

    /* Filters */
    .filters { display: grid; grid-template-columns: repeat(12, 1fr); gap: 12px; padding: 16px; align-items: end; }
    .filters .field { grid-column: span 3; }
    .filters .field.wide { grid-column: span 6; }
    @media (max-width: 900px){ .filters .field, .filters .field.wide { grid-column: span 12; } }
    label { display: block; font-size: 12px; color: var(--muted); margin-bottom: 6px; }
    select, input[type="text"], input[type="datetime-local"] { width: 100%; padding: 10px 12px; border: 1px solid var(--border); background: var(--surface-0); color: var(--text); border-radius: 10px; outline: none; }
    .actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface-0); color: var(--text); text-decoration: none; cursor: pointer; transition: transform .06s ease, background .2s ease, border-color .2s ease; }
    .btn:hover { transform: translateY(-1px); background: var(--surface-2); }
    .btn.primary { background: linear-gradient(180deg, var(--accent), #2b9be9); color: #fff; border-color: transparent; }
    .btn.primary:hover { filter: brightness(1.02); }
    .btn.ghost { background: transparent; }

    .meta { padding: 12px 16px; color: var(--muted); font-size: 13px; display:flex; justify-content: space-between; align-items:center; border-top: 1px solid var(--border); }

    /* Table */
    .table-wrap { overflow: auto; }
    table.records { width: 100%; border-collapse: separate; border-spacing: 0; }
    thead th { position: sticky; top: 0; background: var(--surface-2); color: var(--muted); font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: .4px; border-bottom: 1px solid var(--border); padding: 12px; z-index: 1; }
    tbody td { padding: 12px; border-bottom: 1px solid var(--border); vertical-align: top; }
    tbody tr:hover { background: rgba(79,179,255,.06); }
    td.id { font-variant-numeric: tabular-nums; color: var(--muted); width: 80px; }
    td.op { width: 160px; }
    td.created { white-space: nowrap; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
    td.payload, td.response { min-width: 320px; }
    .preview { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; color: var(--muted); max-height: 3.2em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }

    /* Expandable rows */
    tr.row-main { cursor: pointer; }
    tr.row-main.open { background: rgba(79,179,255,.08); }
    tr.detail-row { display: none; }
    tr.row-main.open + tr.detail-row { display: table-row; }
    .detail-cell { padding: 0 12px 12px; background: var(--surface-2); }
    .detail-card { margin-top: 0; background: var(--surface-1); border: 1px solid var(--border); border-radius: 12px; padding: 16px; }
    .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 900px){ .detail-grid { grid-template-columns: 1fr; } }
    .pane h4 { margin: 0 0 8px; font-size: 14px; color: var(--muted); }
    pre { white-space: pre-wrap; max-height: 320px; overflow: auto; background: var(--surface-0); border: 1px solid var(--border); padding: 12px; border-radius: 10px; }
    .muted { color: var(--muted); }

    /* Badges */
    .badge { display: inline-flex; align-items:center; gap:8px; padding: 6px 10px; border-radius: 999px; font-weight: 600; font-size: 12px; border: 1px solid var(--border); background: var(--surface-0); }
    .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .op-apir .dot { background: var(--accent); }
    .op-injury .dot { background: var(--warning); }
    .op-web .dot { background: var(--success); }
    .op-other .dot { background: var(--muted); }

    /* KV tree */
    .kv { list-style: none; margin: 0; padding-left: 0; }
    .kv ul.kv { margin-left: 16px; }
    .kv > li { padding: 6px 8px; border: 1px solid var(--border); background: var(--surface-0); border-radius: 8px; margin: 6px 0; }
    .kv .k { font-weight: 600; margin-right: 6px; color: var(--text); }
    .kv .v { color: var(--muted); word-break: break-word; }
    .kv .v-num { color: #7bdba7; }
    .kv .v-bool { color: #f0a020; }
    .kv .v-null { color: #c98bdb; }

    /* Pagination */
    .pager { display: flex; gap: 8px; align-items: center; padding: 12px 16px; }
    .pager .btn[aria-disabled="true"] { opacity: .5; pointer-events: none; }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <h1>ONEISS - Stored Records</h1>
    </header>

    <section class="card">
      <form class="filters" method="get">
        <div class="field">
          <label for="operation">Operation</label>
          <select name="operation" id="operation">
            <option value=""<?php if($operation==='') echo ' selected';?>>(any)</option>
            <option value="pushInjuryData"<?php if($operation==='pushInjuryData') echo ' selected';?>>pushInjuryData</option>
            <option value="pushApirData"<?php if($operation==='pushApirData') echo ' selected';?>>pushApirData</option>
            <option value="webInjury"<?php if($operation==='webInjury') echo ' selected';?>>webInjury</option>
          </select>
        </div>
        <div class="field wide">
          <label for="q">Search</label>
          <input type="text" id="q" name="q" value="<?php echo esc($q); ?>" placeholder="Search payload or response...">
        </div>
        <div class="field">
          <label for="from">From</label>
          <input type="datetime-local" id="from" name="from" value="<?php echo esc($from); ?>">
        </div>
        <div class="field">
          <label for="to">To</label>
          <input type="datetime-local" id="to" name="to" value="<?php echo esc($to); ?>">
        </div>
        <div class="actions field wide">
          <button class="btn primary" type="submit">Filter</button>
          <a class="btn" href="?<?php echo http_build_query(array_merge($_GET,['export'=>'csv'])); ?>">Export CSV</a>
          <a class="btn ghost" href="<?php echo esc(strtok($_SERVER['REQUEST_URI'], '?')); ?>">Clear</a>
          <a class="btn ghost" href="/webservice/">Back to API docs</a>
        </div>
      </form>
      <div class="meta">
        <span>Showing <?php echo count($rows); ?> of <?php echo $total; ?> records</span>
        <span>Page <?php echo $page; ?> / <?php echo $totalPages; ?></span>
      </div>
    </section>

    <section class="card">
      <div class="table-wrap">
        <table class="records">
          <thead>
            <tr>
              <th>ID</th>
              <th>Operation</th>
              <th>Created At</th>
              <th>Payload</th>
              <th>Response</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $r): $id = (int)$r['id']; ?>
            <?php
              $payloadPreview = shortPreview($r['payload']);
              $responsePreview = shortPreview($r['response']);
              $payloadJson = tryParseJson($r['payload']);
              $payloadXml = $payloadJson ? null : tryParseXml($r['payload']);
              $responseXml = tryParseXml($r['response']);
              $responseJson = $responseXml ? null : tryParseJson($r['response']);
              $prettyResponseXml = $responseXml ? prettyXml($r['response']) : null;
            ?>
            <tr class="row-main" data-id="<?php echo $id; ?>" aria-expanded="false">
              <td class="id"><?php echo esc($r['id']); ?></td>
              <td class="op">
                <?php $cls = opClass($r['operation']); ?>
                <span class="badge <?php echo $cls; ?>">
                  <span class="dot"></span>
                  <?php echo esc($r['operation']); ?>
                </span>
              </td>
              <td class="created"><?php echo esc($r['created_at']); ?></td>
              <td class="payload"><div class="preview"><?php echo $payloadPreview; ?></div></td>
              <td class="response"><div class="preview"><?php echo $responsePreview; ?></div></td>
            </tr>
            <tr class="detail-row" data-for="<?php echo $id; ?>">
              <td class="detail-cell" colspan="5">
                <div class="detail-card">
                  <div class="detail-grid">
                    <div class="pane">
                      <h4>Payload</h4>
                      <?php if ($payloadJson !== null): ?>
                        <?php echo renderKV($payloadJson); ?>
                        <details style="margin-top:8px"><summary>Raw</summary><pre><?php echo esc($r['payload']); ?></pre></details>
                      <?php elseif ($payloadXml): ?>
                        <?php $payloadArr = xmlToArray($payloadXml); echo renderKV(is_array($payloadArr)?$payloadArr:['xml'=>$payloadArr]); ?>
                        <details style="margin-top:8px"><summary>Raw XML</summary><pre><?php echo esc($r['payload']); ?></pre></details>
                      <?php else: ?>
                        <pre><?php echo esc($r['payload']); ?></pre>
                      <?php endif; ?>
                    </div>
                    <div class="pane">
                      <h4>Response</h4>
                      <?php if ($responseJson !== null): ?>
                        <?php echo renderKV($responseJson); ?>
                        <details style="margin-top:8px"><summary>Raw</summary><pre><?php echo esc($r['response']); ?></pre></details>
                      <?php elseif ($responseXml): ?>
                        <?php $respArr = xmlToArray($responseXml); echo renderKV(is_array($respArr)?$respArr:['xml'=>$respArr]); ?>
                        <details style="margin-top:8px"><summary>Pretty XML</summary><pre><?php echo esc($prettyResponseXml ?? (string)$r['response']); ?></pre></details>
                      <?php else: ?>
                        <pre><?php echo esc($r['response']); ?></pre>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="pager">
        <?php if ($page>1): ?>
          <a class="btn" href="?<?php $qp=$_GET; $qp['page']=$page-1; echo http_build_query($qp); ?>">◀ Prev</a>
        <?php else: ?>
          <span class="btn" aria-disabled="true">◀ Prev</span>
        <?php endif; ?>

        <?php if ($page<$totalPages): ?>
          <a class="btn" href="?<?php $qp=$_GET; $qp['page']=$page+1; echo http_build_query($qp); ?>">Next ▶</a>
        <?php else: ?>
          <span class="btn" aria-disabled="true">Next ▶</span>
        <?php endif; ?>
      </div>
    </section>
  </div>
  <script>
    // Row expand/collapse
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('tr.row-main').forEach(tr => {
        tr.addEventListener('click', (e) => {
          if (e.target.closest('a,button,summary,details')) return; // don't toggle when interacting with controls
          tr.classList.toggle('open');
          tr.setAttribute('aria-expanded', tr.classList.contains('open') ? 'true' : 'false');
        });
      });
    });
  </script>
</body>
</html>
