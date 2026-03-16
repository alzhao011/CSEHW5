<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/database.php';

define('MAX_ATTEMPTS', 5);
define('LOCKOUT_MINUTES', 5);

class AuthController {
    public function loginForm(): void {
        $error = '';
        require __DIR__ . '/../views/login.php';
    }

    public function login(): void {
        verifyCsrf();
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $lockout = $this->getUserLockout($username);
        if ($lockout) {
            $remaining = ceil(($lockout - time()) / 60);
            $error = "Account locked. Too many failed attempts. Try again in {$remaining} minute(s).";
            require __DIR__ . '/../views/login.php';
            return;
        }

        $ipLockout = $this->getIpLockout($ip);
        if ($ipLockout) {
            $remaining = ceil(($ipLockout - time()) / 60);
            $error = "Too many failed attempts from your IP. Try again in {$remaining} minute(s).";
            require __DIR__ . '/../views/login.php';
            return;
        }

        $user = User::verify($username, $password);
        if ($user) {
            $this->clearAttempts($username, $ip);
            session_start();
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            $sections = $user['sections'] ? json_decode($user['sections'], true) : null;
            $_SESSION['sections'] = $sections;
            if ($user['role'] === 'viewer') {
                header("Location: /reports/saved");
            } else {
                header("Location: /dashboard");
            }
            exit;
        }

        $attempts = $this->recordFailedAttempt($username, $ip);
        $remaining = MAX_ATTEMPTS - $attempts;

        if ($attempts >= MAX_ATTEMPTS) {
            $error = "Account locked for " . LOCKOUT_MINUTES . " minutes due to too many failed attempts.";
        } else {
            $error = "Invalid username or password. {$remaining} attempt(s) remaining before lockout.";
        }

        require __DIR__ . '/../views/login.php';
    }

    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_destroy();
        header("Location: /login");
        exit;
    }

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    public function registerForm(): void {
        $error = '';
        require __DIR__ . '/../views/register.php';
    }

    public function register(): void {
        verifyCsrf();

        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $q1 = $_POST['q1'] ?? '';
        $a1 = trim($_POST['a1'] ?? '');
        $q2 = $_POST['q2'] ?? '';
        $a2 = trim($_POST['a2'] ?? '');
        $q3 = $_POST['q3'] ?? '';
        $a3 = trim($_POST['a3'] ?? '');

        // Validation
        $error = '';
        if (!$username || !$email || !$password || !$a1 || !$a2 || !$a3) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } elseif (strlen($username) < 3 || strlen($username) > 40) {
            $error = 'Username must be 3–40 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $error = 'Username may only contain letters, numbers, and underscores.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif ($q1 === $q2 || $q1 === $q3 || $q2 === $q3) {
            $error = 'Please choose three different security questions.';
        } elseif (!in_array($q1, SECURITY_QUESTIONS) || !in_array($q2, SECURITY_QUESTIONS) || !in_array($q3, SECURITY_QUESTIONS)) {
            $error = 'Invalid security question selection.';
        }

        if (!$error && User::findByUsername($username)) {
            $error = 'That username is already taken.';
        }
        if (!$error && User::findByEmail($email)) {
            $error = 'An account with that email already exists.';
        }

        if ($error) {
            require __DIR__ . '/../views/register.php';
            return;
        }

        $ok = User::register($username, $email, $password, $q1, $a1, $q2, $a2, $q3, $a3);
        if (!$ok) {
            $error = 'Registration failed. Please try again.';
            require __DIR__ . '/../views/register.php';
            return;
        }

        // Auto-login after registration
        $user = User::findByUsername($username);
        session_start();
        session_regenerate_id(true);
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];
        $_SESSION['sections'] = null;
        header("Location: /reports/saved");
        exit;
    }

    // -------------------------------------------------------------------------
    // Forgot password — security question flow (no email required)
    //
    // Step 1: GET  /forgot-password           → enter username
    // Step 2: POST /forgot-password           → show user's 3 questions
    // Step 3: POST /forgot-password/verify    → check answers → set session flag
    // Step 4: GET  /reset-password            → new password form
    // Step 5: POST /reset-password            → save password, clear session flag
    // -------------------------------------------------------------------------

    public function forgotPasswordForm(): void {
        // Step 1 — show username entry form
        $step  = 1;
        $error = '';
        $user  = null;
        require __DIR__ . '/../views/forgot_password.php';
    }

    public function forgotPassword(): void {
        verifyCsrf();
        if (session_status() === PHP_SESSION_NONE) session_start();

        $username = trim($_POST['username'] ?? '');
        $error    = '';

        $user = $username ? User::findByUsername($username) : null;

        // Account exists but was created before security questions were introduced
        if ($user && empty($user['security_q1'])) {
            $step = 'no_questions';
            require __DIR__ . '/../views/forgot_password.php';
            return;
        }

        // Always show the questions form whether user exists or not,
        // with fake questions for non-existent users (prevents username enumeration)
        if (!$user) {
            $user = [
                'id'          => 0,
                'username'    => $username,
                'security_q1' => 'What was the name of your first pet?',
                'security_q2' => 'What city were you born in?',
                'security_q3' => 'What is your mother\'s maiden name?',
            ];
        }

        $step = 2;
        require __DIR__ . '/../views/forgot_password.php';
    }

    public function verifySecurityAnswers(): void {
        verifyCsrf();
        if (session_status() === PHP_SESSION_NONE) session_start();

        $username = trim($_POST['username'] ?? '');
        $a1 = strtolower(trim($_POST['a1'] ?? ''));
        $a2 = strtolower(trim($_POST['a2'] ?? ''));
        $a3 = strtolower(trim($_POST['a3'] ?? ''));
        $error = '';

        $user = $username ? User::findByUsername($username) : null;

        $valid = $user
            && $user['security_a1'] && password_verify($a1, $user['security_a1'])
            && $user['security_a2'] && password_verify($a2, $user['security_a2'])
            && $user['security_a3'] && password_verify($a3, $user['security_a3']);

        if (!$valid) {
            $error = 'One or more answers were incorrect. Please try again.';
            $step  = 2;
            // Re-show questions — use real user if found, else fake
            if (!$user) {
                $user = [
                    'id'          => 0,
                    'username'    => $username,
                    'security_q1' => 'What was the name of your first pet?',
                    'security_q2' => 'What city were you born in?',
                    'security_q3' => 'What is your mother\'s maiden name?',
                ];
            }
            require __DIR__ . '/../views/forgot_password.php';
            return;
        }

        // Answers correct — set a short-lived session flag to allow password reset
        $_SESSION['reset_user_id'] = (int)$user['id'];
        $_SESSION['reset_expires'] = time() + 600; // 10-minute window
        header("Location: /reset-password");
        exit;
    }

    // -------------------------------------------------------------------------
    // Reset password — only reachable after passing security questions
    // -------------------------------------------------------------------------

    public function resetPasswordForm(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $error = '';
        if (empty($_SESSION['reset_user_id']) || time() > ($_SESSION['reset_expires'] ?? 0)) {
            $error = 'Your session has expired. Please start over.';
        }
        require __DIR__ . '/../views/reset_password.php';
    }

    public function resetPassword(): void {
        verifyCsrf();
        if (session_status() === PHP_SESSION_NONE) session_start();
        $error = '';

        if (empty($_SESSION['reset_user_id']) || time() > ($_SESSION['reset_expires'] ?? 0)) {
            $error = 'Your session has expired. Please start over.';
            require __DIR__ . '/../views/reset_password.php';
            return;
        }

        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
            require __DIR__ . '/../views/reset_password.php';
            return;
        }
        if ($password !== $confirm) {
            $error = 'Passwords do not match.';
            require __DIR__ . '/../views/reset_password.php';
            return;
        }

        User::updatePassword((int)$_SESSION['reset_user_id'], $password);
        unset($_SESSION['reset_user_id'], $_SESSION['reset_expires']);

        // Redirect to login with success flag
        header("Location: /login?reset=1");
        exit;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function getUserLockout(string $username): ?int {
        $db = getDB();
        $stmt = $db->prepare("SELECT locked_until FROM login_attempts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row || !$row['locked_until']) return null;
        $until = strtotime($row['locked_until']);
        return $until > time() ? $until : null;
    }

    private function getIpLockout(string $ip): ?int {
        $db = getDB();
        $stmt = $db->prepare("SELECT locked_until FROM ip_lockouts WHERE ip = ?");
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row || !$row['locked_until']) return null;
        $until = strtotime($row['locked_until']);
        return $until > time() ? $until : null;
    }

    private function recordFailedAttempt(string $username, string $ip): int {
        $db = getDB();

        $stmt = $db->prepare("INSERT INTO login_attempts (username, attempts) VALUES (?, 1)
            ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()");
        $stmt->bind_param("s", $username);
        $stmt->execute();

        $stmt2 = $db->prepare("SELECT attempts FROM login_attempts WHERE username = ?");
        $stmt2->bind_param("s", $username);
        $stmt2->execute();
        $attempts = (int) ($stmt2->get_result()->fetch_assoc()['attempts'] ?? 1);

        if ($attempts >= MAX_ATTEMPTS) {
            $stmt3 = $db->prepare("UPDATE login_attempts SET locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE username = ?");
            $stmt3->bind_param("is", LOCKOUT_MINUTES, $username);
            $stmt3->execute();
        }

        $stmt4 = $db->prepare("INSERT INTO ip_lockouts (ip, attempts) VALUES (?, 1)
            ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()");
        $stmt4->bind_param("s", $ip);
        $stmt4->execute();

        $stmt5 = $db->prepare("SELECT attempts FROM ip_lockouts WHERE ip = ?");
        $stmt5->bind_param("s", $ip);
        $stmt5->execute();
        $ipAttempts = (int) ($stmt5->get_result()->fetch_assoc()['attempts'] ?? 1);

        if ($ipAttempts >= MAX_ATTEMPTS) {
            $stmt6 = $db->prepare("UPDATE ip_lockouts SET locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE ip = ?");
            $stmt6->bind_param("is", LOCKOUT_MINUTES, $ip);
            $stmt6->execute();
        }

        return $attempts;
    }

    private function clearAttempts(string $username, string $ip): void {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM login_attempts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt2 = $db->prepare("DELETE FROM ip_lockouts WHERE ip = ?");
        $stmt2->bind_param("s", $ip);
        $stmt2->execute();
    }
}
