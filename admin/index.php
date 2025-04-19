<?php
// /admin/index.php
// Admin dashboard for analytics

// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'admin-functions.php';
require_once 'analytics-admin-helpers.php';

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

// Handle IP ignore/unignore actions
if (isset($_POST['ignore_ip'])) {
    addIgnoredIp($_POST['ignore_ip']);
} elseif (isset($_POST['unignore_ip'])) {
    removeIgnoredIp($_POST['unignore_ip']);
}

// Handle archive analytics
if (isset($_POST['archive_name']) && trim($_POST['archive_name']) !== '') {
    archiveActiveAnalytics(trim($_POST['archive_name']));
}

// Get ignored IPs
$ignoredIps = getIgnoredIpsArray();

// Get dashboard statistics (filtered)
$stats = getDashboardStats($ignoredIps);

// Get recent sessions (filtered)
$recentSessions = getRecentSessions(10, $ignoredIps);

// Get page view counts (filtered)
$pageViews = getPageViewCounts($ignoredIps);

// Get all unique IPs with ignore status
$allIps = getAllUniqueIpsWithIgnoreStatus();

// Get analytics archives
$archives = getAnalyticsArchives();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../assets/css/normalize.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="container">
            <div class="header-content">
                <h1 class="site-title">Analytics Dashboard</h1>
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
            <div class="dashboard-header">
    <!-- API Calls Summary Section -->
    <?php
    // API call stats
    $apiTotal = $conn->query("SELECT COUNT(*) AS total FROM api_calls")->fetch_assoc()['total'];
    $apiCompleted = $conn->query("SELECT COUNT(*) AS completed FROM api_calls WHERE response IS NOT NULL AND response != ''")->fetch_assoc()['completed'];
    $apiUncompleted = $conn->query("SELECT COUNT(*) AS uncompleted FROM api_calls WHERE response IS NULL OR response = ''")->fetch_assoc()['uncompleted'];
    ?>
    <div style="margin-bottom:2em; background:#f5f7fa; border:1px solid #dde3ec; padding:15px 20px; border-radius:8px;">
        <strong>API Calls:</strong>
        <span style="margin-left:20px;">Total: <b><?= $apiTotal ?></b></span>
        <span style="margin-left:20px; color:green;">Completed: <b><?= $apiCompleted ?></b></span>
        <span style="margin-left:20px; color:orange;">Uncompleted: <b><?= $apiUncompleted ?></b></span>
        <a href="api-calls.php" style="margin-left:30px; color:#2a5ba5; text-decoration:underline;font-weight:600;">View API Logs</a>
    </div>
                <!-- Analytics Filtering and IP Management -->
                <div class="admin-analytics-tools" style="margin-bottom: 2em;">
                    <h3>Analytics Filtering & IP Management</h3>
                    <form method="post" style="margin-bottom: 1em; display:inline-block;">
                        <input type="text" name="archive_name" placeholder="Archive Name" required />
                        <button type="submit">Archive Current Analytics</button>
                    </form>
                    
                    <div style="margin-bottom:1em;">
                        <strong>Unique Visitor IPs:</strong>
                        <table class="data-table">
                            <thead><tr><th>IP Address</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php foreach($allIps as $ipInfo): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ipInfo['ip']); ?></td>
                                    <td><?php echo $ipInfo['ignored'] ? '<span style="color:red;">Ignored</span>' : '<span style="color:green;">Included</span>'; ?></td>
                                    <td>
                                        <?php if(!$ipInfo['ignored']): ?>
                                            <form method="post" style="display:inline;"><input type="hidden" name="ignore_ip" value="<?php echo htmlspecialchars($ipInfo['ip']); ?>"><button type="submit" title="Exclude from analytics">Exclude</button></form>
                                        <?php else: ?>
                                            <form method="post" style="display:inline;"><input type="hidden" name="unignore_ip" value="<?php echo htmlspecialchars($ipInfo['ip']); ?>"><button type="submit" title="Include in analytics">Include</button></form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="font-size:0.9em; color:#555;">Note: Analytics stats and charts below are filtered to <b>exclude</b> ignored IPs.</div>
                </div>
                <h2>Analytics Overview</h2>
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
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['totalVisitors']; ?></div>
                    <div class="stat-label">Unique Visitors</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['totalSessions']; ?></div>
                    <div class="stat-label">Total Sessions</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['averageDuration']; ?></div>
                    <div class="stat-label">Avg. Session Duration</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['totalPageViews']; ?></div>
                    <div class="stat-label">Page Views</div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="charts-grid">
                <div class="chart-container">
                    <h3>Page Views by Section</h3>
                    <canvas id="pageViewsChart"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3>User Engagement</h3>
                    <canvas id="engagementChart"></canvas>
                </div>
            </div>
            
            <!-- Recent Sessions -->
            <div class="recent-sessions">
                <div class="section-header">
                    <h3>Recent Sessions</h3>
                    <a href="sessions.php" class="view-all">View All</a>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Session ID</th>
                            <th>Visitor</th>
                            <th>Date & Time</th>
                            <th>Duration</th>
                            <th>Pages Viewed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentSessions as $session): ?>
                        <tr>
                            <td><?php echo $session['id']; ?></td>
                            <td><?php echo substr($session['visitor_id'], 0, 8) . '...'; ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($session['start_time'])); ?></td>
                            <td><?php echo formatDuration($session['total_duration']); ?></td>
                            <td><?php echo $session['pages_viewed']; ?></td>
                            <td>
                                <a href="playback.php?session=<?php echo $session['id']; ?>" class="action-btn">Playback</a>
                                <a href="sessions.php?session=<?php echo $session['id']; ?>" class="action-btn">Details</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Most Viewed Pages -->
            <div class="page-views">
                <div class="section-header">
                    <h3>Most Viewed Pages</h3>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>Views</th>
                            <th>Avg. Time on Page</th>
                            <th>Example Tab Switches</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pageViews as $page): ?>
                        <tr>
                            <td><?php echo $page['title']; ?></td>
                            <td><?php echo $page['views']; ?></td>
                            <td><?php echo formatDuration($page['avg_time']); ?></td>
                            <td><?php echo $page['tab_switches']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="admin-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Admin Dashboard</p>
        </div>
    </footer>
    
    <!-- JavaScript for Charts -->
    <script>
        // Page Views Chart
        const pageViewsCtx = document.getElementById('pageViewsChart').getContext('2d');
        const pageViewsChart = new Chart(pageViewsCtx, {
            type: 'pie',
            data: {
                labels: [<?php echo implode(', ', array_map(function($page) { return "'" . $page['title'] . "'"; }, $pageViews)); ?>],
                datasets: [{
                    data: [<?php echo implode(', ', array_map(function($page) { return $page['views']; }, $pageViews)); ?>],
                    backgroundColor: [
                        '#4a6fa5',
                        '#4e7ac7',
                        '#6a8cc9',
                        '#879fd6',
                        '#9fb1e0',
                        '#c1cdea',
                        '#d4daef',
                        '#e5e8f5',
                        '#f0f2fa',
                        '#f8f9fd'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
        
        // User Engagement Chart (Example with dummy data)
        const engagementCtx = document.getElementById('engagementChart').getContext('2d');
        const engagementChart = new Chart(engagementCtx, {
            type: 'bar',
            data: {
                labels: ['Page Views', 'Scrolls', 'Tab Switches', 'Continue Clicks'],
                datasets: [{
                    label: 'User Interactions',
                    data: [
                        <?php echo $stats['totalPageViews']; ?>,
                        <?php echo $stats['totalScrolls']; ?>,
                        <?php echo $stats['totalTabSwitches']; ?>,
                        <?php echo $stats['totalContinueClicks']; ?>
                    ],
                    backgroundColor: '#4a6fa5'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
