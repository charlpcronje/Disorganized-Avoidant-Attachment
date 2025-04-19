<?php
// /includes/functions.php
// Common functions and utilities

// Simple logging class
class Logger {
    private $logFile;
    
    public function __construct() {
        $this->logFile = dirname(__DIR__) . '/logs/app.log';
        
        // Create logs directory if it doesn't exist
        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }
    
    public function info($message) {
        $this->log('INFO', $message);
    }
    
    public function error($message) {
        $this->log('ERROR', $message);
    }
    
    public function warning($message) {
        $this->log('WARNING', $message);
    }
    
    public function debug($message) {
        $this->log('DEBUG', $message);
    }
    
    private function log($level, $message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        
        try {
            file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        } catch (Exception $e) {
            // If we can't write to the log file, there's not much we can do
            error_log("Failed to write to log file: " . $e->getMessage());
        }
    }
}

// Generate a secure random token
function generateToken($length = 32) {
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes($length / 2));
    } else if (function_exists('openssl_random_pseudo_bytes')) {
        return bin2hex(openssl_random_pseudo_bytes($length / 2));
    }
    return bin2hex(substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', mt_rand(1, 10))), 0, $length / 2));
}

// Clean input data
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Get page by slug
function getPageBySlug($slug) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM pages WHERE slug = ?");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Get next page
function getNextPage($currentOrder) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM pages WHERE order_num > ? ORDER BY order_num ASC LIMIT 1");
    $stmt->bind_param("i", $currentOrder);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Get previous page
function getPreviousPage($currentOrder) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM pages WHERE order_num < ? ORDER BY order_num DESC LIMIT 1");
    $stmt->bind_param("i", $currentOrder);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Get all pages for navigation
function getAllPages() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM pages ORDER BY order_num ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $pages = [];
    while ($row = $result->fetch_assoc()) {
        $pages[] = $row;
    }
    
    return $pages;
}

// Record a page view event
function recordPageView($pageId, $sessionId) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $eventData = json_encode([
        'url' => $_SERVER['REQUEST_URI'],
        'title' => isset($pageTitle) ? $pageTitle : '',
        'timestamp' => time()
    ]);
    
    if (!$sessionId) {
        $logger = new Logger();
        $logger->error("recordPageView called with null sessionId (no session row in DB). Event not logged.");
        return false;
    }
    $stmt = $conn->prepare("INSERT INTO events (session_id, page_id, event_type, event_data) VALUES (?, ?, 'pageview', ?)");
    $stmt->bind_param("iis", $sessionId, $pageId, $eventData);
    
    return $stmt->execute();
}

// Create or retrieve a session
function getSession() {
    // Always require a valid visitor_name for session creation
    if (!isset($_SESSION['visitor_name']) || empty($_SESSION['visitor_name'])) {
        header('Location: /index.php');
        exit;
    }
    if (!isset($_SESSION['db_session_id'])) {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sessionId = session_id();
        // Use the visitor name from session if available, else fallback to cookie or random token
        if (isset($_SESSION['visitor_name'])) {
            $visitorId = $_SESSION['visitor_name'];
        } else if (isset($_COOKIE['visitor_id'])) {
            $visitorId = $_COOKIE['visitor_id'];
        } else {
            $visitorId = generateToken();
        }
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $isMobile = preg_match('/(android|iphone|ipad|mobile)/i', $userAgent) ? 1 : 0;
        
        // Set visitor cookie if not exists
        if (!isset($_COOKIE['visitor_id'])) {
            setcookie('visitor_id', $visitorId, time() + (86400 * 365), '/', '', true, true);
        }
        
        // Check if session already exists in database
        $checkStmt = $conn->prepare("SELECT id FROM sessions WHERE session_id = ?");
        $checkStmt->bind_param("s", $sessionId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            // Session exists, get its ID
            $row = $result->fetch_assoc();
            $_SESSION['db_session_id'] = $row['id'];
        } else {
            // Insert new session
            $insertStmt = $conn->prepare("INSERT INTO sessions (session_id, visitor_id, user_agent, ip_address, referrer, is_mobile) VALUES (?, ?, ?, ?, ?, ?)");
            $insertStmt->bind_param("sssssi", $sessionId, $visitorId, $userAgent, $ipAddress, $referrer, $isMobile);
            
            if ($insertStmt->execute()) {
                $_SESSION['db_session_id'] = $conn->insert_id;
            } else {
                $logger = new Logger();
                $logger->error("Failed to create session: " . $conn->error . ". Using temporary session.");
                // Fallback: use a negative session ID to indicate a dummy session (not tracked in DB)
                $_SESSION['db_session_id'] = -1;
            }
        }
    }
    
    // Always return a session id, even if dummy (negative)
    return $_SESSION['db_session_id'] ?? -1;
}

// Update session duration
function updateSessionDuration($sessionId) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("UPDATE sessions SET end_time = CURRENT_TIMESTAMP, total_duration = TIMESTAMPDIFF(SECOND, start_time, CURRENT_TIMESTAMP) WHERE id = ?");
    $stmt->bind_param("i", $sessionId);
    
    return $stmt->execute();
}