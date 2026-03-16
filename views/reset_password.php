<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user_id'])) { header("Location: /dashboard"); exit; }
$error = $error ?? '';
$expired = empty($_SESSION['reset_user_id']) || time() > ($_SESSION['reset_expires'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container" style="max-width:440px; margin-top:100px;">
    <h2>Set New Password</h2>
    <hr>

    <?php if ($expired && !$error): ?>
        <div class="alert alert-warning">Your session expired. Please start the recovery process again.</div>
        <p><a href="/forgot-password">← Back to account recovery</a></p>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="/reset-password">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">New password</label>
                <input type="password" class="form-control" name="password" minlength="8" required autofocus>
                <div class="form-text">Minimum 8 characters.</div>
            </div>
            <div class="mb-4">
                <label class="form-label">Confirm new password</label>
                <input type="password" class="form-control" name="confirm_password" minlength="8" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Save new password</button>
            </div>
        </form>
    <?php endif; ?>

    <p class="mt-3 text-center text-muted" style="font-size:.9rem;">
        <a href="/login">Back to login</a>
    </p>
</div>
</body>
</html>
