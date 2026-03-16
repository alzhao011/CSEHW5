<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($report['title']) ?> - Export</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <style>
        @media print { .no-print { display: none !important; } }
        td.truncate { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        body { font-size: 0.95rem; }
    </style>
</head>
<body class="p-4">

<div class="no-print mb-3">
    <button onclick="window.print()" class="btn btn-primary btn-sm">Print / Save as PDF</button>
    <form method="POST" action="/export/save" class="d-inline">
    <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= $report['id'] ?>">
        <button class="btn btn-outline-dark btn-sm">Save Snapshot (accessible URL)</button>
    </form>
    <a href="/reports/view?id=<?= $report['id'] ?>" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

<h2><?= htmlspecialchars($report['title']) ?></h2>
<p><strong>Category:</strong> <?= ucfirst($report['category']) ?> &nbsp;|&nbsp;
   <strong>Author:</strong> <?= htmlspecialchars($report['author']) ?> &nbsp;|&nbsp;
   <strong>Date:</strong> <?= date('F j, Y', strtotime($report['created_at'])) ?></p>
<hr>

<?php if ($report['comments']): ?>
<div class="mb-4">
    <h5>Analyst Comments</h5>
    <p><?= nl2br(htmlspecialchars($report['comments'])) ?></p>
</div>
<hr>
<?php endif; ?>

<?php if ($report['category'] === 'traffic'): ?>

<div class="row mb-4">
    <div class="col-4"><div class="card text-center p-2"><h5><?= number_format($summary['pageviews']) ?></h5><small>Page Views</small></div></div>
    <div class="col-4"><div class="card text-center p-2"><h5><?= number_format($summary['sessions']) ?></h5><small>Sessions</small></div></div>
    <div class="col-4"><div class="card text-center p-2"><h5><?= count($pageViews) ?></h5><small>Unique Pages</small></div></div>
</div>

<h5>Sessions Over Time</h5>
<div style="height:300px"><canvas id="expSessChart"></canvas></div>
<br>
<h5>Page Views</h5>
<table class="table table-bordered table-sm">
    <thead class="table-dark"><tr><th>Page</th><th>Views</th></tr></thead>
    <tbody>
        <?php foreach ($pageViews as $pv): ?>
        <tr><td class="truncate"><?= htmlspecialchars($pv['page_url']) ?></td><td><?= $pv['views'] ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table>
<script>
new Chart(document.getElementById('expSessChart'),{type:'line',data:{labels:<?= json_encode(array_column($dailySess,'day')) ?>,datasets:[{label:'Sessions',data:<?= json_encode(array_map('intval',array_column($dailySess,'sessions'))) ?>,borderColor:'#0d6efd',fill:false}]},options:{responsive:true,maintainAspectRatio:false}});
</script>

<?php elseif ($report['category'] === 'behavioral'): ?>

<?php $countMap = []; foreach ($counts as $c) $countMap[$c['event_type']] = (int)$c['cnt']; ?>
<div class="row mb-4">
    <div class="col-4"><div class="card text-center p-2"><h5><?= number_format($countMap['click']??0) ?></h5><small>Clicks</small></div></div>
    <div class="col-4"><div class="card text-center p-2"><h5><?= number_format($countMap['mousemove']??0) ?></h5><small>Mouse Moves</small></div></div>
    <div class="col-4"><div class="card text-center p-2"><h5><?= number_format($countMap['keydown']??0) ?></h5><small>Keystrokes</small></div></div>
</div>
<h5>Interaction Breakdown</h5>
<div style="height:300px"><canvas id="expBChart"></canvas></div>
<br>
<h5>Clicks by Page</h5>
<table class="table table-bordered table-sm">
    <thead class="table-dark"><tr><th>Page</th><th>Clicks</th></tr></thead>
    <tbody><?php foreach ($clickPages as $cp): ?><tr><td class="truncate"><?= htmlspecialchars($cp['page_url']) ?></td><td><?= $cp['clicks'] ?></td></tr><?php endforeach; ?></tbody>
</table>
<script>
new Chart(document.getElementById('expBChart'),{type:'pie',data:{labels:<?= json_encode(array_column($counts,'event_type')) ?>,datasets:[{data:<?= json_encode(array_map('intval',array_column($counts,'cnt'))) ?>,backgroundColor:['#ff6384','#36a2eb','#ffce56']}]},options:{responsive:true,maintainAspectRatio:false}});
</script>

<?php elseif ($report['category'] === 'performance'): ?>

<?php $avgLoad = $summary['avg_load'] ? round($summary['avg_load']) : 'N/A'; ?>
<div class="row mb-4">
    <div class="col-4"><div class="card text-center p-2"><h5><?= (int)$summary['samples'] ?></h5><small>Samples</small></div></div>
    <div class="col-4"><div class="card text-center p-2"><h5><?= $avgLoad !== 'N/A' ? $avgLoad.'ms' : 'N/A' ?></h5><small>Avg Load Time</small></div></div>
</div>
<h5>Performance by Page</h5>
<table class="table table-bordered table-sm">
    <thead class="table-dark"><tr><th>Page</th><th>Samples</th><th>Avg (ms)</th><th>Min</th><th>Max</th></tr></thead>
    <tbody><?php foreach ($byPage as $p): ?><tr><td class="truncate"><?= htmlspecialchars($p['page_url']) ?></td><td><?= $p['samples'] ?></td><td><?= $p['avg_load']!==null?round($p['avg_load']):'N/A' ?></td><td><?= $p['min_load']!==null?round($p['min_load']):'N/A' ?></td><td><?= $p['max_load']!==null?round($p['max_load']):'N/A' ?></td></tr><?php endforeach; ?></tbody>
</table>

<?php endif; ?>

</body>
</html>
