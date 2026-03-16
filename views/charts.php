<?php
$pageTitle = 'Charts';
$route = 'reports/charts';
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>';
ob_start();
?>

<h3>Charts</h3>
<br>

<div class="card p-3 mb-4">
    <h5>Top Pages</h5>
    <div style="height: 600px;"><canvas id="pagesChart"></canvas></div>
</div>

<div class="card p-3 mb-4">
    <h5>Events by Type</h5>
    <div style="height: 500px;"><canvas id="typeChart"></canvas></div>
</div>

<div class="card p-3 mb-4">
    <h5>Events Over Time</h5>
    <div style="height: 500px;"><canvas id="timeChart"></canvas></div>
</div>

<script>
// top pages - vertical bar
var pageLabels = <?= json_encode(array_map(function($p) {
    $u = $p['page_url'];
    return strlen($u) > 35 ? '...' . substr($u, -32) : $u;
}, $topPages)) ?>;

new Chart(document.getElementById('pagesChart'), {
    type: 'bar',
    data: {
        labels: pageLabels,
        datasets: [{
            label: 'Hits',
            data: <?= json_encode(array_map('intval', array_column($topPages, 'cnt'))) ?>,
            backgroundColor: '#36a2eb'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// events by type - pie chart
new Chart(document.getElementById('typeChart'), {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($eventsByType, 'event_type')) ?>,
        datasets: [{
            data: <?= json_encode(array_map('intval', array_column($eventsByType, 'cnt'))) ?>,
            backgroundColor: ['#ff6384','#36a2eb','#ffce56','#4bc0c0','#9966ff','#ff9f40','#c9cbcf']
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

// events over time - line chart
new Chart(document.getElementById('timeChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($eventsByDay, 'day')) ?>,
        datasets: [{
            label: 'Events',
            data: <?= json_encode(array_map('intval', array_column($eventsByDay, 'cnt'))) ?>,
            borderColor: '#36a2eb',
            fill: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
