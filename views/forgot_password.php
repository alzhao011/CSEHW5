<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user_id'])) { header("Location: /dashboard"); exit; }
// $step (1 or 2), $error, $user set by controller
$step  = $step  ?? 1;
$error = $error ?? '';
$user  = $user  ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container" style="max-width:460px; margin-top:100px;">

    <?php if ($step === 'no_questions'): ?>
        <h2>Account Recovery Unavailable</h2>
        <hr>
        <div class="alert alert-warning">
            This account was set up before security questions were introduced, so we can't verify your identity automatically.
        </div>
        <p>Please contact the website manager to have your password reset manually.</p>
        <p class="mt-3"><a href="/login">← Back to login</a></p>

    <?php elseif ($step === 1): ?>
        <h2>Forgot Password</h2>
        <p class="text-muted">Enter your username and we'll ask you your security questions.</p>
        <hr>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="/forgot-password">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" name="username" required autofocus>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Continue</button>
            </div>
        </form>

    <?php elseif ($step === 2): ?>
        <h2>Security Questions</h2>
        <p class="text-muted">Answer all three questions to verify your identity.</p>
        <hr>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="/forgot-password/verify">
            <?= csrfField() ?>
            <input type="hidden" name="username" value="<?= htmlspecialchars($user['username']) ?>">

            <?php foreach ([1,2,3] as $n): ?>
            <div class="mb-3">
                <label class="form-label fw-semibold"><?= htmlspecialchars($user["security_q{$n}"]) ?></label>
                <input type="text" class="form-control" name="a<?= $n ?>" autocomplete="off" required>
            </div>
            <?php endforeach; ?>

            <div class="d-grid mt-2">
                <button type="submit" class="btn btn-primary">Verify answers</button>
            </div>
        </form>
        <p class="mt-2 text-center" style="font-size:.85rem;">
            <a href="/forgot-password">← Try a different username</a>
        </p>
    <?php endif; ?>

    <p class="mt-3 text-center text-muted" style="font-size:.9rem;">
        <a href="/login">Back to login</a>
    </p>
</div>
</body>
</html>
