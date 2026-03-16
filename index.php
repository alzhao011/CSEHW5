<?php

// Harden session cookies before any session starts.
// Without these, cookies can be read by JS (XSS), sent over HTTP, or sent cross-site.
ini_set('session.cookie_secure', '1');    // HTTPS only
ini_set('session.cookie_httponly', '1');  // no JS access
ini_set('session.cookie_samesite', 'Lax'); // blocks cross-site POST

require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/ReportController.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/ExportController.php';

$route  = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$method = $_SERVER['REQUEST_METHOD'];

$auth      = new AuthController();
$dashboard = new DashboardController();
$report    = new ReportController();
$user      = new UserController();
$export    = new ExportController();

switch ($route) {
    case '':
    case 'login':
        $method === 'POST' ? $auth->login() : $auth->loginForm();
        break;

    case 'logout':
        $auth->logout();
        break;

    case 'register':
        $method === 'POST' ? $auth->register() : $auth->registerForm();
        break;

    case 'forgot-password':
        $method === 'POST' ? $auth->forgotPassword() : $auth->forgotPasswordForm();
        break;

    case 'forgot-password/verify':
        $auth->verifySecurityAnswers();
        break;

    case 'reset-password':
        $method === 'POST' ? $auth->resetPassword() : $auth->resetPasswordForm();
        break;

    case 'dashboard':
        $dashboard->index();
        break;

    case 'reports/traffic':
        $report->traffic();
        break;

    case 'reports/behavioral':
        $report->behavioral();
        break;

    case 'reports/performance':
        $report->performance();
        break;

    case 'reports/saved':
        $report->saved();
        break;

    case 'reports/view':
        $report->view();
        break;

    case 'reports/save':
        $report->save();
        break;

    case 'reports/delete':
        $report->delete();
        break;

    case 'admin/users':
        $user->index();
        break;

    case 'admin/users/create':
        $user->create();
        break;

    case 'admin/users/update':
        $user->update();
        break;

    case 'admin/users/delete':
        $user->delete();
        break;

    case 'export/print':
        $export->printReport();
        break;

    case 'export/save':
        $export->saveSnapshot();
        break;

    default:
        show404();
        break;
}
