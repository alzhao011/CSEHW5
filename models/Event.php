<?php

require_once __DIR__ . '/../config/database.php';

class Event {

    // Build a date range WHERE clause addition and bind types/values.
    // Returns ['clause' => ' AND created_at BETWEEN ? AND ?', 'types' => 'ss', 'params' => [...]]
    private static function dateRange(?string $start, ?string $end): array {
        if ($start && $end) {
            return ['clause' => ' AND created_at BETWEEN ? AND ?', 'types' => 'ss', 'params' => [$start . ' 00:00:00', $end . ' 23:59:59']];
        } elseif ($start) {
            return ['clause' => ' AND created_at >= ?', 'types' => 's', 'params' => [$start . ' 00:00:00']];
        } elseif ($end) {
            return ['clause' => ' AND created_at <= ?', 'types' => 's', 'params' => [$end . ' 23:59:59']];
        }
        return ['clause' => '', 'types' => '', 'params' => []];
    }

    public static function getAll(int $limit = 100, int $offset = 0): array {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM events ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $rows = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public static function getCount(): int {
        $db = getDB();
        $row = $db->query("SELECT COUNT(*) as cnt FROM events")->fetch_assoc();
        return (int) $row['cnt'];
    }

    public static function getCountByType(): array {
        $db = getDB();
        $result = $db->query("SELECT event_type, COUNT(*) as cnt FROM events GROUP BY event_type ORDER BY cnt DESC");
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public static function getCountByDay(): array {
        $db = getDB();
        $result = $db->query("SELECT DATE(created_at) as day, COUNT(*) as cnt FROM events GROUP BY DATE(created_at) ORDER BY day");
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public static function getTopPages(int $limit = 10): array {
        $db = getDB();
        $stmt = $db->prepare("SELECT page_url, COUNT(*) as cnt FROM events GROUP BY page_url ORDER BY cnt DESC LIMIT ?");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $rows = [];
        $result = $stmt->get_result(); while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    // --- Traffic ---
    public static function getPageViews(?string $start = null, ?string $end = null): array {
        $dr = self::dateRange($start, $end);
        $db = getDB();
        $stmt = $db->prepare("SELECT page_url, COUNT(*) as views FROM events WHERE event_type='static'" . $dr['clause'] . " GROUP BY page_url ORDER BY views DESC");
        if ($dr['types']) $stmt->bind_param($dr['types'], ...$dr['params']);
        $stmt->execute();
        $rows = [];
        $result = $stmt->get_result(); while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public static function getDailySessions(?string $start = null, ?string $end = null): array {
        $dr = self::dateRange($start, $end);
        $db = getDB();
        $stmt = $db->prepare("SELECT DATE(created_at) as day, COUNT(DISTINCT session_id) as sessions, COUNT(*) as events FROM events WHERE event_type='static'" . $dr['clause'] . " GROUP BY DATE(created_at) ORDER BY day");
        if ($dr['types']) $stmt->bind_param($dr['types'], ...$dr['params']);
        $stmt->execute();
        $rows = [];
        $result = $stmt->get_result(); while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public static function getDeviceBreakdown(?string $start = null, ?string $end = null): array {
        $dr = self::dateRange($start, $end);
        $db = getDB();
        $stmt = $db->prepare("SELECT
            SUM(CASE WHEN JSON_EXTRACT(data,'$.screen.width') <= 768 THEN 1 ELSE 0 END) as mobile,
            SUM(CASE WHEN JSON_EXTRACT(data,'$.screen.width') > 768 AND JSON_EXTRACT(data,'$.screen.width') <= 1280 THEN 1 ELSE 0 END) as tablet,
            SUM(CASE WHEN JSON_EXTRACT(data,'$.screen.width') > 1280 THEN 1 ELSE 0 END) as desktop
            FROM events WHERE event_type='static'" . $dr['clause']);
        if ($dr['types']) $stmt->bind_param($dr['types'], ...$dr['params']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: ['mobile'=>0,'tablet'=>0,'desktop'=>0];
    }

    public static function getConnectionTypes(?string $start = null, ?string $end = null): array {
        $dr = self::dateRange($start, $end);
        $db = getDB();
        $stmt = $db->prepare("SELECT JSON_UNQUOTE(JSON_EXTRACT(data,'$.connection')) as conn, COUNT(*) as cnt FROM events WHERE event_type='static'" . $dr['clause'] . " GROUP BY conn ORDER BY cnt DESC");
        if ($dr['types']) $stmt->bind_param($dr['types'], ...$dr['params']);
        $stmt->execute();
        $rows = [];
        $result = $stmt->get_result(); while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public static function getTrafficSummary(?string $start = null, ?string $end = null): array {
        $dr = self::dateRange($start, $end);
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) as pageviews, COUNT(DISTINCT session_id) as sessions FROM events WHERE event_type='static'" . $dr['clause']);
        if ($dr['types']) $stmt->bind_param($dr['types'], ...$dr['params']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: ['pageviews'=>0,'sessions'=>0];
    }

    // --- Behavioral ---
    public static function getBehavioralCounts(?string $start = null, ?string $end = null): array {
        $dr = self::dateRange($start, $end);
        $db = getDB();
        $stmt = $db->prepare("SELECT event_type, COUNT(*) as cnt FROM events WHERE event_type IN ('click','mousemove','submit')" . $dr['clause'] . " GROUP BY event_type");
        if ($dr['types']) $stmt->bind_param($dr['types'], ...$dr['params']);
        $stmt->execute();
        $rows = [];
        $result = $stmt->get_result(); while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public static function getClicksByPage(?string $start = null, ?string $end = null): array {
        $dr = self::dateRange($start, $end);
        $db = getDB();
        $stmt = $db->prepare("SELECT page_url, COUNT(*) as clicks FROM events WHERE event_type='click'" . $dr['clause'] . " GROUP BY page_url ORDER BY clicks DESC LIMIT 10");
        if ($dr['types']) $stmt->bind_param($dr['types'], ...$dr['params']);
        $stmt->execute();
        $rows = [];
        $result = $stmt->get_result(); while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public static function getFormSubmissionsByPage(?string $start = null, ?string $end = null): array {
        $dr = self::dateRange($start, $end);
        $db = getDB();
        $stmt = $db->prepare("SELECT page_url, JSON_UNQUOTE(JSON_EXTRACT(data,'$.action')) as form_action, COUNT(*) as cnt FROM events WHERE event_type='submit'" . $dr['clause'] . " GROUP BY page_url, form_action ORDER BY cnt DESC LIMIT 20");
        if ($dr['types']) $stmt->bind_param($dr['types'], ...$dr['params']);
        $stmt->execute();
        $rows = [];
        $result = $stmt->get_result(); while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public static function getBehavioralByDay(?string $start = null, ?string $end = null): array {
        $dr = self::dateRange($start, $end);
        $db = getDB();
        $stmt = $db->prepare("SELECT DATE(created_at) as day, COUNT(*) as cnt FROM events WHERE event_type IN ('click','mousemove','submit')" . $dr['clause'] . " GROUP BY DATE(created_at) ORDER BY day");
        if ($dr['types']) $stmt->bind_param($dr['types'], ...$dr['params']);
        $stmt->execute();
        $rows = [];
        $result = $stmt->get_result(); while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public static function getBehavioralRaw(int $limit = 50, int $offset = 0): array {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM events WHERE event_type IN ('click','mousemove','submit') ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $rows = [];
        $result = $stmt->get_result(); while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public static function getClickCoordinates(?string $start = null, ?string $end = null): array {
        $dr = self::dateRange($start, $end);
        $db = getDB();
        $stmt = $db->prepare("SELECT page_url, JSON_EXTRACT(data,'$.x') as x, JSON_EXTRACT(data,'$.y') as y, JSON_EXTRACT(data,'$.vw') as vw, JSON_EXTRACT(data,'$.vh') as vh FROM events WHERE event_type='click'" . $dr['clause'] . " ORDER BY created_at DESC LIMIT 500");
        if ($dr['types']) $stmt->bind_param($dr['types'], ...$dr['params']);
        $stmt->execute();
        $rows = [];
        $result = $stmt->get_result(); while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public static function getClickSessionsByPage(?string $start = null, ?string $end = null): array {
        $dr = self::dateRange($start, $end);
        $db = getDB();
        $stmt = $db->prepare("SELECT page_url, COUNT(DISTINCT session_id) as sessions FROM events WHERE event_type='click'" . $dr['clause'] . " GROUP BY page_url");
        if ($dr['types']) $stmt->bind_param($dr['types'], ...$dr['params']);
        $stmt->execute();
        $rows = [];
        $result = $stmt->get_result(); while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    // --- Performance ---
    public static function getPerformanceByPage(?string $start = null, ?string $end = null): array {
        $dr = self::dateRange($start, $end);
        $db = getDB();
        $valid = "JSON_EXTRACT(data,'$.totalLoadTime') > 0 AND JSON_EXTRACT(data,'$.totalLoadTime') < 300000";
        $stmt = $db->prepare("SELECT page_url, COUNT(*) as samples,
            AVG(CASE WHEN $valid THEN JSON_EXTRACT(data,'$.totalLoadTime') END) as avg_load,
            MIN(CASE WHEN $valid THEN JSON_EXTRACT(data,'$.totalLoadTime') END) as min_load,
            MAX(CASE WHEN $valid THEN JSON_EXTRACT(data,'$.totalLoadTime') END) as max_load,
            AVG(CASE WHEN JSON_EXTRACT(data,'$.ttfb') > 0 THEN JSON_EXTRACT(data,'$.ttfb') END) as avg_ttfb,
            AVG(CASE WHEN JSON_EXTRACT(data,'$.domReady') > 0 THEN JSON_EXTRACT(data,'$.domReady') END) as avg_dom
            FROM events WHERE event_type='performance'" . $dr['clause'] . "
            GROUP BY page_url ORDER BY avg_load DESC");
        if ($dr['types']) $stmt->bind_param($dr['types'], ...$dr['params']);
        $stmt->execute();
        $rows = [];
        $result = $stmt->get_result(); while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public static function getPerformanceRaw(int $limit = 50): array {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM events WHERE event_type='performance' ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $rows = [];
        $result = $stmt->get_result(); while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public static function getPerformanceSummary(?string $start = null, ?string $end = null): array {
        $dr = self::dateRange($start, $end);
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) as samples,
            AVG(CASE WHEN JSON_EXTRACT(data,'$.totalLoadTime') > 0 AND JSON_EXTRACT(data,'$.totalLoadTime') < 300000 THEN JSON_EXTRACT(data,'$.totalLoadTime') END) as avg_load,
            AVG(CASE WHEN JSON_EXTRACT(data,'$.ttfb') > 0 THEN JSON_EXTRACT(data,'$.ttfb') END) as avg_ttfb,
            AVG(CASE WHEN JSON_EXTRACT(data,'$.domReady') > 0 THEN JSON_EXTRACT(data,'$.domReady') END) as avg_dom
            FROM events WHERE event_type='performance'" . $dr['clause']);
        if ($dr['types']) $stmt->bind_param($dr['types'], ...$dr['params']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: ['samples'=>0,'avg_load'=>0,'avg_ttfb'=>0,'avg_dom'=>0];
    }

    public static function getActivityHeatmap(?string $start = null, ?string $end = null): array {
        $dr = self::dateRange($start, $end);
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT DAYOFWEEK(created_at) as dow, HOUR(created_at) as hr, COUNT(*) as cnt
             FROM events WHERE event_type='static'" . $dr['clause'] . " GROUP BY dow, hr"
        );
        if ($dr['types']) $stmt->bind_param($dr['types'], ...$dr['params']);
        $stmt->execute();
        $rows = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public static function getSessionStats(?string $start = null, ?string $end = null): array {
        $dr = self::dateRange($start, $end);
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT session_id) as total_sessions,
                    ROUND(AVG(pages), 1) as avg_pages,
                    SUM(CASE WHEN pages = 1 THEN 1 ELSE 0 END) as bounced
             FROM (
                 SELECT session_id, COUNT(*) as pages
                 FROM events WHERE event_type='static'" . $dr['clause'] . " GROUP BY session_id
             ) s"
        );
        if ($dr['types']) $stmt->bind_param($dr['types'], ...$dr['params']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: ['total_sessions' => 0, 'avg_pages' => 0, 'bounced' => 0];
    }
}
