<?php
$pageTitle = 'Traffic Report';
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>';
ob_start();
?>

<h3>Traffic Report</h3>
<br>
<?php require __DIR__ . '/_date_filter.php'; ?>

<!-- Summary stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center p-3">
            <h4><?= number_format($summary['pageviews']) ?></h4>
            <small class="text-muted">Page Views</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3">
            <h4><?= number_format($summary['sessions']) ?></h4>
            <small class="text-muted">Unique Sessions</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3">
            <h4><?= count($pageViews) ?></h4>
            <small class="text-muted">Unique Pages</small>
        </div>
    </div>
</div>

<!-- Sessions over time -->
<div class="card p-3 mb-4">
    <h5>Sessions Over Time</h5>
    <div style="height:350px"><canvas id="sessionsChart"></canvas></div>
</div>

<!-- Page views bar chart -->
<div class="card p-3 mb-4">
    <h5>Page Views by URL</h5>
    <div style="height:400px"><canvas id="pageViewsChart"></canvas></div>
</div>

<!-- Device breakdown -->
<div class="card p-3 mb-4">
    <h5>Device Type Breakdown</h5>
    <div style="height:350px"><canvas id="deviceChart"></canvas></div>
</div>

<!-- Raw page view table -->
<div class="card p-3 mb-4">
    <h5>Page Views Table</h5>
    <table class="table table-bordered table-striped table-sm">
        <thead class="table-dark">
            <tr><th>Page URL</th><th>Views</th></tr>
        </thead>
        <tbody>
            <?php foreach ($pageViews as $pv): ?>
            <tr>
                <td class="truncate"><?= htmlspecialchars($pv['page_url']) ?></td>
                <td><?= number_format($pv['views']) ?></td>
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
        <input type="hidden" name="category" value="traffic">
        <div class="mb-2">
            <label class="form-label">Report Title</label>
            <input type="text" name="title" class="form-control" required placeholder="e.g. Traffic Analysis - March 2026">
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
new Chart(document.getElementById('sessionsChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($dailySess, 'day')) ?>,
        datasets: [{
            label: 'Sessions',
            data: <?= json_encode(array_map('intval', array_column($dailySess, 'sessions'))) ?>,
            borderColor: '#0d6efd',
            fill: false
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

var pvLabels = <?= json_encode(array_map(function($p) {
    $u = $p['page_url'];
    return strlen($u) > 40 ? '...'.substr($u,-37) : $u;
}, $pageViews)) ?>;
new Chart(document.getElementById('pageViewsChart'), {
    type: 'bar',
    data: {
        labels: pvLabels,
        datasets: [{ label: 'Views', data: <?= json_encode(array_map('intval', array_column($pageViews,'views'))) ?>, backgroundColor: '#0d6efd' }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

new Chart(document.getElementById('deviceChart'), {
    type: 'pie',
    data: {
        labels: ['Mobile (≤768px)', 'Tablet (769-1280px)', 'Desktop (>1280px)'],
        datasets: [{ data: [<?= (int)$devices['mobile'] ?>, <?= (int)$devices['tablet'] ?>, <?= (int)$devices['desktop'] ?>], backgroundColor: ['#ff6384','#36a2eb','#ffce56'] }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
