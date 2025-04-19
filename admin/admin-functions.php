<?php
// /admin/admin-functions.php
// Admin helper functions

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get dashboard statistics
function getDashboardStats() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $logger = new Logger();
    
    // Apply date filter if set
    $dateFilter = $_GET['date-range'] ?? 'all';
    $dateCondition = '';
    
    switch ($dateFilter) {
        case 'today':
            $dateCondition = "WHERE DATE(start_time) = CURDATE()";
            break;
        case 'yesterday':
            $dateCondition = "WHERE DATE(start_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'week':
            $dateCondition = "WHERE start_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $dateCondition = "WHERE start_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        default:
            $dateCondition = "";
    }
    
    try {
        // Total unique visitors
        $visitorQuery = "SELECT COUNT(DISTINCT visitor_id) as total FROM sessions $dateCondition";
        $visitorResult = $conn->query($visitorQuery);
        $totalVisitors = $visitorResult->fetch_assoc()['total'];
        
        // Total sessions
        $sessionQuery = "SELECT COUNT(*) as total FROM sessions $dateCondition";
        $sessionResult = $conn->query($sessionQuery);
        $totalSessions = $sessionResult->fetch_assoc()['total'];
        
        // Average session duration
        $durationQuery = "SELECT AVG(total_duration) as avg_duration FROM sessions 
                         WHERE total_duration > 0 $dateCondition";
        $durationResult = $conn->query($durationQuery);
        $avgDuration = round($durationResult->fetch_assoc()['avg_duration'] ?? 0);
        
        // Total page views
        $pageViewsQuery = "SELECT COUNT(*) as total FROM events 
                          WHERE event_type = 'pageview' " . 
                          ($dateCondition ? str_replace("WHERE", "AND", $dateCondition) : "");
        $pageViewsResult = $conn->query($pageViewsQuery);
        $totalPageViews = $pageViewsResult->fetch_assoc()['total'];
        
        // Total scrolls
        $scrollsQuery = "SELECT COUNT(*) as total FROM events 
                        WHERE event_type = 'scroll' " . 
                        ($dateCondition ? str_replace("WHERE", "AND", $dateCondition) : "");
        $scrollsResult = $conn->query($scrollsQuery);
        $totalScrolls = $scrollsResult->fetch_assoc()['total'];
        
        // Total tab switches
        $tabSwitchesQuery = "SELECT COUNT(*) as total FROM events 
                            WHERE event_type = 'tab_switch' " . 
                            ($dateCondition ? str_replace("WHERE", "AND", $dateCondition) : "");
        $tabSwitchesResult = $conn->query($tabSwitchesQuery);
        $totalTabSwitches = $tabSwitchesResult->fetch_assoc()['total'];
        
        // Total continue clicks
        $continueClicksQuery = "SELECT COUNT(*) as total FROM events 
                               WHERE event_type = 'continue' " . 
                               ($dateCondition ? str_replace("WHERE", "AND", $dateCondition) : "");
        $continueClicksResult = $conn->query($continueClicksQuery);
        $totalContinueClicks = $continueClicksResult->fetch_assoc()['total'];
        
        return [
            'totalVisitors' => $totalVisitors,
            'totalSessions' => $totalSessions,
            'averageDuration' => formatDuration($avgDuration),
            'totalPageViews' => $totalPageViews,
            'totalScrolls' => $totalScrolls,
            'totalTabSwitches' => $totalTabSwitches,
            'totalContinueClicks' => $totalContinueClicks
        ];
        
    } catch (Exception $e) {
        $logger->error("Error getting dashboard stats: " . $e->getMessage());
        return [
            'totalVisitors' => 0,
            'totalSessions' => 0,
            'averageDuration' => '0:00',
            'totalPageViews' => 0,
            'totalScrolls' => 0,
            'totalTabSwitches' => 0,
            'totalContinueClicks' => 0
        ];
    }
}

// Get recent sessions
function getRecentSessions($limit = 10) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $logger = new Logger();
    
    // Apply date filter if set
    $dateFilter = $_GET['date-range'] ?? 'all';
    $dateCondition = '';
    
    switch ($dateFilter) {
        case 'today':
            $dateCondition = "WHERE DATE(s.start_time) = CURDATE()";
            break;
        case 'yesterday':
            $dateCondition = "WHERE DATE(s.start_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'week':
            $dateCondition = "WHERE s.start_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $dateCondition = "WHERE s.start_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        default:
            $dateCondition = "";
    }
    
    try {
        $query = "SELECT s.*, 
                 (SELECT COUNT(*) FROM events WHERE session_id = s.id AND event_type = 'pageview') as pages_viewed
                 FROM sessions s
                 $dateCondition
                 ORDER BY s.start_time DESC
                 LIMIT ?";
                 
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sessions = [];
        while ($row = $result->fetch_assoc()) {
            $sessions[] = $row;
        }
        
        return $sessions;
        
    } catch (Exception $e) {
        $logger->error("Error getting recent sessions: " . $e->getMessage());
        return [];
    }
}

// Get page view counts
function getPageViewCounts() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $logger = new Logger();
    
    // Apply date filter if set
    $dateFilter = $_GET['date-range'] ?? 'all';
    $dateCondition = '';
    
    switch ($dateFilter) {
        case 'today':
            $dateCondition = "AND DATE(e.timestamp) = CURDATE()";
            break;
        case 'yesterday':
            $dateCondition = "AND DATE(e.timestamp) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'week':
            $dateCondition = "AND e.timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $dateCondition = "AND e.timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        default:
            $dateCondition = "";
    }
    
    try {
        $query = "SELECT p.id, p.title, 
                 COUNT(CASE WHEN e.event_type = 'pageview' THEN 1 END) as views,
                 AVG(CASE WHEN e.event_type = 'page_exit' THEN 
                     JSON_EXTRACT(e.event_data, '$.duration') 
                 END) as avg_time,
                 COUNT(CASE WHEN e.event_type = 'tab_switch' THEN 1 END) as tab_switches
                 FROM pages p
                 LEFT JOIN events e ON e.page_id = p.id $dateCondition
                 GROUP BY p.id
                 ORDER BY views DESC";
                 
        $result = $conn->query($query);
        
        $pageViews = [];
        while ($row = $result->fetch_assoc()) {
            $pageViews[] = $row;
        }
        
        return $pageViews;
        
    } catch (Exception $e) {
        $logger->error("Error getting page view counts: " . $e->getMessage());
        return [];
    }
}

// Format duration from seconds to readable time
function formatDuration($seconds) {
    if (!$seconds) return '0:00';
    
    $minutes = floor($seconds / 60);
    $hours = floor($minutes / 60);
    $minutes %= 60;
    $seconds %= 60;
    
    if ($hours > 0) {
        return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
    } else {
        return sprintf('%d:%02d', $minutes, $seconds);
    }
}

// Get session details by ID
function getSessionDetails($sessionId) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $logger = new Logger();
    
    try {
        // Get session info
        $sessionQuery = "SELECT * FROM sessions WHERE id = ?";
        $stmt = $conn->prepare($sessionQuery);
        $stmt->bind_param("i", $sessionId);
        $stmt->execute();
        $sessionResult = $stmt->get_result();
        
        if ($sessionResult->num_rows === 0) {
            return null;
        }
        
        $session = $sessionResult->fetch_assoc();
        
        // Get all events for this session
        $eventsQuery = "SELECT e.*, s.visitor_id as visitor_name, p.title as page_title, p.slug as page_slug
                       FROM events e
                       JOIN sessions s ON e.session_id = s.id
                       JOIN pages p ON e.page_id = p.id
                       WHERE e.session_id = ?
                       ORDER BY e.timestamp ASC";
        $stmt = $conn->prepare($eventsQuery);
        $stmt->bind_param("i", $sessionId);
        $stmt->execute();
        $eventsResult = $stmt->get_result();
        
        $events = [];
        while ($row = $eventsResult->fetch_assoc()) {
            $events[] = $row;
        }
        
        return [
            'session' => $session,
            'events' => $events
        ];
        
    } catch (Exception $e) {
        $logger->error("Error getting session details: " . $e->getMessage());
        return null;
    }
}

// Get session events for playback
function getSessionEventsForPlayback($sessionId) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $logger = new Logger();
    
    try {
        $query = "SELECT e.*, p.title as page_title, p.slug as page_slug
                 FROM events e
                 JOIN pages p ON e.page_id = p.id
                 WHERE e.session_id = ?
                 ORDER BY e.timestamp ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $events = [];
        while ($row = $result->fetch_assoc()) {
            // Parse JSON data
            $row['event_data'] = json_decode($row['event_data'], true);
            $events[] = $row;
        }
        
        return $events;
        
    } catch (Exception $e) {
        $logger->error("Error getting session events for playback: " . $e->getMessage());
        return [];
    }
}

// Export session data to CSV
function exportSessionsToCSV() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $logger = new Logger();
    
    try {
        $query = "SELECT s.id, s.visitor_id, s.start_time, s.end_time, s.total_duration, 
                 s.referrer, s.user_agent, s.is_mobile,
                 (SELECT COUNT(*) FROM events WHERE session_id = s.id AND event_type = 'pageview') as page_views
                 FROM sessions s
                 ORDER BY s.start_time DESC";
        $result = $conn->query($query);
        
        $filename = 'sessions_export_' . date('Y-m-d') . '.csv';
        
        // Headers for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, [
            'Session ID',
            'Visitor ID',
            'Start Time',
            'End Time',
            'Duration (seconds)',
            'Referrer',
            'User Agent',
            'Mobile Device',
            'Page Views'
        ]);
        
        // Add data
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['visitor_id'],
                $row['start_time'],
                $row['end_time'],
                $row['total_duration'],
                $row['referrer'],
                $row['user_agent'],
                $row['is_mobile'] ? 'Yes' : 'No',
                $row['page_views']
            ]);
        }
        
        fclose($output);
        exit;
        
    } catch (Exception $e) {
        $logger->error("Error exporting sessions to CSV: " . $e->getMessage());
        return false;
    }
}

// Export events data to CSV
function exportEventsToCSV() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $logger = new Logger();
    
    try {
        $query = "SELECT e.id, e.session_id, s.visitor_id as visitor_name, p.title as page_title, 
                 e.event_type, e.timestamp, e.event_data
                 FROM events e
                 JOIN sessions s ON e.session_id = s.id
                 JOIN pages p ON e.page_id = p.id
                 ORDER BY e.timestamp DESC";
        $result = $conn->query($query);
        
        $filename = 'events_export_' . date('Y-m-d') . '.csv';
        
        // Headers for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, [
            'Event ID',
            'Session ID',
            'Visitor Name',
            'Page',
            'Event Type',
            'Timestamp',
            'Event Data'
        ]);
        
        // Add data
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['session_id'],
                $row['visitor_name'],
                $row['page_title'],
                $row['event_type'],
                $row['timestamp'],
                $row['event_data']
            ]);
        }
        
        fclose($output);
        exit;
        
    } catch (Exception $e) {
        $logger->error("Error exporting events to CSV: " . $e->getMessage());
        return false;
    }
}