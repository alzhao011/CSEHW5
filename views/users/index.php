<?php
$pageTitle = 'User Management';
ob_start();

$msgMap = [
    'created'           => ['success', 'User created.'],
    'updated'           => ['success', 'User updated.'],
    'deleted'           => ['success', 'User deleted.'],
    'invalid'           => ['danger',  'Invalid input.'],
    'cantdeleteyourself'=> ['danger',  "You can't delete your own account."],
];
$msgInfo = $msgMap[$message] ?? null;
?>

<h3>User Management</h3>
<br>

<?php if ($msgInfo): ?>
<div class="alert alert-<?= $msgInfo[0] ?>"><?= $msgInfo[1] ?></div>
<?php endif; ?>

<!-- Existing users -->
<div class="card p-3 mb-4">
    <h5>All Users</h5>
    <table class="table table-bordered table-striped table-sm">
        <thead class="table-dark">
            <tr><th>ID</th><th>Username</th><th>Role</th><th>Sections</th><th>Created</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><span class="badge bg-secondary"><?= $u['role'] ?></span></td>
                <td><?= $u['sections'] ? htmlspecialchars($u['sections']) : '<span class="text-muted">all</span>' ?></td>
                <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $u['id'] ?>">Edit</button>
                    <form method="POST" action="/admin/users/delete" class="d-inline" onsubmit="return confirm('Delete <?= htmlspecialchars($u['username']) ?>?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </td>
            </tr>

            <!-- Edit modal -->
            <div class="modal fade" id="editModal<?= $u['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="/admin/users/update">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <div class="modal-header"><h5 class="modal-title">Edit <?= htmlspecialchars($u['username']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body">
                                <div class="mb-2">
                                    <label class="form-label">Role</label>
                                    <select name="role" class="form-select" onchange="toggleSections(this, 'sections<?= $u['id'] ?>')">
                                        <option value="viewer" <?= $u['role']==='viewer'?'selected':'' ?>>viewer</option>
                                        <option value="analyst" <?= $u['role']==='analyst'?'selected':'' ?>>analyst</option>
                                        <option value="super_admin" <?= $u['role']==='super_admin'?'selected':'' ?>>super_admin</option>
                                    </select>
                                </div>
                                <div id="sections<?= $u['id'] ?>" <?= $u['role']==='analyst'?'':'style="display:none"' ?>>
                                    <label class="form-label">Sections (leave all unchecked = all sections)</label>
                                    <?php foreach (['traffic','behavioral','performance'] as $sec): ?>
                                    <?php $checked = $u['sections'] && in_array($sec, json_decode($u['sections'],true)??[]); ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sections[]" value="<?= $sec ?>" id="<?= $sec.$u['id'] ?>" <?= $checked?'checked':'' ?>>
                                        <label class="form-check-label" for="<?= $sec.$u['id'] ?>"><?= $sec ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">Save</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Create user -->
<div class="card p-3 mb-4">
    <h5>Create New User</h5>
    <form method="POST" action="/admin/users/create">
        <?= csrfField() ?>
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" onchange="toggleSections(this,'newSections')">
                    <option value="viewer">viewer</option>
                    <option value="analyst">analyst</option>
                    <option value="super_admin">super_admin</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-success w-100">Create</button>
            </div>
        </div>
        <div id="newSections" style="display:none" class="mt-2">
            <label class="form-label">Sections</label>
            <?php foreach (['traffic','behavioral','performance'] as $sec): ?>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="sections[]" value="<?= $sec ?>" id="new_<?= $sec ?>">
                <label class="form-check-label" for="new_<?= $sec ?>"><?= $sec ?></label>
            </div>
            <?php endforeach; ?>
        </div>
    </form>
</div>

<script>
function toggleSections(sel, targetId) {
    document.getElementById(targetId).style.display = sel.value === 'analyst' ? 'block' : 'none';
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
