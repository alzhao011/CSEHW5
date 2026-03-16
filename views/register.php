<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user_id'])) { header("Location: /dashboard"); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container" style="max-width:540px; margin-top:60px; margin-bottom:60px;">
    <h2>Create Account</h2>
    <p class="text-muted">New accounts start as <strong>Viewer</strong> access. An admin can upgrade your role.</p>
    <hr>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/register">
        <?= csrfField() ?>

        <h6 class="mt-3 mb-2">Account details</h6>
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="40" required>
            <div class="form-text">Letters, numbers, and underscores only. 3–40 characters.</div>
        </div>
        <div class="mb-3">
            <label class="form-label">Email address</label>
            <input type="email" class="form-control" name="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" minlength="8" required>
            <div class="form-text">Minimum 8 characters.</div>
        </div>
        <div class="mb-4">
            <label class="form-label">Confirm password</label>
            <input type="password" class="form-control" name="confirm_password" minlength="8" required>
        </div>

        <h6 class="mb-2">Security questions</h6>
        <p class="text-muted" style="font-size:.85rem;">
            These are used to help verify your identity if you need to recover your account.
            Choose three different questions and answer each one.
        </p>

        <?php
        $questions = SECURITY_QUESTIONS;
        foreach ([1, 2, 3] as $n):
            $prevQ = $_POST["q{$n}"] ?? '';
        ?>
        <div class="card p-3 mb-3">
            <div class="mb-2">
                <label class="form-label fw-semibold">Question <?= $n ?></label>
                <select class="form-select" name="q<?= $n ?>" required>
                    <option value="">— select a question —</option>
                    <?php foreach ($questions as $q): ?>
                        <option value="<?= htmlspecialchars($q) ?>"
                            <?= $prevQ === $q ? 'selected' : '' ?>>
                            <?= htmlspecialchars($q) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Answer</label>
                <input type="text" class="form-control" name="a<?= $n ?>"
                       value="<?= htmlspecialchars($_POST["a{$n}"] ?? '') ?>"
                       autocomplete="off" required>
                <div class="form-text">Answers are not case-sensitive.</div>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="d-grid mt-2">
            <button type="submit" class="btn btn-primary">Create account</button>
        </div>
    </form>

    <p class="mt-3 text-center text-muted" style="font-size:.9rem;">
        Already have an account? <a href="/login">Sign in</a>
    </p>
</div>

<script>
// Prevent the same question from being selected in multiple dropdowns
document.querySelectorAll('select[name^="q"]').forEach(function(sel) {
    sel.addEventListener('change', function() {
        var chosen = Array.from(document.querySelectorAll('select[name^="q"]'))
                         .map(function(s) { return s.value; })
                         .filter(function(v) { return v !== ''; });
        document.querySelectorAll('select[name^="q"]').forEach(function(other) {
            Array.from(other.options).forEach(function(opt) {
                if (opt.value === '') { opt.disabled = false; return; }
                opt.disabled = chosen.includes(opt.value) && other.value !== opt.value;
            });
        });
    });
});
</script>
</body>
</html>
