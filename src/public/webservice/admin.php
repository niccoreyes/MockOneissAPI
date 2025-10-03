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

// Pagination simple
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 25;
$offset = ($page-1)*$per;

$stmt = $pdo->prepare("SELECT id, operation, payload, response, created_at FROM records $whereSql ORDER BY created_at DESC LIMIT :lim OFFSET :off");
foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
$stmt->bindValue(':lim',$per,PDO::PARAM_INT);
$stmt->bindValue(':off',$offset,PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total
$stmt2 = $pdo->prepare("SELECT COUNT(1) as c FROM records $whereSql");
$stmt2->execute($params);
$total = (int)$stmt2->fetch(PDO::FETCH_ASSOC)['c'];
$totalPages = max(1, ceil($total / $per));

?><!doctype html>
<html><head><meta charset="utf-8"><title>ONEISS Admin - Records</title>
<style>body{font-family:Arial;margin:16px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:8px;text-align:left}pre{white-space:pre-wrap;max-height:200px;overflow:auto}</style>
</head><body>
<h1>ONEISS - Stored Records</h1>
<form method="get">
  <label>Operation: <select name="operation"><option value="">(any)</option><option value="pushInjuryData"<?php if($operation==='pushInjuryData') echo ' selected';?>>pushInjuryData</option><option value="pushApirData"<?php if($operation==='pushApirData') echo ' selected';?>>pushApirData</option><option value="webInjury"<?php if($operation==='webInjury') echo ' selected';?>>webInjury</option></select></label>
  <label> Search: <input name="q" value="<?php echo htmlspecialchars($q); ?>"></label>
  <label> From: <input name="from" type="datetime-local" value="<?php echo htmlspecialchars($from); ?>"></label>
  <label> To: <input name="to" type="datetime-local" value="<?php echo htmlspecialchars($to); ?>"></label>
  <button type="submit">Filter</button>
  <a href="?<?php echo http_build_query(array_merge($_GET,['export'=>'csv'])); ?>">Export CSV</a>
</form>
<p>Showing <?php echo count($rows); ?> of <?php echo $total; ?> records. Page <?php echo $page; ?> / <?php echo $totalPages; ?></p>
<table>
<thead><tr><th>ID</th><th>Operation</th><th>Created At</th><th>Payload</th><th>Response</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
  <tr>
    <td><?php echo htmlspecialchars($r['id']); ?></td>
    <td><?php echo htmlspecialchars($r['operation']); ?></td>
    <td><?php echo htmlspecialchars($r['created_at']); ?></td>
    <td><pre><?php echo htmlspecialchars($r['payload']); ?></pre></td>
    <td><pre><?php echo htmlspecialchars($r['response']); ?></pre></td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>
<div>
<?php if ($page>1): ?><a href="?<?php $qp=$_GET; $qp['page']=$page-1; echo http_build_query($qp); ?>">Prev</a><?php endif; ?>
<?php if ($page<$totalPages): ?><a href="?<?php $qp=$_GET; $qp['page']=$page+1; echo http_build_query($qp); ?>">Next</a><?php endif; ?>
</div>
<p><a href="/webservice/">Back to API docs</a></p>
</body></html>
