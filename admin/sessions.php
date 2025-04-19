<?php
// /admin/sessions.php
// Sessions list and details

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

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Pagination variables
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Apply date filter if set
$dateFilter = $_GET['date-range'] ?? 'all';
$dateCondition = '';

switch ($dateFilter) {
    case 'today':
        $dateCondition = "WHERE DATE(start_time) = CURDATE()";
        break;
    case 'yesterday':
        $dateCondition = "WHERE DATE(start_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        break;
    case 'week':
        $dateCondition = "WHERE start_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $dateCondition = "WHERE start_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    default:
        $dateCondition = "";
}

// Check if we're viewing a specific session
$sessionId = isset($_GET['session']) ? intval($_GET['session']) : 0;
$sessionDetails = null;

if ($sessionId > 0) {
    $sessionDetails = getSessionDetails($sessionId);
}

// Get sessions list with pagination
try {
    // Count total sessions
    $countQuery = "SELECT COUNT(*) as total FROM sessions $dateCondition";
    $countResult = $conn->query($countQuery);
    $totalSessions = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalSessions / $perPage);
    
    // Get sessions for current page
    $query = "SELECT s.*, 
             (SELECT COUNT(*) FROM events WHERE session_id = s.id AND event_type = 'pageview') as pages_viewed
             FROM sessions s
             $dateCondition
             ORDER BY s.start_time DESC
             LIMIT ?, ?";
             
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $offset, $perPage);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sessions = [];
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }
    
} catch (Exception $e) {
    $logger->error("Error getting sessions: " . $e->getMessage());
    $sessions = [];
    $totalSessions = 0;
    $totalPages = 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessions - <?php echo SITE_NAME; ?></title>
    
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
                <h1 class="site-title">Sessions</h1>
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
            <?php if ($sessionDetails): ?>
                <!-- Single Session Details -->
                <div class="playback-container">
                    <div class="playback-header">
                        <h2>Session Details: #<?php echo $sessionId; ?></h2>
                        <div>
                            <a href="playback.php?session=<?php echo $sessionId; ?>" class="btn">Playback Session</a>
                            <a href="sessions.php" class="btn">Back to Sessions</a>
                        </div>
                    </div>
                    
                    <div class="session-info">
                        <div class="session-info-grid">
                            <div class="info-item">
                                <div class="info-label">Visitor ID</div>
                                <div class="info-value"><?php echo htmlspecialchars(substr($sessionDetails['session']['visitor_id'],0,10)); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Start Time</div>
                                <div class="info-value"><?php echo date('M d, Y H:i:s', strtotime($sessionDetails['session']['start_time'])); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">End Time</div>
                                <div class="info-value">
                                    <?php echo $sessionDetails['session']['end_time'] ? date('M d, Y H:i:s', strtotime($sessionDetails['session']['end_time'])) : 'Still Active'; ?>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Duration</div>
                                <div class="info-value"><?php echo formatDuration($sessionDetails['session']['total_duration']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Device</div>
                                <div class="info-value"><?php echo $sessionDetails['session']['is_mobile'] ? 'Mobile' : 'Desktop'; ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Referrer</div>
                                <div class="info-value"><?php echo htmlspecialchars($sessionDetails['session']['referrer'] ?: 'Direct'); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">User Agent</div>
                                <div class="info-value" style="font-size: 12px; word-break: break-all;">
                                    <?php echo htmlspecialchars($sessionDetails['session']['user_agent']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h3>Session Events</h3>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Visitor</th>
                                <th>Event Type</th>
                                <th>Page</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessionDetails['events'] as $event): ?>
                            <tr>
                                <td><?php echo date('H:i:s', strtotime($event['timestamp'])); ?></td>
                                <td><?php echo htmlspecialchars($event['visitor_name'] ?? ''); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $event['event_type'])); ?></td>
                                <td><?php echo htmlspecialchars($event['page_title']); ?></td>
                                <td>
                                    <?php 
                                    $eventData = json_decode($event['event_data'], true);
                                    switch ($event['event_type']) {
                                        case 'pageview':
                                            echo "URL: " . htmlspecialchars($eventData['url'] ?? 'Unknown');
                                            break;
                                        case 'scroll':
                                            echo "Scrolled from " . ($eventData['startPosition'] ?? '0') . "px to " . ($eventData['endPosition'] ?? '0') . "px (" . ($eventData['scrollPercentage'] ?? '0') . "%)";
                                            break;
                                        case 'tab_switch':
                                            echo "Switched to tab: " . htmlspecialchars($eventData['tabLabel'] ?? 'Unknown');
                                            break;
                                        case 'navigation':
                                            echo "Navigated to: " . htmlspecialchars($eventData['label'] ?? 'Unknown');
                                            break;
                                        case 'continue':
                                            echo "Clicked continue button to: " . htmlspecialchars($eventData['nextPageUrl'] ?? 'Unknown');
                                            break;
                                        default:
                                            echo "Event data: " . htmlspecialchars(substr(json_encode($eventData), 0, 100)) . (strlen(json_encode($eventData)) > 100 ? '...' : '');
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <!-- Sessions List -->
                <div class="dashboard-header">
                    <h2>All Sessions</h2>
                    <div class="date-filter">
                        <form action="" method="get">
                            <label for="date-range">Time Period:</label>
                            <select name="date-range" id="date-range" onchange="this.form.submit()">
                                <option value="today" <?php echo isset($_GET['date-range']) && $_GET['date-range'] === 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="yesterday" <?php echo isset($_GET['date-range']) && $_GET['date-range'] === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                                <option value="week" <?php echo isset($_GET['date-range']) && $_GET['date-range'] === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="month" <?php echo isset($_GET['date-range']) && $_GET['date-range'] === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                                <option value="all" <?php echo !isset($_GET['date-range']) || $_GET['date-range'] === 'all' ? 'selected' : ''; ?>>All Time</option>
                            </select>
                        </form>
                    </div>
                </div>
                
                <div class="sessions-list">
                    <p>Showing <?php echo count($sessions); ?> of <?php echo $totalSessions; ?> sessions</p>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Visitor</th>
                                <th>Date & Time</th>
                                <th>Duration</th>
                                <th>Pages</th>
                                <th>Device</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td><?php echo $session['id']; ?></td>
                                <td><?php echo substr($session['visitor_id'], 0, 8) . '...'; ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($session['start_time'])); ?></td>
                                <td><?php echo formatDuration($session['total_duration']); ?></td>
                                <td><?php echo $session['pages_viewed']; ?></td>
                                <td><?php echo $session['is_mobile'] ? 'Mobile' : 'Desktop'; ?></td>
                                <td>
                                    <a href="sessions.php?session=<?php echo $session['id']; ?>" class="action-btn">Details</a>
                                    <a href="playback.php?session=<?php echo $session['id']; ?>" class="action-btn">Playback</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="page-current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?><?php echo isset($_GET['date-range']) ? '&date-range=' . $_GET['date-range'] : ''; ?>" class="page-link"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="admin-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Admin Dashboard</p>
        </div>
    </footer>
    
    <style>
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        
        .page-link, .page-current {
            display: inline-block;
            padding: 5px 10px;
            margin: 0 3px;
            border: 1px solid var(--border);
            border-radius: var(--border-radius);
        }
        
        .page-current {
            background-color: var(--primary);
            color: white;
            font-weight: bold;
        }
    </style>
</body>
</html>
