<?php
$pageTitle = 'Data Table';
$route = 'reports/table';
ob_start();
?>

<h3>Event Data</h3>
<p><?= number_format($totalEvents) ?> total events collected</p>
<br>

<div class="table-responsive">
    <table class="table table-bordered table-striped table-sm">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Session ID</th>
                <th>Type</th>
                <th>Page URL</th>
                <th>Data</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($events as $event): ?>
            <tr>
                <td><?= (int) $event['id'] ?></td>
                <td><code><?= htmlspecialchars(substr($event['session_id'], 0, 12)) ?>...</code></td>
                <td><?= htmlspecialchars($event['event_type']) ?></td>
                <td class="truncate"><?= htmlspecialchars($event['page_url']) ?></td>
                <td class="truncate" title="<?= htmlspecialchars($event['data']) ?>"><?= htmlspecialchars(substr($event['data'], 0, 60)) ?><?= strlen($event['data']) > 60 ? '...' : '' ?></td>
                <td style="white-space:nowrap"><?= htmlspecialchars($event['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<nav>
    <ul class="pagination">
        <?php if ($page > 1): ?>
            <li class="page-item"><a class="page-link" href="/reports/table?page=<?= $page - 1 ?>">Prev</a></li>
        <?php endif; ?>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="/reports/table?page=<?= $i ?>"><?= $i ?></a></li>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <li class="page-item"><a class="page-link" href="/reports/table?page=<?= $page + 1 ?>">Next</a></li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
