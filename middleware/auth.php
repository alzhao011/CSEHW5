<?php

define('SESSION_TIMEOUT', 1800); // 30 minutes of inactivity

function requireAuth(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) {
        header("Location: /login");
        exit;
    }
    // Expire idle sessions
    if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header("Location: /login?timeout=1");
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function requireRole(string ...$roles): void {
    requireAuth();
    if (!in_array($_SESSION['role'] ?? '', $roles)) {
        show403();
    }
}

function requireSection(string $section): void {
    requireAuth();
    if (!canAccessSection($section)) {
        show403();
    }
}

function canAccessSection(string $section): bool {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'super_admin') return true;
    if ($role === 'analyst') {
        $sections = $_SESSION['sections'] ?? null;
        if ($sections === null) return true; // null = all sections
        return in_array($section, $sections);
    }
    return false;
}

function isLoggedIn(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return !empty($_SESSION['user_id']);
}

function currentRole(): string {
    return $_SESSION['role'] ?? '';
}

function show403(): void {
    http_response_code(403);
    require __DIR__ . '/../views/errors/403.php';
    exit;
}

function show404(): void {
    http_response_code(404);
    require __DIR__ . '/../views/errors/404.php';
    exit;
}

// CSRF helpers.
// Without these, any page on any domain can silently POST to our forms
// while the victim is logged in (e.g. delete a user, save a fake report).
function csrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function verifyCsrf(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid request.');
    }
}
