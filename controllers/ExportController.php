<?php

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../models/Event.php';

class ExportController {

    public function printReport(): void {
        requireAuth();
        $id = (int) ($_GET['id'] ?? 0);
        $report = Report::getById($id);
        if (!$report) { show404(); return; }

        if (currentRole() === 'viewer' && !$report['is_published']) { show403(); return; }
        if (currentRole() === 'analyst' && !canAccessSection($report['category'])) { show403(); return; }

        $category = $report['category'];
        if ($category === 'traffic') {
            $summary     = Event::getTrafficSummary();
            $pageViews   = Event::getPageViews();
            $dailySess   = Event::getDailySessions();
            $devices     = Event::getDeviceBreakdown();
            $connections = Event::getConnectionTypes();
        } elseif ($category === 'behavioral') {
            $counts     = Event::getBehavioralCounts();
            $clickPages = Event::getClicksByPage();
            $topKeys    = Event::getTopKeys();
            $byDay      = Event::getBehavioralByDay();
        } elseif ($category === 'performance') {
            $byPage  = Event::getPerformanceByPage();
            $summary = Event::getPerformanceSummary();
        }

        require __DIR__ . '/../views/export/report.php';
    }

    public function saveSnapshot(): void {
        requireRole('super_admin', 'analyst');
        verifyCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $report = Report::getById($id);
        if (!$report) { show404(); return; }
        if (!canAccessSection($report['category'])) { show403(); return; }

        // capture the print view as HTML snapshot
        $category = $report['category'];
        if ($category === 'traffic') {
            $summary     = Event::getTrafficSummary();
            $pageViews   = Event::getPageViews();
            $dailySess   = Event::getDailySessions();
            $devices     = Event::getDeviceBreakdown();
            $connections = Event::getConnectionTypes();
        } elseif ($category === 'behavioral') {
            $counts     = Event::getBehavioralCounts();
            $clickPages = Event::getClicksByPage();
            $topKeys    = Event::getTopKeys();
            $byDay      = Event::getBehavioralByDay();
        } elseif ($category === 'performance') {
            $byPage  = Event::getPerformanceByPage();
            $summary = Event::getPerformanceSummary();
        }

        ob_start();
        require __DIR__ . '/../views/export/report.php';
        $html = ob_get_clean();

        $filename = "report_{$id}_" . date('Ymd_His') . ".html";
        $dir = __DIR__ . '/../exports/';
        file_put_contents($dir . $filename, $html);
        Report::updateExportPath($id, "/exports/{$filename}");

        header("Location: /exports/{$filename}");
        exit;
    }
}
