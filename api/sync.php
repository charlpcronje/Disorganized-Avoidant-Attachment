<?php
// /api/sync.php
// Endpoint for receiving and storing analytics data

header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$logger = new Logger();
$response = ['success' => false, 'message' => 'Unknown error'];

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Method not allowed';
    echo json_encode($response);
    exit;
}

// Get the raw POST data
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Validate input
if (json_last_error() !== JSON_ERROR_NONE || !isset($input['sessionId']) || !isset($input['events']) || !is_array($input['events'])) {
    $response['message'] = 'Invalid JSON data';
    $logger->error('Sync API: Invalid JSON data received');
    echo json_encode($response);
    exit;
}

$sessionId = intval($input['sessionId']);
$events = $input['events'];

// Validate session ID
if ($sessionId <= 0) {
    $response['message'] = 'Invalid session ID';
    $logger->error('Sync API: Invalid session ID: ' . $sessionId);
    echo json_encode($response);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Begin transaction
$conn->begin_transaction();

try {
    // Update session end time
    $stmt = $conn->prepare("UPDATE sessions SET end_time = CURRENT_TIMESTAMP, total_duration = TIMESTAMPDIFF(SECOND, start_time, CURRENT_TIMESTAMP) WHERE id = ?");
    $stmt->bind_param("i", $sessionId);
    $stmt->execute();
    
    // Process each event
    $successCount = 0;
    $errorCount = 0;
    
    $insertStmt = $conn->prepare("INSERT INTO events (session_id, page_id, event_type, event_data) VALUES (?, ?, ?, ?)");
    
    foreach ($events as $event) {
        if (!isset($event['type']) || !isset($event['pageId']) || !isset($event['timestamp']) || !isset($event['data'])) {
            $errorCount++;
            continue;
        }
        
        $pageId = intval($event['pageId']);
        $eventType = $event['type'];
        $eventData = json_encode($event['data']);
        
        // Validate event type
        $validTypes = ['pageview', 'scroll', 'click', 'tab_switch', 'continue', 'navigation', 'page_exit'];
        if (!in_array($eventType, $validTypes)) {
            $errorCount++;
            continue;
        }
        
        $insertStmt->bind_param("iiss", $sessionId, $pageId, $eventType, $eventData);
        if ($insertStmt->execute()) {
            $successCount++;
        } else {
            $errorCount++;
            $logger->error('Failed to insert event: ' . $db->error());
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    $response = [
        'success' => true,
        'message' => 'Events recorded successfully',
        'stats' => [
            'total' => count($events),
            'success' => $successCount,
            'errors' => $errorCount
        ]
    ];
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $response['message'] = 'Server error: ' . $e->getMessage();
    $logger->error('Sync API error: ' . $e->getMessage());
}

// Send response
echo json_encode($response);