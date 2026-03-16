<?php
$pageTitle = 'Performance Report';
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>';
ob_start();

$avgLoad = $summary['avg_load'] ? round($summary['avg_load']) : 'N/A';
$avgTtfb = $summary['avg_ttfb'] ? round($summary['avg_ttfb']) : 'N/A';
$avgDom  = $summary['avg_dom']  ? round($summary['avg_dom'])  : 'N/A';
?>

<h3>Performance Report</h3>
<br>
<?php require __DIR__ . '/_date_filter.php'; ?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center p-3">
            <h4><?= number_format((int)$summary['samples']) ?></h4>
            <small class="text-muted">Samples Collected</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3">
            <h4><?= $avgLoad !== 'N/A' ? number_format($avgLoad).'ms' : 'N/A' ?></h4>
            <small class="text-muted">Avg Load Time</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3">
            <h4><?= $avgTtfb !== 'N/A' ? number_format($avgTtfb).'ms' : 'N/A' ?></h4>
            <small class="text-muted">Avg TTFB</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3">
            <h4><?= $avgDom !== 'N/A' ? number_format($avgDom).'ms' : 'N/A' ?></h4>
            <small class="text-muted">Avg DOM Ready</small>
        </div>
    </div>
</div>

<!-- Avg load by page chart -->
<div class="card p-3 mb-4">
    <h5>Avg Load Time by Page (ms)</h5>
    <div style="height:400px"><canvas id="loadChart"></canvas></div>
    <noscript>
        <table class="table table-bordered table-sm mt-2">
            <thead class="table-dark"><tr><th>Page URL</th><th>Avg Load (ms)</th></tr></thead>
            <tbody>
                <?php foreach ($byPage as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['page_url']) ?></td>
                    <td><?= $p['avg_load'] !== null ? round($p['avg_load']) : 'N/A' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </noscript>
</div>

<!-- Raw performance table -->
<div class="card p-3 mb-4">
    <h5>Performance Data by Page</h5>
    <table class="table table-bordered table-striped table-sm">
        <thead class="table-dark">
            <tr><th>Page URL</th><th>Samples</th><th>Avg Load</th><th>Min Load</th><th>Max Load</th><th>Avg TTFB</th><th>Avg DOM Ready</th></tr>
        </thead>
        <tbody>
            <?php foreach ($byPage as $p): ?>
            <tr>
                <td class="truncate"><?= htmlspecialchars($p['page_url']) ?></td>
                <td><?= (int)$p['samples'] ?></td>
                <td><?= $p['avg_load'] !== null ? round($p['avg_load']).'ms' : 'N/A' ?></td>
                <td><?= $p['min_load'] !== null ? round($p['min_load']).'ms' : 'N/A' ?></td>
                <td><?= $p['max_load'] !== null ? round($p['max_load']).'ms' : 'N/A' ?></td>
                <td><?= $p['avg_ttfb'] !== null ? round($p['avg_ttfb']).'ms' : 'N/A' ?></td>
                <td><?= $p['avg_dom']  !== null ? round($p['avg_dom']).'ms'  : 'N/A' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Save report form -->
<?php if (in_array($_SESSION['role'] ?? '', ['super_admin','analyst'])): ?>
<div class="card p-3 mb-4">
    <h5>Save this Report</h5>
    <form method="POST" action="/reports/save">
        <?= csrfField() ?>
        <input type="hidden" name="category" value="performance">
        <div class="mb-2">
            <label class="form-label">Report Title</label>
            <input type="text" name="title" class="form-control" required placeholder="e.g. Performance Review - March 2026">
        </div>
        <div class="mb-2">
            <label class="form-label">Analyst Comments</label>
            <textarea name="comments" class="form-control" rows="4" placeholder="Write your analysis here..."></textarea>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="publish" id="publish" value="1">
            <label class="form-check-label" for="publish">Publish (make visible to viewers)</label>
        </div>
        <button type="submit" class="btn btn-primary">Save Report</button>
    </form>
</div>
<?php endif; ?>

<script>
var loadLabels = <?= json_encode(array_map(function($p) {
    $u = $p['page_url']; return strlen($u)>40?'...'.substr($u,-37):$u;
}, $byPage)) ?>;
var loadData = <?= json_encode(array_map(function($p) {
    return $p['avg_load'] !== null ? round($p['avg_load']) : 0;
}, $byPage)) ?>;

new Chart(document.getElementById('loadChart'), {
    type: 'bar',
    data: {
        labels: loadLabels,
        datasets: [{ label: 'Avg Load Time (ms)', data: loadData, backgroundColor: '#20c997' }]
    },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
