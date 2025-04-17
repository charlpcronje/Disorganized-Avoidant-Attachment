<?php
// Analytics Admin Helper Functions

// Get all unique IPs from sessions, mark which are ignored
function getAllUniqueIpsWithIgnoreStatus() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $ips = [];
    $result = $conn->query("SELECT DISTINCT ip_address FROM sessions WHERE ip_address IS NOT NULL AND ip_address != ''");
    $ignored = getIgnoredIpsArray();
    while ($row = $result->fetch_assoc()) {
        $ip = $row['ip_address'];
        $ips[] = [
            'ip' => $ip,
            'ignored' => in_array($ip, $ignored)
        ];
    }
    return $ips;
}

// Get ignored IPs as array
function getIgnoredIpsArray() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $ips = [];
    $result = $conn->query("SELECT ip_address FROM analytics_ignored_ips");
    while ($row = $result->fetch_assoc()) {
        $ips[] = $row['ip_address'];
    }
    return $ips;
}

// Add ignored IP
function addIgnoredIp($ip) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("INSERT IGNORE INTO analytics_ignored_ips (ip_address) VALUES (?)");
    $stmt->bind_param("s", $ip);
    return $stmt->execute();
}

// Remove ignored IP
function removeIgnoredIp($ip) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("DELETE FROM analytics_ignored_ips WHERE ip_address = ?");
    $stmt->bind_param("s", $ip);
    return $stmt->execute();
}

// Archive all active analytics events to a given name
function archiveActiveAnalytics($archiveName) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $logger = new Logger();
    try {
        $stmt = $conn->prepare("INSERT INTO analytics_archives (name) VALUES (?)");
        $stmt->bind_param("s", $archiveName);
        $stmt->execute();
        $archiveId = $conn->insert_id;
        $updateStmt = $conn->prepare("UPDATE events SET archive_id = ? WHERE archive_id IS NULL");
        $updateStmt->bind_param("i", $archiveId);
        $updateStmt->execute();
        return $archiveId;
    } catch (Exception $e) {
        $logger->error('Error archiving analytics: ' . $e->getMessage());
        return false;
    }
}

// List analytics archives
function getAnalyticsArchives() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $archives = [];
    $result = $conn->query("SELECT * FROM analytics_archives ORDER BY created_at DESC");
    while ($row = $result->fetch_assoc()) {
        $archives[] = $row;
    }
    return $archives;
}
