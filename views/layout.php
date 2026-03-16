<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$role     = $_SESSION['role'] ?? '';
$sections = $_SESSION['sections'] ?? null; // null = all for analyst
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Analytics') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        td.truncate { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    </style>
    <?php if (!empty($extraHead)) echo $extraHead; ?>
</head>
<body>
<nav class="navbar navbar-expand navbar-dark bg-dark px-3">
    <a class="navbar-brand" href="/dashboard">Analytics</a>
    <ul class="navbar-nav me-auto">

        <?php if ($role !== 'viewer'): ?>
        <li class="nav-item"><a class="nav-link" href="/dashboard">Dashboard</a></li>

        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Reports</a>
            <ul class="dropdown-menu">
                <?php if ($role === 'super_admin' || ($role === 'analyst' && ($sections === null || in_array('traffic', $sections)))): ?>
                <li><a class="dropdown-item" href="/reports/traffic">Traffic</a></li>
                <?php endif; ?>
                <?php if ($role === 'super_admin' || ($role === 'analyst' && ($sections === null || in_array('behavioral', $sections)))): ?>
                <li><a class="dropdown-item" href="/reports/behavioral">Behavioral</a></li>
                <?php endif; ?>
                <?php if ($role === 'super_admin' || ($role === 'analyst' && ($sections === null || in_array('performance', $sections)))): ?>
                <li><a class="dropdown-item" href="/reports/performance">Performance</a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/reports/saved">Saved Reports</a></li>
            </ul>
        </li>
        <?php else: ?>
        <li class="nav-item"><a class="nav-link" href="/reports/saved">Saved Reports</a></li>
        <?php endif; ?>

        <?php if ($role === 'super_admin'): ?>
        <li class="nav-item"><a class="nav-link" href="/admin/users">User Management</a></li>
        <?php endif; ?>
    </ul>
    <span class="navbar-text me-3 text-secondary small"><?= htmlspecialchars($_SESSION['username'] ?? '') ?> (<?= $role ?>)</span>
    <a href="/logout" class="btn btn-outline-light btn-sm">Logout</a>
</nav>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<div class="container mt-4">
    <?= $content ?? '' ?>
</div>
</body>
</html>
