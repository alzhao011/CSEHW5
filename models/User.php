<?php

require_once __DIR__ . '/../config/database.php';

define('SECURITY_QUESTIONS', [
    "What was the name of your first pet?",
    "What city were you born in?",
    "What is your mother's maiden name?",
    "What was the name of your elementary school?",
    "What was the make and model of your first car?",
    "What is the name of your childhood best friend?",
    "What street did you grow up on?",
    "What was your childhood nickname?",
    "What is the name of the hospital where you were born?",
    "What was the first concert you attended?",
]);

class User {
    public static function findByUsername(string $username): ?array {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE BINARY username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public static function findByEmail(string $email): ?array {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public static function verify(string $username, string $password): ?array {
        $user = self::findByUsername($username);
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return null;
    }

    public static function getAll(): array {
        $db = getDB();
        $result = $db->query("SELECT id, username, email, role, sections, created_at FROM users ORDER BY id");
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public static function create(string $username, string $password, string $role, ?array $sections): bool {
        $db = getDB();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sectionsJson = $sections !== null ? json_encode($sections) : null;
        $stmt = $db->prepare("INSERT INTO users (username, password_hash, role, sections) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $hash, $role, $sectionsJson);
        $ok = $stmt->execute();
        return $ok;
    }

    // Used for public self-registration — always creates viewer accounts
    public static function register(
        string $username,
        string $email,
        string $password,
        string $q1, string $a1,
        string $q2, string $a2,
        string $q3, string $a3
    ): bool {
        $db = getDB();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        // Answers are lowercased before hashing so "Paris" == "paris"
        $ha1 = password_hash(strtolower(trim($a1)), PASSWORD_DEFAULT);
        $ha2 = password_hash(strtolower(trim($a2)), PASSWORD_DEFAULT);
        $ha3 = password_hash(strtolower(trim($a3)), PASSWORD_DEFAULT);
        $role = 'viewer';
        $stmt = $db->prepare(
            "INSERT INTO users (username, email, password_hash, role,
             security_q1, security_a1, security_q2, security_a2, security_q3, security_a3)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "ssssssssss",
            $username, $email, $hash, $role,
            $q1, $ha1, $q2, $ha2, $q3, $ha3
        );
        $ok = $stmt->execute();
        return $ok;
    }

    public static function updatePassword(int $userId, string $newPassword): bool {
        $db = getDB();
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $userId);
        $ok = $stmt->execute();
        return $ok;
    }

    // Password reset token methods
    public static function saveResetToken(int $userId, string $token): bool {
        $db = getDB();
        // Invalidate any existing unused tokens for this user first
        $stmt = $db->prepare("UPDATE password_reset_tokens SET used = 1 WHERE user_id = ? AND used = 0");
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $stmt2 = $db->prepare(
            "INSERT INTO password_reset_tokens (user_id, token, expires_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))"
        );
        $stmt2->bind_param("is", $userId, $token);
        $ok = $stmt2->execute();
        return $ok;
    }

    public static function findResetToken(string $token): ?array {
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT t.*, u.email FROM password_reset_tokens t
             JOIN users u ON u.id = t.user_id
             WHERE t.token = ? AND t.used = 0 AND t.expires_at > NOW()"
        );
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public static function markTokenUsed(int $tokenId): void {
        $db = getDB();
        $stmt = $db->prepare("UPDATE password_reset_tokens SET used = 1 WHERE id = ?");
        $stmt->bind_param("i", $tokenId);
        $stmt->execute();
    }

    public static function update(int $id, string $role, ?array $sections): bool {
        $db = getDB();
        $sectionsJson = $sections !== null ? json_encode($sections) : null;
        $stmt = $db->prepare("UPDATE users SET role = ?, sections = ? WHERE id = ?");
        $stmt->bind_param("ssi", $role, $sectionsJson, $id);
        $ok = $stmt->execute();
        return $ok;
    }

    public static function delete(int $id): bool {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        return $ok;
    }
}
