<?php
$pageTitle = 'Saved Reports';
ob_start();
$role = $_SESSION['role'] ?? '';
?>

<h3>Saved Reports</h3>
<br>

<?php if (empty($reports)): ?>
    <p class="text-muted">No reports available yet.</p>
<?php else: ?>
<table class="table table-bordered table-striped">
    <thead class="table-dark">
        <tr>
            <th>Title</th>
            <th>Category</th>
            <th>Author</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($reports as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['title']) ?></td>
            <td><span class="badge bg-secondary"><?= htmlspecialchars($r['category']) ?></span></td>
            <td><?= htmlspecialchars($r['author']) ?></td>
            <td><?= $r['is_published'] ? '<span class="badge bg-success">Published</span>' : '<span class="badge bg-warning text-dark">Draft</span>' ?></td>
            <td><?= htmlspecialchars(date('M j, Y', strtotime($r['created_at']))) ?></td>
            <td>
                <a href="/reports/view?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                <a href="/export/print?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank">Print</a>
                <?php if (in_array($role, ['super_admin','analyst'])): ?>
                <form method="POST" action="/reports/delete" class="d-inline" onsubmit="return confirm('Delete this report?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
