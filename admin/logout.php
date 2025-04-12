<?php
// /admin/logout.php
// Admin logout script

// Start session
session_start();

// Include required files
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Initialize logger
$logger = new Logger();

// Log the logout if user was logged in
if (isset($_SESSION['username'])) {
    $logger->info("User {$_SESSION['username']} logged out");
}

// Unset all session variables
$_SESSION = [];

// If a session cookie is used, destroy it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;