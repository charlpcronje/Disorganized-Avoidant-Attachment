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
$logger = new Logger();
function get_client_ip() {
    global $logger;
    $logger->info('get_client_ip() called');
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        $logger->info('get_client_ip() using HTTP_X_FORWARDED_FOR: ' . $ip);
        return trim($ip);
    }
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $logger->info('get_client_ip() using HTTP_X_REAL_IP: ' . $_SERVER['HTTP_X_REAL_IP']);
        return $_SERVER['HTTP_X_REAL_IP'];
    }
    $logger->info('get_client_ip() using REMOTE_ADDR: ' . ($_SERVER['REMOTE_ADDR'] ?? ''));
    return $_SERVER['REMOTE_ADDR'] ?? '';
}
$logger->info('get_client_ip() finished');

$ip_address = get_client_ip();
$apiCallId = log_api_call($endpoint, $method, $headers, $body, $query_params, $ip_address);

// Register shutdown function to log response
function log_api_call_response() {
    global $apiCallId, $logger;
    $logger->info('log_api_call_response() called');
    $response = ob_get_contents();
    $logger->info('log_api_call_response() got response: ' . print_r($response, true));
    log_api_response($apiCallId, $response);
    $logger->info('log_api_call_response() finished');
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

$logger->info('Starting session and events sync.');
$logger->info('Raw input: ' . print_r($input, true));
$frontendSessionId = $input['sessionId'];
$logger->info('Extracted frontendSessionId: ' . print_r($frontendSessionId, true));
$events = $input['events'];
$logger->info('Extracted events: ' . print_r($events, true));

// Validate session ID
if (!isset($frontendSessionId) || trim($frontendSessionId) === '') {
    $response['message'] = 'Invalid session ID';
    $logger->error('Sync API: Invalid session ID: ' . print_r($frontendSessionId, true));
    echo json_encode($response);
    exit;
}
$logger->info('Session ID validated: ' . $frontendSessionId);

// Find or create session in the sessions table
$logger->info('Checking for existing session in DB for session_id: ' . $frontendSessionId);
$sessionLookupStmt = $conn->prepare("SELECT id FROM sessions WHERE session_id = ? LIMIT 1");
$logger->info('Prepared session lookup statement.');
$sessionLookupStmt->bind_param("s", $frontendSessionId);
$logger->info('Bound param for session lookup.');
$sessionLookupStmt->execute();
$logger->info('Executed session lookup statement.');
$sessionLookupStmt->bind_result($sessionRowId);
$found = $sessionLookupStmt->fetch();
$logger->info('Session lookup fetch result: found=' . print_r($found, true) . ', sessionRowId=' . print_r($sessionRowId, true));
$sessionLookupStmt->close();

if ($found && $sessionRowId) {
    $sessionId = $sessionRowId;
    $logger->info('Existing session found. Using sessionId: ' . $sessionId);
} else {
    $logger->info('No session found. Inserting new session for session_id: ' . $frontendSessionId);
    // Insert new session row
    $insertSessionStmt = $conn->prepare("INSERT INTO sessions (session_id, start_time) VALUES (?, CURRENT_TIMESTAMP)");
    $logger->info('Prepared insert statement for new session.');
    $insertSessionStmt->bind_param("s", $frontendSessionId);
    $logger->info('Bound param for new session insert.');
    if ($insertSessionStmt->execute()) {
        $sessionId = $conn->insert_id;
        $logger->info('Inserted new session. New sessionId: ' . $sessionId);
    } else {
        $response['message'] = 'Failed to create session';
        $logger->error('Sync API: Failed to insert session: ' . $conn->error);
        echo json_encode($response);
        exit;
    }
    $insertSessionStmt->close();
}
$logger->info('Final sessionId to use for event insert: ' . $sessionId);

$logger->info('Instantiating database connection.');
$db = Database::getInstance();
$logger->info('Database instance obtained.');
$conn = $db->getConnection();
$logger->info('Database connection obtained.');

// Begin transaction
$logger->info('Beginning transaction.');
$conn->begin_transaction();

try {
    $logger->info('Entered try block for event processing.');
    // Update session end time and visitor name if provided
    $logger->info('Preparing to update session end time for sessionId: ' . $sessionId);
    $stmt = $conn->prepare("UPDATE sessions SET end_time = CURRENT_TIMESTAMP, total_duration = TIMESTAMPDIFF(SECOND, start_time, CURRENT_TIMESTAMP) WHERE id = ?");
    $logger->info('Prepared statement for updating session end time.');
    $stmt->bind_param("i", $sessionId);
    $logger->info('Bound sessionId param for update: ' . $sessionId);
    $stmt->execute();
    $logger->info('Executed session end time update for sessionId: ' . $sessionId);

    // Process each event
    $successCount = 0;
    $logger->info('Initialized successCount: 0');
    $errorCount = 0;
    $logger->info('Initialized errorCount: 0');

    $insertStmt = $conn->prepare("INSERT INTO events (session_id, page_id, event_type, event_data) VALUES (?, ?, ?, ?)");
    $logger->info('Prepared statement for event insert.');

    foreach ($events as $idx => $event) {
        $logger->info('Processing event index: ' . $idx . ', event: ' . print_r($event, true));
        if (!isset($event['type']) || !isset($event['pageId']) || !isset($event['timestamp']) || !isset($event['data'])) {
            $errorCount++;
            $logger->error('Event missing required fields. Skipping. errorCount now: ' . $errorCount);
            continue;
        }

        $pageId = intval($event['pageId']);
        $logger->info('Parsed pageId: ' . $pageId);
        $eventType = $event['type'];
        $logger->info('Parsed eventType: ' . $eventType);
        
        // Always attach visitor name to every event
        $visitorName = isset($_SESSION['visitor_name']) ? $_SESSION['visitor_name'] : null;
        $logger->info('Visitor name: ' . print_r($visitorName, true));
        $eventDataArr = $event['data'];
        $logger->info('Event data array: ' . print_r($eventDataArr, true));
        $eventDataArr['visitorName'] = $visitorName;
        $eventData = json_encode($eventDataArr);
        $logger->info('Final eventData JSON: ' . $eventData);

        // Validate event type
        $validTypes = ['pageview', 'scroll', 'click', 'tab_switch', 'continue', 'navigation', 'page_exit'];
        $logger->info('Valid event types: ' . implode(',', $validTypes));
        if (!in_array($eventType, $validTypes)) {
            $errorCount++;
            $logger->error('Invalid event type: ' . $eventType . '. errorCount now: ' . $errorCount);
            continue;
        }

        $logger->info('Binding params for event insert: sessionId=' . $sessionId . ', pageId=' . $pageId . ', eventType=' . $eventType);
        $insertStmt->bind_param("iiss", $sessionId, $pageId, $eventType, $eventData);
        $logger->info('Executing event insert.');
        if ($insertStmt->execute()) {
            $successCount++;
            $logger->info('Event insert successful. successCount now: ' . $successCount);
        } else {
            $errorCount++;
            $logger->error('Failed to insert event: ' . $db->error() . '. errorCount now: ' . $errorCount);
        }
    }

    // Commit transaction
    $logger->info('Committing transaction.');
    $conn->commit();
    $logger->info('Transaction committed.');

    $response = [
        'success' => true,
        'message' => 'Events recorded successfully',
        'stats' => [
            'total' => count($events),
            'success' => $successCount,
            'errors' => $errorCount
        ]
    ];
    $logger->info('Response prepared: ' . print_r($response, true));

} catch (Exception $e) {
    // Rollback transaction on error
    $logger->error('Exception caught during event processing: ' . $e->getMessage());
    $conn->rollback();
    $logger->info('Transaction rolled back.');
    $response['message'] = 'Server error: ' . $e->getMessage();
    $logger->error('Sync API error: ' . $e->getMessage());
}

// Send response
$responseJSON = json_encode($response);
$logger->info('Sending response: ' . $responseJSON);
register_shutdown_function('log_api_call_response', $conn, $apiCallId, $responseJSON);
echo $responseJSON;