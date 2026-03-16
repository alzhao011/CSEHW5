<form method="GET" class="row g-2 align-items-end mb-4">
    <div class="col-auto">
        <label class="form-label mb-0">From</label>
        <input type="date" name="start" class="form-control form-control-sm" value="<?= htmlspecialchars($start ?? '') ?>">
    </div>
    <div class="col-auto">
        <label class="form-label mb-0">To</label>
        <input type="date" name="end" class="form-control form-control-sm" value="<?= htmlspecialchars($end ?? '') ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
        <?php if ($start || $end): ?>
        <a href="<?= strtok($_SERVER['REQUEST_URI'], '?') ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </div>
    <?php if ($start || $end): ?>
    <div class="col-auto align-self-end">
        <span class="text-muted small">Showing <?= htmlspecialchars($start ?? 'all time') ?> → <?= htmlspecialchars($end ?? 'now') ?></span>
    </div>
    <?php endif; ?>
</form>
