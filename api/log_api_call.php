<?php
// /api/log_api_call.php
// Utility for logging API calls and responses to the database
require_once '../includes/db.php';

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
