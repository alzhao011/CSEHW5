<?php
$pageTitle = htmlspecialchars($report['title']);
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>';
$role = $_SESSION['role'] ?? '';
ob_start();
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h3><?= htmlspecialchars($report['title']) ?></h3>
        <span class="badge bg-secondary"><?= htmlspecialchars($report['category']) ?></span>
        <?= $report['is_published'] ? '<span class="badge bg-success">Published</span>' : '<span class="badge bg-warning text-dark">Draft</span>' ?>
        <small class="text-muted ms-2">by <?= htmlspecialchars($report['author']) ?> on <?= date('M j, Y', strtotime($report['created_at'])) ?></small>
    </div>
    <div>
        <a href="/export/print?id=<?= $report['id'] ?>" class="btn btn-outline-secondary btn-sm" target="_blank">Print / Export PDF</a>
        <?php if (in_array($role, ['super_admin','analyst'])): ?>
        <form method="POST" action="/export/save" class="d-inline">
        <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= $report['id'] ?>">
            <button class="btn btn-outline-dark btn-sm">Save Snapshot</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Analyst comments -->
<?php if ($report['comments']): ?>
<div class="card p-3 mb-4 border-start border-primary border-3">
    <h5>Analyst Comments</h5>
    <p><?= nl2br(htmlspecialchars($report['comments'])) ?></p>
</div>
<?php endif; ?>

<?php if ($report['export_path']): ?>
<div class="alert alert-light">Snapshot saved: <a href="<?= htmlspecialchars($report['export_path']) ?>" target="_blank"><?= htmlspecialchars($report['export_path']) ?></a></div>
<?php endif; ?>

<!-- Category-specific data -->
<?php if ($report['category'] === 'traffic'): ?>

<div class="row mb-4">
    <div class="col-md-3"><div class="card text-center p-3"><h4><?= number_format($summary['pageviews']) ?></h4><small class="text-muted">Page Views</small></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><h4><?= number_format($summary['sessions']) ?></h4><small class="text-muted">Sessions</small></div></div>
</div>

<div class="card p-3 mb-4"><h5>Sessions Over Time</h5><div style="height:300px"><canvas id="sessChart"></canvas></div></div>
<div class="card p-3 mb-4"><h5>Page Views</h5>
<table class="table table-bordered table-sm"><thead class="table-dark"><tr><th>Page</th><th>Views</th></tr></thead><tbody>
<?php foreach ($pageViews as $pv): ?><tr><td class="truncate"><?= htmlspecialchars($pv['page_url']) ?></td><td><?= $pv['views'] ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<script>
new Chart(document.getElementById('sessChart'),{type:'line',data:{labels:<?= json_encode(array_column($dailySess,'day')) ?>,datasets:[{label:'Sessions',data:<?= json_encode(array_map('intval',array_column($dailySess,'sessions'))) ?>,borderColor:'#0d6efd',fill:false}]},options:{responsive:true,maintainAspectRatio:false}});
</script>

<?php elseif ($report['category'] === 'behavioral'): ?>

<?php $countMap = []; foreach ($counts as $c) $countMap[$c['event_type']] = (int)$c['cnt']; ?>
<div class="row mb-4">
    <div class="col-md-3"><div class="card text-center p-3"><h4><?= number_format($countMap['click']??0) ?></h4><small class="text-muted">Clicks</small></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><h4><?= number_format($countMap['mousemove']??0) ?></h4><small class="text-muted">Mouse Moves</small></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><h4><?= number_format($countMap['keydown']??0) ?></h4><small class="text-muted">Keystrokes</small></div></div>
</div>
<div class="card p-3 mb-4"><h5>Interaction Breakdown</h5><div style="height:300px"><canvas id="bChart"></canvas></div></div>
<div class="card p-3 mb-4"><h5>Clicks by Page</h5>
<table class="table table-bordered table-sm"><thead class="table-dark"><tr><th>Page</th><th>Clicks</th></tr></thead><tbody>
<?php foreach ($clickPages as $cp): ?><tr><td class="truncate"><?= htmlspecialchars($cp['page_url']) ?></td><td><?= $cp['clicks'] ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<script>
new Chart(document.getElementById('bChart'),{type:'pie',data:{labels:<?= json_encode(array_column($counts,'event_type')) ?>,datasets:[{data:<?= json_encode(array_map('intval',array_column($counts,'cnt'))) ?>,backgroundColor:['#ff6384','#36a2eb','#ffce56']}]},options:{responsive:true,maintainAspectRatio:false}});
</script>

<?php elseif ($report['category'] === 'performance'): ?>

<?php
$avgLoad = $summary['avg_load'] ? round($summary['avg_load']) : 'N/A';
$avgTtfb = $summary['avg_ttfb'] ? round($summary['avg_ttfb']) : 'N/A';
$avgDom  = $summary['avg_dom']  ? round($summary['avg_dom'])  : 'N/A';
?>
<div class="row mb-4">
    <div class="col-md-3"><div class="card text-center p-3"><h4><?= number_format((int)$summary['samples']) ?></h4><small class="text-muted">Samples</small></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><h4><?= $avgLoad !== 'N/A' ? $avgLoad.'ms' : 'N/A' ?></h4><small class="text-muted">Avg Load Time</small></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><h4><?= $avgTtfb !== 'N/A' ? $avgTtfb.'ms' : 'N/A' ?></h4><small class="text-muted">Avg TTFB</small></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><h4><?= $avgDom !== 'N/A' ? $avgDom.'ms' : 'N/A' ?></h4><small class="text-muted">Avg DOM Ready</small></div></div>
</div>
<div class="card p-3 mb-4"><h5>Avg Load Time by Page</h5><div style="height:300px"><canvas id="loadChart"></canvas></div></div>
<div class="card p-3 mb-4"><h5>Load Time by Page</h5>
<table class="table table-bordered table-sm"><thead class="table-dark"><tr><th>Page</th><th>Samples</th><th>Avg Load</th><th>Min</th><th>Max</th><th>Avg TTFB</th><th>Avg DOM Ready</th></tr></thead><tbody>
<?php foreach ($byPage as $p): ?><tr>
    <td class="truncate"><?= htmlspecialchars($p['page_url']) ?></td>
    <td><?= $p['samples'] ?></td>
    <td><?= $p['avg_load']!==null?round($p['avg_load']).'ms':'N/A' ?></td>
    <td><?= $p['min_load']!==null?round($p['min_load']).'ms':'N/A' ?></td>
    <td><?= $p['max_load']!==null?round($p['max_load']).'ms':'N/A' ?></td>
    <td><?= $p['avg_ttfb']!==null?round($p['avg_ttfb']).'ms':'N/A' ?></td>
    <td><?= $p['avg_dom']!==null?round($p['avg_dom']).'ms':'N/A' ?></td>
</tr><?php endforeach; ?>
</tbody></table></div>
<script>
new Chart(document.getElementById('loadChart'),{type:'bar',data:{labels:<?= json_encode(array_map(function($p){$u=$p['page_url'];return strlen($u)>40?'...'.substr($u,-37):$u;},$byPage)) ?>,datasets:[{label:'Avg Load Time (ms)',data:<?= json_encode(array_map(function($p){return $p['avg_load']!==null?round($p['avg_load']):0;},$byPage)) ?>,backgroundColor:'#20c997'}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true}}}});
</script>

<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
