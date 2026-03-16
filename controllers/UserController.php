<?php

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/User.php';

class UserController {

    public function index(): void {
        requireRole('super_admin');
        $users = User::getAll();
        $message = $_GET['msg'] ?? '';
        require __DIR__ . '/../views/users/index.php';
    }

    public function create(): void {
        requireRole('super_admin');
        verifyCsrf();
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'viewer';
        $secs     = $_POST['sections'] ?? [];

        if (!$username || !$password || !in_array($role, ['super_admin','analyst','viewer'])) {
            header("Location: /admin/users?msg=invalid");
            exit;
        }
        $sections = ($role === 'analyst' && !empty($secs)) ? array_values($secs) : null;
        User::create($username, $password, $role, $sections);
        header("Location: /admin/users?msg=created");
        exit;
    }

    public function update(): void {
        requireRole('super_admin');
        verifyCsrf();
        $id   = (int) ($_POST['id'] ?? 0);
        $role = $_POST['role'] ?? 'viewer';
        $secs = $_POST['sections'] ?? [];

        $sections = ($role === 'analyst' && !empty($secs)) ? array_values($secs) : null;
        User::update($id, $role, $sections);
        header("Location: /admin/users?msg=updated");
        exit;
    }

    public function delete(): void {
        requireRole('super_admin');
        verifyCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === ($_SESSION['user_id'] ?? 0)) {
            header("Location: /admin/users?msg=cantdeleteyourself");
            exit;
        }
        User::delete($id);
        header("Location: /admin/users?msg=deleted");
        exit;
    }
}
