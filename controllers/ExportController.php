<?php

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../models/Event.php';
if (!defined('FPDF_FONTPATH')) {
    define('FPDF_FONTPATH', __DIR__ . '/../lib/font/');
}
require_once __DIR__ . '/../lib/fpdf.php';

class ExportController {

    public function printReport(): void {
        requireAuth();
        $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
        if ($isPost) verifyCsrf();
        $id = (int) ($isPost ? ($_POST['id'] ?? 0) : ($_GET['id'] ?? 0));
        $report = Report::getById($id);
        if (!$report) { show404(); return; }

        if (currentRole() === 'viewer' && !$report['is_published']) { show403(); return; }
        if (currentRole() === 'analyst' && !canAccessSection($report['category'])) { show403(); return; }

        $category = $report['category'];
        if ($category === 'traffic') {
            $summary   = Event::getTrafficSummary();
            $pageViews = Event::getPageViews();
            $dailySess = Event::getDailySessions();
            $devices   = Event::getDeviceBreakdown();
        } elseif ($category === 'behavioral') {
            $counts          = Event::getBehavioralCounts();
            $clickPages      = Event::getClicksByPage();
            $formSubmissions = Event::getFormSubmissionsByPage();
        } elseif ($category === 'performance') {
            $byPage  = Event::getPerformanceByPage();
            $summary = Event::getPerformanceSummary();
        }

        // Save any submitted chart images to temp PNG files for embedding
        $chartFiles = [];
        if ($isPost && !empty($_POST['charts']) && is_array($_POST['charts'])) {
            foreach ($_POST['charts'] as $dataUri) {
                if (preg_match('/^data:image\/png;base64,(.+)$/s', $dataUri, $m)) {
                    $tmp = tempnam(sys_get_temp_dir(), 'chart_') . '.png';
                    file_put_contents($tmp, base64_decode($m[1]));
                    $chartFiles[] = $tmp;
                }
            }
        }

        $this->generatePdf($report, $category, compact(
            'summary','pageViews','dailySess','devices',
            'counts','clickPages','formSubmissions',
            'byPage'
        ), $chartFiles);
    }

    private function embedChart(FPDF $pdf, string $file, int $pageWidth): void {
        if (!file_exists($file)) return;
        $size = @getimagesize($file);
        if (!$size || $size[0] === 0) return;
        $pdfH = round(($size[1] / $size[0]) * $pageWidth);
        // Start a new page if not enough vertical space remains
        if ($pdf->GetY() + $pdfH > 270) $pdf->AddPage();
        $pdf->Image($file, 15, $pdf->GetY(), $pageWidth, 0);
        $pdf->SetY($pdf->GetY() + $pdfH + 4);
    }

    private function generatePdf(array $report, string $category, array $data, array $chartFiles = []): void {
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();
        $pageWidth = 180; // 210 - 30 margins

        // Header
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, $report['title'], 0, 1);

        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 6, 'Category: ' . ucfirst($category) . '   |   Author: ' . $report['author'] . '   |   Date: ' . date('F j, Y', strtotime($report['created_at'])), 0, 1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(4);

        // Analyst comments
        if (!empty($report['comments'])) {
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(0, 7, 'Analyst Comments', 0, 1);
            $pdf->SetFont('Arial', '', 10);
            $pdf->MultiCell(0, 6, $report['comments']);
            $pdf->Ln(3);
        }

        if ($category === 'traffic') {
            $summary   = $data['summary']   ?? [];
            $pageViews = $data['pageViews'] ?? [];
            $dailySess = $data['dailySess'] ?? [];
            $devices   = $data['devices']   ?? [];

            // Summary stats
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(0, 7, 'Summary', 0, 1);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell($pageWidth / 3, 8, 'Page Views: ' . number_format((int)($summary['pageviews'] ?? 0)), 1, 0);
            $pdf->Cell($pageWidth / 3, 8, 'Sessions: ' . number_format((int)($summary['sessions'] ?? 0)), 1, 0);
            $pdf->Cell($pageWidth / 3, 8, 'Unique Pages: ' . count($pageViews), 1, 1);
            $pdf->Ln(4);

            if (!empty($chartFiles[0])) {
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->Cell(0, 7, 'Sessions Over Time', 0, 1);
                $this->embedChart($pdf, $chartFiles[0], $pageWidth);
            }

            // Sessions over time table
            if (!empty($dailySess)) {
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->Cell(0, 7, 'Sessions Over Time', 0, 1);
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetFillColor(50, 50, 50);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell($pageWidth * 0.6, 7, 'Date', 1, 0, '', true);
                $pdf->Cell($pageWidth * 0.4, 7, 'Sessions', 1, 1, '', true);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('Arial', '', 9);
                $fill = false;
                foreach ($dailySess as $row) {
                    $pdf->SetFillColor(240, 240, 240);
                    $pdf->Cell($pageWidth * 0.6, 6, $row['day'], 1, 0, '', $fill);
                    $pdf->Cell($pageWidth * 0.4, 6, (int)$row['sessions'], 1, 1, '', $fill);
                    $fill = !$fill;
                }
                $pdf->Ln(4);
            }

            // Page views table
            if (!empty($pageViews)) {
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->Cell(0, 7, 'Page Views by URL', 0, 1);
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetFillColor(50, 50, 50);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell($pageWidth * 0.75, 7, 'Page URL', 1, 0, '', true);
                $pdf->Cell($pageWidth * 0.25, 7, 'Views', 1, 1, '', true);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('Arial', '', 9);
                $fill = false;
                foreach ($pageViews as $pv) {
                    $url = strlen($pv['page_url']) > 60 ? '...' . substr($pv['page_url'], -57) : $pv['page_url'];
                    $pdf->SetFillColor(240, 240, 240);
                    $pdf->Cell($pageWidth * 0.75, 6, $url, 1, 0, '', $fill);
                    $pdf->Cell($pageWidth * 0.25, 6, number_format((int)$pv['views']), 1, 1, '', $fill);
                    $fill = !$fill;
                }
                $pdf->Ln(4);
            }

            // Device breakdown
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(0, 7, 'Device Breakdown', 0, 1);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell($pageWidth / 3, 8, 'Mobile: ' . number_format((int)($devices['mobile'] ?? 0)), 1, 0);
            $pdf->Cell($pageWidth / 3, 8, 'Tablet: ' . number_format((int)($devices['tablet'] ?? 0)), 1, 0);
            $pdf->Cell($pageWidth / 3, 8, 'Desktop: ' . number_format((int)($devices['desktop'] ?? 0)), 1, 1);

        } elseif ($category === 'behavioral') {
            $counts          = $data['counts']          ?? [];
            $clickPages      = $data['clickPages']      ?? [];
            $formSubmissions = $data['formSubmissions'] ?? [];

            $countMap = [];
            foreach ($counts as $c) $countMap[$c['event_type']] = (int)$c['cnt'];

            // Summary stats
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(0, 7, 'Summary', 0, 1);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell($pageWidth / 3, 8, 'Clicks: ' . number_format($countMap['click'] ?? 0), 1, 0);
            $pdf->Cell($pageWidth / 3, 8, 'Mouse Moves: ' . number_format($countMap['mousemove'] ?? 0), 1, 0);
            $pdf->Cell($pageWidth / 3, 8, 'Form Submissions: ' . number_format($countMap['submit'] ?? 0), 1, 1);
            $pdf->Ln(4);

            if (!empty($chartFiles[0])) {
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->Cell(0, 7, 'Interaction Type Breakdown', 0, 1);
                $this->embedChart($pdf, $chartFiles[0], $pageWidth);
            }

            // Clicks by page
            if (!empty($clickPages)) {
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->Cell(0, 7, 'Clicks by Page', 0, 1);
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetFillColor(50, 50, 50);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell($pageWidth * 0.75, 7, 'Page URL', 1, 0, '', true);
                $pdf->Cell($pageWidth * 0.25, 7, 'Clicks', 1, 1, '', true);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('Arial', '', 9);
                $fill = false;
                foreach ($clickPages as $cp) {
                    $url = strlen($cp['page_url']) > 60 ? '...' . substr($cp['page_url'], -57) : $cp['page_url'];
                    $pdf->SetFillColor(240, 240, 240);
                    $pdf->Cell($pageWidth * 0.75, 6, $url, 1, 0, '', $fill);
                    $pdf->Cell($pageWidth * 0.25, 6, number_format((int)$cp['clicks']), 1, 1, '', $fill);
                    $fill = !$fill;
                }
                $pdf->Ln(4);
            }

            // Form submissions
            if (!empty($formSubmissions)) {
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->Cell(0, 7, 'Form Submissions by Page', 0, 1);
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetFillColor(50, 50, 50);
                $pdf->SetTextColor(255, 255, 255);
                $col1 = $pageWidth * 0.45;
                $col2 = $pageWidth * 0.40;
                $col3 = $pageWidth * 0.15;
                $pdf->Cell($col1, 7, 'Page URL', 1, 0, '', true);
                $pdf->Cell($col2, 7, 'Form Action', 1, 0, '', true);
                $pdf->Cell($col3, 7, 'Count', 1, 1, 'C', true);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('Arial', '', 9);
                $fill = false;
                foreach ($formSubmissions as $f) {
                    $pg  = strlen($f['page_url']) > 40 ? '...' . substr($f['page_url'], -37) : $f['page_url'];
                    $act = strlen($f['form_action'] ?? '') > 35 ? '...' . substr($f['form_action'], -32) : ($f['form_action'] ?? '(unknown)');
                    $pdf->SetFillColor(240, 240, 240);
                    $pdf->Cell($col1, 6, $pg, 1, 0, '', $fill);
                    $pdf->Cell($col2, 6, $act, 1, 0, '', $fill);
                    $pdf->Cell($col3, 6, number_format((int)$f['cnt']), 1, 1, 'C', $fill);
                    $fill = !$fill;
                }
            }

        } elseif ($category === 'performance') {
            $byPage  = $data['byPage']  ?? [];
            $summary = $data['summary'] ?? [];

            $avgLoad = isset($summary['avg_load']) && $summary['avg_load'] ? round($summary['avg_load']) . 'ms' : 'N/A';
            $avgTtfb = isset($summary['avg_ttfb']) && $summary['avg_ttfb'] ? round($summary['avg_ttfb']) . 'ms' : 'N/A';
            $avgDom  = isset($summary['avg_dom'])  && $summary['avg_dom']  ? round($summary['avg_dom'])  . 'ms' : 'N/A';

            // Summary stats
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(0, 7, 'Summary', 0, 1);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell($pageWidth / 4, 8, 'Samples: ' . number_format((int)($summary['samples'] ?? 0)), 1, 0);
            $pdf->Cell($pageWidth / 4, 8, 'Avg Load: ' . $avgLoad, 1, 0);
            $pdf->Cell($pageWidth / 4, 8, 'Avg TTFB: ' . $avgTtfb, 1, 0);
            $pdf->Cell($pageWidth / 4, 8, 'Avg DOM: ' . $avgDom, 1, 1);
            $pdf->Ln(4);

            if (!empty($chartFiles[0])) {
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->Cell(0, 7, 'Avg Load Time by Page', 0, 1);
                $this->embedChart($pdf, $chartFiles[0], $pageWidth);
            }

            // Performance by page table
            if (!empty($byPage)) {
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->Cell(0, 7, 'Performance by Page', 0, 1);
                $col1 = $pageWidth * 0.35;
                $col  = ($pageWidth - $col1) / 5;
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->SetFillColor(50, 50, 50);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell($col1, 7, 'Page URL', 1, 0, '', true);
                $pdf->Cell($col, 7, 'Samples', 1, 0, 'C', true);
                $pdf->Cell($col, 7, 'Avg Load', 1, 0, 'C', true);
                $pdf->Cell($col, 7, 'Min', 1, 0, 'C', true);
                $pdf->Cell($col, 7, 'Max', 1, 0, 'C', true);
                $pdf->Cell($col, 7, 'Avg TTFB', 1, 1, 'C', true);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('Arial', '', 8);
                $fill = false;
                foreach ($byPage as $p) {
                    $url = strlen($p['page_url']) > 35 ? '...' . substr($p['page_url'], -32) : $p['page_url'];
                    $pdf->SetFillColor(240, 240, 240);
                    $pdf->Cell($col1, 6, $url, 1, 0, '', $fill);
                    $pdf->Cell($col, 6, (int)$p['samples'], 1, 0, 'C', $fill);
                    $pdf->Cell($col, 6, $p['avg_load'] !== null ? round($p['avg_load']) . 'ms' : 'N/A', 1, 0, 'C', $fill);
                    $pdf->Cell($col, 6, $p['min_load'] !== null ? round($p['min_load']) . 'ms' : 'N/A', 1, 0, 'C', $fill);
                    $pdf->Cell($col, 6, $p['max_load'] !== null ? round($p['max_load']) . 'ms' : 'N/A', 1, 0, 'C', $fill);
                    $pdf->Cell($col, 6, $p['avg_ttfb'] !== null ? round($p['avg_ttfb']) . 'ms' : 'N/A', 1, 1, 'C', $fill);
                    $fill = !$fill;
                }
            }
        }

        // Footer
        $pdf->Ln(6);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 5, 'Generated by Analytics Platform on ' . date('F j, Y \a\t g:i A'), 0, 1, 'C');

        $filename = 'report_' . $report['id'] . '_' . date('Ymd') . '.pdf';
        $pdf->Output('D', $filename);
        foreach ($chartFiles as $f) { if (file_exists($f)) unlink($f); }
        exit;
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
            $counts          = Event::getBehavioralCounts();
            $clickPages      = Event::getClicksByPage();
            $formSubmissions = Event::getFormSubmissionsByPage();
            $byDay           = Event::getBehavioralByDay();
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
