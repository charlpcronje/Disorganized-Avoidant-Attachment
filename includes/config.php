<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
session_start();

// Define database connection constants
define('DB_HOST', 'localhost');
define('DB_USER', 'cp');
define('DB_PASS', '4334.4334');
define('DB_NAME', 'attachment_site');

// Error reporting settings
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Site configuration
define('SITE_NAME', 'Understanding and Awareness is key');
define('BASE_URL', 'https://info.nade.webally.co.za/');
define('ADMIN_EMAIL', 'charl@webally.co.za'); // Replace with your admin email
define('CONTACT_EMAIL', 'contact@example.com');

// Set the timezone
date_default_timezone_set('UTC');

// Analytics settings
define('SYNC_INTERVAL', 20000); // milliseconds
define('SCROLL_DEBOUNCE', 500); // milliseconds
