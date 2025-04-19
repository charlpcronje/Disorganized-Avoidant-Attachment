<?php
// /api/sync.php
// Endpoint for receiving and storing analytics data

// Set CORS headers to allow requests from any origin
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-Key');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once __DIR__ . '/log_api_call.php';

// Log incoming API call
$endpoint = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];
$headers = json_encode(getallheaders());
$body = file_get_contents('php://input');
$query_params = json_encode($_GET);
function get_client_ip() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        return trim($ip);
    }
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        return $_SERVER['HTTP_X_REAL_IP'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

$ip_address = get_client_ip();
$apiCallId = log_api_call($endpoint, $method, $headers, $body, $query_params, $ip_address);

// Register shutdown function to log response
function log_api_call_response() {
    global $apiCallId;
    $response = ob_get_contents();
    log_api_response($apiCallId, $response);
}
ob_start();
register_shutdown_function('log_api_call_response');

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
$responseJSON = json_encode($response);
register_shutdown_function('log_api_call_response', $conn, $apiCallId, $responseJSON);
echo $responseJSON;