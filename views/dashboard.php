<?php
$pageTitle = 'Dashboard';
ob_start();
?>

<h3>Dashboard</h3>
<p class="text-muted">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>.</p>
<br>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center p-3">
            <h4><?= number_format($totalEvents) ?></h4>
            <small class="text-muted">Total Events</small>
        </div>
    </div>
    <?php foreach (array_slice($eventsByType, 0, 3) as $et): ?>
    <div class="col-md-3">
        <div class="card text-center p-3">
            <h4><?= number_format($et['cnt']) ?></h4>
            <small class="text-muted"><?= htmlspecialchars($et['event_type']) ?></small>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<h5>Event Breakdown</h5>
<table class="table table-bordered table-striped">
    <thead class="table-dark">
        <tr><th>Event Type</th><th>Count</th><th>%</th></tr>
    </thead>
    <tbody>
        <?php foreach ($eventsByType as $et): ?>
        <tr>
            <td><?= htmlspecialchars($et['event_type']) ?></td>
            <td><?= number_format($et['cnt']) ?></td>
            <td><?= $totalEvents > 0 ? round($et['cnt'] / $totalEvents * 100, 1) : 0 ?>%</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
