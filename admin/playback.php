<?php
// /admin/playback.php
// Session playback interface

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

// Get session ID from URL parameter
$sessionId = isset($_GET['session']) ? intval($_GET['session']) : 0;

// Validate session ID
if ($sessionId <= 0) {
    header('Location: sessions.php');
    exit;
}

// Get session details and events
$sessionData = getSessionDetails($sessionId);
if (!$sessionData) {
    // Session not found
    header('Location: sessions.php');
    exit;
}

$session = $sessionData['session'];
$events = $sessionData['events'];

// Get playback events
$playbackEvents = getSessionEventsForPlayback($sessionId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Playback - <?php echo SITE_NAME; ?></title>
    
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
                <h1 class="site-title">Session Playback</h1>
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
            <div class="playback-container">
                <div class="playback-header">
                    <h2>Session Playback: #<?php echo $sessionId; ?></h2>
                    <a href="sessions.php" class="btn">Back to Sessions</a>
                </div>
                
                <div class="session-info">
                    <div class="session-info-grid">
                        <div class="info-item">
                            <div class="info-label">Visitor ID</div>
                            <div class="info-value"><?php echo htmlspecialchars($session['visitor_id']); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Start Time</div>
                            <div class="info-value"><?php echo date('M d, Y H:i:s', strtotime($session['start_time'])); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Duration</div>
                            <div class="info-value"><?php echo formatDuration($session['total_duration']); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Device</div>
                            <div class="info-value"><?php echo $session['is_mobile'] ? 'Mobile' : 'Desktop'; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="playback-controls">
                    <button id="playBtn">▶ Play</button>
                    <button id="pauseBtn">⏸ Pause</button>
                    <button id="restartBtn">⟲ Restart</button>
                    <select id="playbackSpeed">
                        <option value="0.5">0.5x Speed</option>
                        <option value="1" selected>1x Speed</option>
                        <option value="2">2x Speed</option>
                        <option value="4">4x Speed</option>
                    </select>
                </div>
                
                <div class="playback-viewer">
                    <iframe id="playbackFrame" src="about:blank" width="100%" height="600" frameborder="0"></iframe>
                </div>
                
                <div class="event-timeline">
                    <h3>Event Timeline</h3>
                    
                    <div id="timeline-container">
                        <?php foreach ($events as $event): ?>
                        <div class="timeline-item" data-timestamp="<?php echo strtotime($event['timestamp']); ?>">
                            <div class="timeline-time"><?php echo date('H:i:s', strtotime($event['timestamp'])); ?></div>
                            <div class="timeline-content">
                                <div class="timeline-event-type"><?php echo ucfirst(str_replace('_', ' ', $event['event_type'])); ?></div>
                                <div class="timeline-details">
                                    <?php
                                    $eventData = json_decode($event['event_data'], true);
                                    switch ($event['event_type']) {
                                        case 'pageview':
                                            echo 'Page: ' . htmlspecialchars($event['page_title']);
                                            break;
                                        case 'scroll':
                                            echo 'Scrolled from ' . $eventData['startPosition'] . 'px to ' . $eventData['endPosition'] . 'px (' . $eventData['scrollPercentage'] . '%)';
                                            break;
                                        case 'tab_switch':
                                            echo 'Switched to tab: ' . htmlspecialchars($eventData['tabLabel'] ?? 'Unknown');
                                            break;
                                        case 'navigation':
                                            echo 'Navigated to: ' . htmlspecialchars($eventData['label'] ?? 'Unknown');
                                            break;
                                        case 'continue':
                                            echo 'Clicked continue button to next page';
                                            break;
                                        default:
                                            echo 'Event data: ' . htmlspecialchars(substr(json_encode($eventData), 0, 100)) . (strlen(json_encode($eventData)) > 100 ? '...' : '');
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
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
    
    <!-- JavaScript for Playback -->
    <script>
        // Playback data
        const playbackEvents = <?php echo json_encode($playbackEvents); ?>;
        const sessionStartTime = new Date("<?php echo $session['start_time']; ?>").getTime();
        let currentEventIndex = 0;
        let playbackSpeed = 1;
        let isPlaying = false;
        let playbackTimeout;

        // DOM elements
        const playBtn = document.getElementById('playBtn');
        const pauseBtn = document.getElementById('pauseBtn');
        const restartBtn = document.getElementById('restartBtn');
        const speedSelect = document.getElementById('playbackSpeed');
        const playbackFrame = document.getElementById('playbackFrame');
        const timelineContainer = document.getElementById('timeline-container');

        // Initialize playback frame with the first page
        function initializePlayback() {
            if (playbackEvents.length === 0) {
                alert('No events to playback for this session.');
                return;
            }
            
            // Find first pageview event
            const firstPageview = playbackEvents.find(event => event.event_type === 'pageview');
            if (firstPageview) {
                const url = `../index.php?page=${firstPageview.page_slug}`;
                playbackFrame.src = url;
            } else {
                playbackFrame.src = '../index.php';
            }
            
            // Highlight the first event in timeline
            highlightTimelineEvent(0);
        }

        // Play button click
        playBtn.addEventListener('click', function() {
            if (!isPlaying) {
                isPlaying = true;
                playNextEvent();
            }
        });

        // Pause button click
        pauseBtn.addEventListener('click', function() {
            isPlaying = false;
            clearTimeout(playbackTimeout);
        });

        // Restart button click
        restartBtn.addEventListener('click', function() {
            isPlaying = false;
            clearTimeout(playbackTimeout);
            currentEventIndex = 0;
            initializePlayback();
        });

        // Speed change
        speedSelect.addEventListener('change', function() {
            playbackSpeed = parseFloat(this.value);
        });

        // Play next event in sequence
        function playNextEvent() {
            if (!isPlaying || currentEventIndex >= playbackEvents.length) {
                isPlaying = false;
                return;
            }
            
            const currentEvent = playbackEvents[currentEventIndex];
            const nextEvent = playbackEvents[currentEventIndex + 1];
            
            // Process current event
            processEvent(currentEvent);
            
            // Highlight current event in timeline
            highlightTimelineEvent(currentEventIndex);
            
            // Scroll timeline to current event
            scrollTimelineToEvent(currentEventIndex);
            
            // Move to next event
            currentEventIndex++;
            
            // Schedule next event if there is one
            if (nextEvent) {
                const currentTime = currentEvent.timestamp ? new Date(currentEvent.timestamp).getTime() : sessionStartTime;
                const nextTime = nextEvent.timestamp ? new Date(nextEvent.timestamp).getTime() : sessionStartTime;
                
                // Calculate delay based on real time difference and playback speed
                let delay = (nextTime - currentTime) / playbackSpeed;
                
                // Cap the delay to prevent very long waits
                delay = Math.min(delay, 5000 / playbackSpeed);
                
                // Schedule next event
                playbackTimeout = setTimeout(playNextEvent, delay);
            } else {
                isPlaying = false;
                console.log('Playback complete');
            }
        }

        // Process a single event
        function processEvent(event) {
            console.log('Processing event:', event);
            
            switch (event.event_type) {
                case 'pageview':
                    handlePageview(event);
                    break;
                case 'scroll':
                    handleScroll(event);
                    break;
                case 'tab_switch':
                    handleTabSwitch(event);
                    break;
                case 'navigation':
                    handleNavigation(event);
                    break;
                case 'continue':
                    handleContinue(event);
                    break;
                default:
                    console.log('Unknown event type:', event.event_type);
            }
        }

        // Handle pageview event
        function handlePageview(event) {
            const url = `../index.php?page=${event.page_slug}`;
            playbackFrame.src = url;
        }

        // Handle scroll event
        function handleScroll(event) {
            if (!event.event_data || !event.event_data.path) return;
            
            // Get the scroll path
            const scrollPath = event.event_data.path;
            const frameDocument = playbackFrame.contentDocument || playbackFrame.contentWindow.document;
            
            // Animate the scroll
            let currentStep = 0;
            const totalSteps = scrollPath.length;
            
            function animateScroll() {
                if (currentStep >= totalSteps || !isPlaying) return;
                
                const position = scrollPath[currentStep].y;
                frameDocument.documentElement.scrollTop = position;
                frameDocument.body.scrollTop = position; // For older browsers
                
                currentStep++;
                setTimeout(animateScroll, 50 / playbackSpeed);
            }
            
            // Start scroll animation after frame is ready
            if (frameDocument.readyState === 'complete') {
                animateScroll();
            } else {
                playbackFrame.onload = animateScroll;
            }
        }

        // Handle tab switch event
        function handleTabSwitch(event) {
            if (!event.event_data || !event.event_data.tabId) return;
            
            const frameDocument = playbackFrame.contentDocument || playbackFrame.contentWindow.document;
            const tabId = event.event_data.tabId;
            
            // Find and click the tab button in the iframe
            playbackFrame.onload = function() {
                const tabButton = frameDocument.querySelector(`.tab-btn[data-tab="${tabId}"]`);
                if (tabButton) {
                    tabButton.click();
                }
            };
        }

        // Handle navigation event
        function handleNavigation(event) {
            if (!event.event_data || !event.event_data.url) return;
            
            // For navigation, we just redirect the iframe
            const url = event.event_data.url;
            if (url.startsWith('../') || url.startsWith('/')) {
                playbackFrame.src = url;
            } else if (url.includes('index.php')) {
                playbackFrame.src = url;
            }
        }

        // Handle continue button click
        function handleContinue(event) {
            if (!event.event_data || !event.event_data.nextPageUrl) return;
            
            const url = event.event_data.nextPageUrl;
            playbackFrame.src = url.includes('index.php') ? url : `../index.php?page=${url}`;
        }

        // Highlight event in timeline
        function highlightTimelineEvent(index) {
            // Remove highlight from all events
            const allItems = timelineContainer.querySelectorAll('.timeline-item');
            allItems.forEach(item => item.classList.remove('highlight'));
            
            // Add highlight to current event
            if (index < allItems.length) {
                allItems[index].classList.add('highlight');
            }
        }

        // Scroll timeline to event
        function scrollTimelineToEvent(index) {
            const allItems = timelineContainer.querySelectorAll('.timeline-item');
            if (index < allItems.length) {
                allItems[index].scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        }

        // Initialize when document is ready
        document.addEventListener('DOMContentLoaded', function() {
            initializePlayback();
            
            // Add click handlers to timeline items
            const timelineItems = timelineContainer.querySelectorAll('.timeline-item');
            timelineItems.forEach((item, index) => {
                item.addEventListener('click', function() {
                    isPlaying = false;
                    clearTimeout(playbackTimeout);
                    currentEventIndex = index;
                    highlightTimelineEvent(index);
                    
                    // Find the corresponding event and process it
                    if (playbackEvents[index]) {
                        processEvent(playbackEvents[index]);
                    }
                });
            });
            
            // Add event listener for iframe load
            playbackFrame.addEventListener('load', function() {
                console.log('Iframe loaded');
                
                // Add CSS to highlight elements in the iframe
                const frameDocument = playbackFrame.contentDocument || playbackFrame.contentWindow.document;
                const style = frameDocument.createElement('style');
                style.textContent = `
                    .highlight-click {
                        position: relative;
                    }
                    .highlight-click::after {
                        content: '';
                        position: absolute;
                        width: 20px;
                        height: 20px;
                        border-radius: 50%;
                        background-color: rgba(255, 0, 0, 0.5);
                        transform: translate(-50%, -50%);
                        animation: pulse 1s infinite;
                        pointer-events: none;
                    }
                    @keyframes pulse {
                        0% { transform: translate(-50%, -50%) scale(0.5); opacity: 1; }
                        100% { transform: translate(-50%, -50%) scale(1.5); opacity: 0; }
                    }
                `;
                frameDocument.head.appendChild(style);
            });
        });

        // Add CSS to highlight timeline items
    </script>
</body>
</html>
