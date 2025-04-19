<?php
// /api/log_api_call.php
// Utility for logging API calls and responses to the database
require_once '../includes/db.php';

// Helper to get real client IP behind proxies
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

function log_api_call($endpoint, $method, $headers, $body, $query_params, $ip_address) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("INSERT INTO api_calls (endpoint, method, headers, body, query_params, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $endpoint, $method, $headers, $body, $query_params, $ip_address);
    $stmt->execute();
    $id = $conn->insert_id;
    $stmt->close();
    return $id;
}

function log_api_response($id, $response) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("UPDATE api_calls SET response = ?, finished_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("si", $response, $id);
    $stmt->execute();
    $stmt->close();
}
?>
