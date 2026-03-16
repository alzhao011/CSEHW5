<?php

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../config/cache.php';

class ReportController {

    private function getDates(): array {
        $start = $_GET['start'] ?? null;
        $end   = $_GET['end']   ?? null;
        // basic sanity check — prevent injecting arbitrary strings into queries
        if ($start && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) $start = null;
        if ($end   && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end))   $end   = null;
        return [$start, $end];
    }

    public function traffic(): void {
        requireSection('traffic');
        [$start, $end] = $this->getDates();
        $ck = "t_{$start}_{$end}"; // cache key varies by date range
        $summary     = cached("traffic_summary_$ck",  fn() => Event::getTrafficSummary($start, $end));
        $pageViews   = cached("traffic_pageviews_$ck", fn() => Event::getPageViews($start, $end));
        $dailySess   = cached("traffic_daily_$ck",     fn() => Event::getDailySessions($start, $end));
        $devices     = cached("traffic_devices_$ck",   fn() => Event::getDeviceBreakdown($start, $end));
        $connections = cached("traffic_conns_$ck",     fn() => Event::getConnectionTypes($start, $end));
        $savedReports = Report::getAll(false);
        $savedReports = array_filter($savedReports, fn($r) => $r['category'] === 'traffic');
        require __DIR__ . '/../views/reports/traffic.php';
    }

    public function behavioral(): void {
        requireSection('behavioral');
        [$start, $end] = $this->getDates();
        $ck = "b_{$start}_{$end}";
        $counts     = cached("behav_counts_$ck",  fn() => Event::getBehavioralCounts($start, $end));
        $clickPages = cached("behav_clicks_$ck",  fn() => Event::getClicksByPage($start, $end));
        $topKeys    = cached("behav_keys_$ck",    fn() => Event::getTopKeys(15, $start, $end));
        $byDay      = cached("behav_byday_$ck",   fn() => Event::getBehavioralByDay($start, $end));
        $clicks        = Event::getClickCoordinates($start, $end);      // not cached — heatmap should always be live
        $clickSessions = Event::getClickSessionsByPage($start, $end);  // sessions per page for legend
        $raw        = Event::getBehavioralRaw(50);
        $savedReports = Report::getAll(false);
        $savedReports = array_filter($savedReports, fn($r) => $r['category'] === 'behavioral');
        require __DIR__ . '/../views/reports/behavioral.php';
    }

    public function performance(): void {
        requireSection('performance');
        [$start, $end] = $this->getDates();
        $ck = "p_{$start}_{$end}";
        $byPage  = cached("perf_bypage_$ck",   fn() => Event::getPerformanceByPage($start, $end));
        $raw     = Event::getPerformanceRaw(50);
        $summary = cached("perf_summary_$ck",  fn() => Event::getPerformanceSummary($start, $end));
        $savedReports = Report::getAll(false);
        $savedReports = array_filter($savedReports, fn($r) => $r['category'] === 'performance');
        require __DIR__ . '/../views/reports/performance.php';
    }

    public function saved(): void {
        requireAuth();
        $role = currentRole();
        $publishedOnly = ($role === 'viewer');
        $reports = Report::getAll($publishedOnly);
        require __DIR__ . '/../views/reports/saved.php';
    }

    public function view(): void {
        requireAuth();
        $id = (int) ($_GET['id'] ?? 0);
        $report = Report::getById($id);
        if (!$report) { show404(); return; }

        // viewers can only see published reports
        if (currentRole() === 'viewer' && !$report['is_published']) {
            show403(); return;
        }
        // analysts need section access
        if (currentRole() === 'analyst' && !canAccessSection($report['category'])) {
            show403(); return;
        }

        // load the data for this category
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

        require __DIR__ . '/../views/reports/view.php';
    }

    public function save(): void {
        requireAuth();
        verifyCsrf();
        if (currentRole() === 'viewer') { show403(); return; }

        $title    = trim($_POST['title'] ?? '');
        $category = $_POST['category'] ?? '';
        $comments = trim($_POST['comments'] ?? '');
        $publish  = !empty($_POST['publish']);

        if (!$title || !in_array($category, ['traffic','behavioral','performance'])) {
            header("Location: /reports/{$category}");
            exit;
        }
        if (!canAccessSection($category)) { show403(); return; }

        $id = Report::create($title, $category, $comments, $_SESSION['user_id'], $publish);
        header("Location: /reports/view?id={$id}");
        exit;
    }

    public function delete(): void {
        requireRole('super_admin', 'analyst');
        verifyCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $report = Report::getById($id);
        if (!$report) { show404(); return; }
        if (currentRole() === 'analyst' && $report['created_by'] != $_SESSION['user_id']) {
            show403(); return;
        }
        Report::delete($id);
        header("Location: /reports/saved");
        exit;
    }
}
