<?php

// Harden session cookies before any session starts.
// Without these, cookies can be read by JS (XSS), sent over HTTP, or sent cross-site.
ini_set('session.cookie_secure', '1');    // HTTPS only
ini_set('session.cookie_httponly', '1');  // no JS access
ini_set('session.cookie_samesite', 'Lax'); // blocks cross-site POST

require_once __DIR__ . '/middleware/auth.php';

$route  = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$method = $_SERVER['REQUEST_METHOD'];

switch ($route) {
    case '':
    case 'login':
    case 'logout':
    case 'register':
    case 'forgot-password':
    case 'forgot-password/verify':
    case 'reset-password':
        require_once __DIR__ . '/controllers/AuthController.php';
        $auth = new AuthController();
        switch ($route) {
            case '':
            case 'login':       $method === 'POST' ? $auth->login() : $auth->loginForm(); break;
            case 'logout':      $auth->logout(); break;
            case 'register':    $method === 'POST' ? $auth->register() : $auth->registerForm(); break;
            case 'forgot-password': $method === 'POST' ? $auth->forgotPassword() : $auth->forgotPasswordForm(); break;
            case 'forgot-password/verify': $auth->verifySecurityAnswers(); break;
            case 'reset-password': $method === 'POST' ? $auth->resetPassword() : $auth->resetPasswordForm(); break;
        }
        break;

    case 'dashboard':
        require_once __DIR__ . '/controllers/DashboardController.php';
        (new DashboardController())->index();
        break;

    case 'reports/traffic':
    case 'reports/behavioral':
    case 'reports/performance':
    case 'reports/saved':
    case 'reports/view':
    case 'reports/save':
    case 'reports/delete':
        require_once __DIR__ . '/controllers/ReportController.php';
        $report = new ReportController();
        switch ($route) {
            case 'reports/traffic':     $report->traffic(); break;
            case 'reports/behavioral':  $report->behavioral(); break;
            case 'reports/performance': $report->performance(); break;
            case 'reports/saved':       $report->saved(); break;
            case 'reports/view':        $report->view(); break;
            case 'reports/save':        $report->save(); break;
            case 'reports/delete':      $report->delete(); break;
        }
        break;

    case 'admin/users':
    case 'admin/users/create':
    case 'admin/users/update':
    case 'admin/users/delete':
    case 'admin/reset-data':
        require_once __DIR__ . '/controllers/UserController.php';
        $user = new UserController();
        switch ($route) {
            case 'admin/users':        $user->index(); break;
            case 'admin/users/create': $user->create(); break;
            case 'admin/users/update': $user->update(); break;
            case 'admin/users/delete': $user->delete(); break;
            case 'admin/reset-data':   $user->resetData(); break;
        }
        break;

    case 'export/print':
    case 'export/save':
        require_once __DIR__ . '/controllers/ExportController.php';
        $export = new ExportController();
        switch ($route) {
            case 'export/print': $export->printReport(); break;
            case 'export/save':  $export->saveSnapshot(); break;
        }
        break;

    default:
        show404();
        break;
}
