<?php
// /admin/ip-test.php
// Simple script to show all possible client IPs and proxy headers
header('Content-Type: text/plain');

echo "REMOTE_ADDR: ", $_SERVER['REMOTE_ADDR'] ?? 'N/A', "\n";
echo "HTTP_X_FORWARDED_FOR: ", $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'N/A', "\n";
echo "HTTP_X_REAL_IP: ", $_SERVER['HTTP_X_REAL_IP'] ?? 'N/A', "\n";
echo "HTTP_CLIENT_IP: ", $_SERVER['HTTP_CLIENT_IP'] ?? 'N/A', "\n";
echo "All headers:\n";
foreach (getallheaders() as $name => $value) {
    echo $name . ': ' . $value . "\n";
}
?>
