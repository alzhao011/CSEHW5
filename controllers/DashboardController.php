<?php

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Event.php';

class DashboardController {
    public function index(): void {
        requireAuth();
        // viewers don't have a dashboard — send them to saved reports
        if (currentRole() === 'viewer') {
            header("Location: /reports/saved");
            exit;
        }
        $totalEvents  = Event::getCount();
        $eventsByType = Event::getCountByType();
        require __DIR__ . '/../views/dashboard.php';
    }
}
