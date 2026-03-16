<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user_id'])) {
    header("Location: /dashboard");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container" style="max-width: 450px; margin-top: 100px;">
        <h2>Analytics Login</h2>
        <hr>

        <?php if (!empty($_GET['reset'])): ?>
            <div class="alert alert-success">Password updated successfully. You can now log in.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['timeout'])): ?>
            <div class="alert alert-warning">Your session expired due to inactivity. Please log in again.</div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/login">
            <?= csrfField() ?>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>

        <div class="mt-3 d-flex justify-content-between" style="font-size:.9rem;">
            <a href="/forgot-password">Forgot password?</a>
            <a href="/register">Create an account</a>
        </div>
    </div>
</body>
</html>
