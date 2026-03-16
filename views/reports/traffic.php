<?php
$pageTitle = 'Traffic Report';
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>';
ob_start();

$bounceRate = 0;
if (!empty($sessionStats['total_sessions']) && $sessionStats['total_sessions'] > 0) {
    $bounceRate = round(($sessionStats['bounced'] / $sessionStats['total_sessions']) * 100);
}
$avgPages = $sessionStats['avg_pages'] ?? '—';
?>

<h3>Traffic Report</h3>
<br>
<?php require __DIR__ . '/_date_filter.php'; ?>

<!-- Summary stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center p-3">
            <h4 data-count="<?= (int)$summary['pageviews'] ?>"><?= number_format($summary['pageviews']) ?></h4>
            <small class="text-muted">Page Views</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3">
            <h4 data-count="<?= (int)$summary['sessions'] ?>"><?= number_format($summary['sessions']) ?></h4>
            <small class="text-muted">Unique Sessions</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3">
            <h4 data-count="<?= count($pageViews) ?>"><?= count($pageViews) ?></h4>
            <small class="text-muted">Unique Pages</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3">
            <h4><?= $bounceRate ?>%</h4>
            <small class="text-muted">Bounce Rate</small>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card text-center p-3">
            <h4><?= $avgPages !== '—' ? number_format((float)$avgPages, 1) : '—' ?></h4>
            <small class="text-muted">Avg Pages / Session</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card text-center p-3">
            <h4 data-count="<?= (int)($devices['desktop'] ?? 0) + (int)($devices['tablet'] ?? 0) + (int)($devices['mobile'] ?? 0) ?>"><?= number_format((int)($devices['desktop'] ?? 0) + (int)($devices['tablet'] ?? 0) + (int)($devices['mobile'] ?? 0)) ?></h4>
            <small class="text-muted">Total Device Events</small>
        </div>
    </div>
</div>

<!-- Sessions over time -->
<div class="card p-3 mb-4">
    <h5>Sessions Over Time</h5>
    <div style="height:350px"><canvas id="sessionsChart"></canvas></div>
    <noscript>
        <table class="table table-bordered table-sm mt-2">
            <thead class="table-dark"><tr><th>Date</th><th>Sessions</th></tr></thead>
            <tbody>
                <?php foreach ($dailySess as $d): ?>
                <tr><td><?= htmlspecialchars($d['day']) ?></td><td><?= (int)$d['sessions'] ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </noscript>
</div>

<!-- Activity heatmap -->
<div class="card p-3 mb-4">
    <h5>Activity by Hour &amp; Day</h5>
    <p class="text-muted small mb-2">Page views grouped by day of week and hour. Darker = more activity.</p>
    <div id="activityGrid"></div>
    <noscript><p class="text-muted">JavaScript required to render the activity heatmap.</p></noscript>
</div>

<!-- Page views bar chart -->
<div class="card p-3 mb-4">
    <h5>Page Views by URL</h5>
    <div style="height:400px"><canvas id="pageViewsChart"></canvas></div>
    <noscript>
        <table class="table table-bordered table-sm mt-2">
            <thead class="table-dark"><tr><th>Page URL</th><th>Views</th></tr></thead>
            <tbody>
                <?php foreach ($pageViews as $pv): ?>
                <tr><td><?= htmlspecialchars($pv['page_url']) ?></td><td><?= number_format((int)$pv['views']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </noscript>
</div>

<!-- Device breakdown -->
<div class="card p-3 mb-4">
    <h5>Device Type Breakdown</h5>
    <div style="height:350px"><canvas id="deviceChart"></canvas></div>
    <noscript>
        <table class="table table-bordered table-sm mt-2">
            <thead class="table-dark"><tr><th>Device</th><th>Sessions</th></tr></thead>
            <tbody>
                <tr><td>Mobile (&le;768px)</td><td><?= number_format((int)$devices['mobile']) ?></td></tr>
                <tr><td>Tablet (769-1280px)</td><td><?= number_format((int)$devices['tablet']) ?></td></tr>
                <tr><td>Desktop (&gt;1280px)</td><td><?= number_format((int)$devices['desktop']) ?></td></tr>
            </tbody>
        </table>
    </noscript>
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
            backgroundColor: 'rgba(13,110,253,0.08)',
            fill: true,
            tension: 0.3,
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
});

var pvLabels = <?= json_encode(array_map(function($p) {
    $u = $p['page_url'];
    return strlen($u) > 40 ? '...'.substr($u,-37) : $u;
}, $pageViews)) ?>;
new Chart(document.getElementById('pageViewsChart'), {
    type: 'bar',
    data: {
        labels: pvLabels,
        datasets: [{ label: 'Views', data: <?= json_encode(array_map('intval', array_column($pageViews,'views'))) ?>, backgroundColor: '#0d6efd', borderRadius: 4 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('deviceChart'), {
    type: 'doughnut',
    data: {
        labels: ['Mobile (≤768px)', 'Tablet (769-1280px)', 'Desktop (>1280px)'],
        datasets: [{ data: [<?= (int)$devices['mobile'] ?>, <?= (int)$devices['tablet'] ?>, <?= (int)$devices['desktop'] ?>], backgroundColor: ['#ff6384','#36a2eb','#ffce56'], borderWidth: 2 }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

// Activity heatmap grid
(function () {
    var raw = <?= json_encode($heatmap) ?>;
    var grid = {}, maxVal = 1;
    raw.forEach(function (d) {
        var key = (+d.dow) + '_' + (+d.hr);
        grid[key] = +d.cnt;
        if (+d.cnt > maxVal) maxVal = +d.cnt;
    });
    // MySQL DAYOFWEEK: 1=Sun,2=Mon,...,7=Sat
    var dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    var html = '<div style="overflow-x:auto"><table style="border-collapse:separate;border-spacing:3px;width:100%;table-layout:fixed"><thead><tr>';
    html += '<th style="width:38px"></th>';
    for (var h = 0; h < 24; h++) {
        html += '<th style="text-align:center;font-weight:normal;color:#aaa;font-size:0.68rem;padding:0 0 4px">' + (h % 3 === 0 ? (h < 10 ? '0' + h : h) + 'h' : '') + '</th>';
    }
    html += '</tr></thead><tbody>';
    for (var d = 1; d <= 7; d++) {
        html += '<tr><td style="font-size:0.75rem;color:#555;white-space:nowrap;vertical-align:middle;padding-right:4px">' + dayLabels[d - 1] + '</td>';
        for (var h = 0; h < 24; h++) {
            var cnt = grid[d + '_' + h] || 0;
            var a = cnt > 0 ? (0.15 + (cnt / maxVal) * 0.85) : 0;
            var bg = cnt === 0 ? '#f0f2f5' : 'rgba(13,110,253,' + a.toFixed(2) + ')';
            var tip = dayLabels[d - 1] + ' ' + (h < 10 ? '0' + h : h) + ':00  —  ' + cnt + ' visit' + (cnt !== 1 ? 's' : '');
            html += '<td title="' + tip + '" style="background:' + bg + ';border-radius:3px;height:20px;cursor:default"></td>';
        }
        html += '</tr>';
    }
    html += '</tbody></table></div>';
    html += '<div class="d-flex align-items-center gap-2 mt-2" style="font-size:0.75rem;color:#999">';
    html += '<span>Less</span>';
    [0.08, 0.25, 0.45, 0.65, 0.9].forEach(function (a) {
        html += '<div style="width:14px;height:14px;border-radius:3px;background:rgba(13,110,253,' + a + ')"></div>';
    });
    html += '<span>More</span></div>';
    document.getElementById('activityGrid').innerHTML = html;
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
