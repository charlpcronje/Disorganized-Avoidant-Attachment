<?php
// /admin/export.php
// Export analytics data

// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'admin-functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page
    header('Location: login.php');
    exit;
}

// Initialize logger
$logger = new Logger();

// Check if exporting data
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];
    
    switch ($exportType) {
        case 'sessions':
            exportSessionsToCSV();
            break;
        case 'events':
            exportEventsToCSV();
            break;
        default:
            // Invalid export type
            header('Location: export.php');
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Data - <?php echo SITE_NAME; ?></title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../assets/css/normalize.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="container">
            <div class="header-content">
                <h1 class="site-title">Export Data</h1>
                <div class="user-actions">
                    <span class="username">Welcome, <?php echo $_SESSION['username']; ?></span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Admin Navigation -->
    <nav class="admin-nav">
        <div class="container">
            <ul class="nav-list">
                <li class="nav-item"><a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">Dashboard</a></li>
                <li class="nav-item"><a href="sessions.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'sessions.php' ? 'active' : ''; ?>">Sessions</a></li>
                <li class="nav-item"><a href="playback.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'playback.php' ? 'active' : ''; ?>">Session Playback</a></li>
                <li class="nav-item"><a href="examples.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'examples.php' ? 'active' : ''; ?>">Manage Examples</a></li>
                <li class="nav-item"><a href="export.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'export.php' ? 'active' : ''; ?>">Export Data</a></li>
            </ul>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="admin-content">
        <div class="container">
            <div class="export-container">
                <h2>Export Analytics Data</h2>
                <p>You can export different types of data from the analytics system. Select an option below to download the data in CSV format.</p>
                
                <div class="export-options">
                    <div class="export-option">
                        <h3>Sessions Data</h3>
                        <p>Export information about all user sessions, including visitor ID, duration, device type, and page views.</p>
                        <a href="export.php?export=sessions" class="btn export-btn">Export Sessions</a>
                    </div>
                    
                    <div class="export-option">
                        <h3>Events Data</h3>
                        <p>Export detailed event data including page views, scrolls, tab switches, and navigation events.</p>
                        <a href="export.php?export=events" class="btn export-btn">Export Events</a>
                    </div>
                </div>
                
                <div class="export-notes">
                    <h3>Export Notes</h3>
                    <ul>
                        <li>Exports may take some time to generate depending on the amount of data.</li>
                        <li>All times are in UTC.</li>
                        <li>CSV files can be opened in Excel, Google Sheets, or any other spreadsheet application.</li>
                        <li>For large data sets, consider filtering by date range before exporting.</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="admin-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Admin Dashboard</p>
        </div>
    </footer>
    
    <style>
        .export-container {
            background-color: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .export-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin: 30px 0;
        }
        
        .export-option {
            background-color: var(--background-alt);
            padding: 20px;
            border-radius: var(--border-radius);
            border: 1px solid var(--border);
        }
        
        .export-option h3 {
            margin-top: 0;
            color: var(--primary);
        }
        
        .export-btn {
            margin-top: 15px;
        }
        
        .export-notes {
            background-color: #fff8e1;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-top: 30px;
        }
        
        .export-notes h3 {
            margin-top: 0;
            color: #f39c12;
        }
        
        .export-notes ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .export-notes li {
            margin-bottom: 10px;
        }
        
        .export-notes li:last-child {
            margin-bottom: 0;
        }
    </style>
</body>
</html>
