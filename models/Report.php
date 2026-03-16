<?php

require_once __DIR__ . '/../config/database.php';

class Report {
    public static function getAll(bool $publishedOnly = false): array {
        $db = getDB();
        $where = $publishedOnly ? "WHERE r.is_published = 1" : "";
        $result = $db->query("SELECT r.*, u.username as author FROM reports r JOIN users u ON r.created_by = u.id $where ORDER BY r.created_at DESC");
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        $db->close();
        return $rows;
    }

    public static function getById(int $id): ?array {
        $db = getDB();
        $stmt = $db->prepare("SELECT r.*, u.username as author FROM reports r JOIN users u ON r.created_by = u.id WHERE r.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $db->close();
        return $row ?: null;
    }

    public static function create(string $title, string $category, string $comments, int $userId, bool $publish): int {
        $db = getDB();
        $pub = $publish ? 1 : 0;
        $stmt = $db->prepare("INSERT INTO reports (title, category, comments, created_by, is_published) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssii", $title, $category, $comments, $userId, $pub);
        $stmt->execute();
        $id = $stmt->insert_id;
        $db->close();
        return $id;
    }

    public static function updateExportPath(int $id, string $path): void {
        $db = getDB();
        $stmt = $db->prepare("UPDATE reports SET export_path = ? WHERE id = ?");
        $stmt->bind_param("si", $path, $id);
        $stmt->execute();
        $db->close();
    }

    public static function delete(int $id): void {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM reports WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $db->close();
    }
}
