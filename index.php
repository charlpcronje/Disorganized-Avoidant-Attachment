<?php
// /index.php
// Main entry point for the Disorganized Attachment site

// Include required files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/plugin-loader.php';


// Handle name entry
if (!isset($_SESSION['visitor_name'])) {
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['visitor_name'])) {
        $input_name = strtolower(trim($_POST['visitor_name']));
        if (in_array($input_name, ['charl', 'nade'])) {
            $_SESSION['visitor_name'] = ucfirst($input_name);
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $error = 'Name not recognized.';
        }
    }
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Enter Name</title>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<style>body{font-family:sans-serif;background:#f6f6f6;margin:0;padding:0;display:flex;align-items:center;justify-content:center;height:100vh;}form{background:#fff;padding:2em 2.5em;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}label{font-size:1.2em;}input[type=text]{font-size:1.1em;padding:0.5em;margin-top:0.5em;margin-bottom:1em;width:100%;border-radius:4px;border:1px solid #ccc;}button{padding:0.6em 2em;font-size:1em;border:none;border-radius:4px;background:#2e7d32;color:#fff;cursor:pointer;}button:hover{background:#256027;}p.error{color:#b71c1c;}</style>';
    echo '</head><body><form method="post"><label for="visitor_name">Enter your name:</label><br><input type="text" id="visitor_name" name="visitor_name" autocomplete="off" required><br>';
    if ($error) echo '<p class="error">' . htmlspecialchars($error) . '</p>';
    echo '<button type="submit">Continue</button></form></body></html>';
    exit;
}
$visitorName = $_SESSION['visitor_name'];

// Initialize logger (Keep as is, assuming it works or isn't critical for page loading)
$logger = new Logger();

// Get session ID for analytics (Keep as is)
$sessionId = getSession();

// --- START MINIMAL CHANGES ---

// 1. Determine the requested page slug, default to 'introduction'
$requestedSlug = $_GET['page'] ?? 'introduction';

// 2. Get page info for the *requested* page
$pageInfo = getPageBySlug($requestedSlug);

// 3. Set defaults ONLY if the requested page wasn't found
if (!$pageInfo) {
    // Fallback to introduction if requested page is invalid
    $requestedSlug = 'introduction';
    $pageInfo = getPageBySlug($requestedSlug);

    // If even introduction fails, something is very wrong (handle minimally)
    if (!$pageInfo) {
        die("Error: Could not load default page information.");
        // Or redirect: header("Location: error.php"); exit;
    }
}

// 4. Set variables based on the *found* page info
$currentPage = $pageInfo['slug'];
$pageTitle = SITE_NAME . ' - ' . htmlspecialchars($pageInfo['title']); // Basic security
$pageContent = 'pages/' . $pageInfo['slug'] . '.php';

// 5. Check if the content file exists (basic check to prevent include errors)
if (!file_exists($pageContent)) {
    // If content file missing, show introduction instead (simplest fallback)
    $currentPage = 'introduction';
    $pageInfo = getPageBySlug($currentPage); // Re-fetch intro info
    $pageTitle = SITE_NAME . ' - Introduction';
    $pageContent = 'pages/introduction.php';
    // Note: This might show the wrong title if introduction fails above, but minimal change asked.
}

// 6. Get next/prev based on the *actual* current page's order
$nextPage = getNextPage($pageInfo['order_num']);
$prevPage = getPreviousPage($pageInfo['order_num']);

// --- END MINIMAL CHANGES ---

// Get all pages for navigation (Keep as is)
$allPages = getAllPages();

// Record page view (logic adjusted slightly by variable changes above)
if ($pageInfo && $sessionId) {
    recordPageView($pageInfo['id'], $sessionId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; // Uses the dynamically set title ?></title>

    <link rel="icon" type="image/png" href="favicon.png">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/normalize.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/nav-update.css">
    <link rel="stylesheet" href="assets/css/talk.css">
    <!-- Google Fonts (preconnect) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Web Font Loader for async font loading -->
    <script src="https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js"></script>

    <!-- JavaScript variables for analytics -->
    <script>
        window.BASE_URL = '<?php echo BASE_URL; ?>';
        window.SYNC_INTERVAL = <?php echo SYNC_INTERVAL; ?>;
        window.SCROLL_DEBOUNCE = <?php echo SCROLL_DEBOUNCE; ?>;
        window.VISITOR_NAME = '<?php echo htmlspecialchars($visitorName); ?>';
    </script>
</head>
<!-- Uses the dynamically set page slug and ID -->
<body data-page="<?php echo htmlspecialchars($currentPage); ?>" data-page-id="<?php echo $pageInfo['id'] ?? ''; ?>" data-session-id="<?php echo htmlspecialchars($sessionId); ?>">
    <!-- Header -->
    <header class="site-header">
        <div class="container">
             <!-- Link logo to introduction page explicitly -->
            <a href="index.php?page=introduction" class="site-logo">
                <span>Understanding what happened and why?</span>
            </a>

            <button class="nav-toggle">
                <span>≡</span>
            </button>

            <!-- Main Navigation -->
            <nav class="main-nav">
                <ul class="nav-list">
                    <?php foreach ($allPages as $page): ?>
                    <li class="nav-item">
                         <!-- Add 'active' class for current page styling -->
                        <a href="index.php?page=<?php echo htmlspecialchars($page['slug']); ?>"
                           class="nav-link <?php echo ($page['slug'] === $currentPage) ? 'active' : ''; ?>"
                           data-page="<?php echo htmlspecialchars($page['slug']); ?>">
                            <?php echo htmlspecialchars($page['title']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Sub Navigation -->
    <div class="sub-nav">
        <div class="container">
            <ul class="nav-list">
                <li class="nav-item">
                     <!-- Link Home to introduction page, add 'active' class -->
                    <a href="index.php?page=introduction" class="nav-link <?php echo ($currentPage === 'introduction') ? 'active' : ''; ?>">
                        Home
                    </a>
                </li>

                <li class="nav-item">
                    <a href="#" class="nav-link last-visited-btn hidden">
                        Last Visited
                    </a>
                </li>

                <li class="nav-item">
                     <!-- Use dynamically determined $prevPage -->
                    <a href="<?php echo $prevPage ? 'index.php?page=' . htmlspecialchars($prevPage['slug']) : '#'; ?>"
                       class="nav-link <?php echo !$prevPage ? 'disabled' : ''; ?>"
                       <?php echo !$prevPage ? 'aria-disabled="true" tabindex="-1"' : ''; ?>>
                        ← Prev
                    </a>
                </li>

                <li class="nav-item">
                     <!-- Use dynamically determined $nextPage -->
                    <a href="<?php echo $nextPage ? 'index.php?page=' . htmlspecialchars($nextPage['slug']) : '#'; ?>"
                       class="nav-link <?php echo !$nextPage ? 'disabled' : ''; ?>"
                       <?php echo !$nextPage ? 'aria-disabled="true" tabindex="-1"' : ''; ?>>
                        Next →
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Page Content -->
    <main>
        <?php
        // Include the dynamically determined content file
        // Basic check already done above, so include should be safer
        if (isset($pageContent) && file_exists($pageContent)) {
             include $pageContent;
        } else {
             // Minimal fallback message if something went wrong after checks
             echo "<div class='container'><p>Error loading page content.</p></div>";
        }
        ?>

        <?php if ($nextPage && isset($_GET['page'])): ?>
        <div class="container text-center">
             <!-- Use dynamically determined $nextPage -->
            <a href="index.php?page=<?php echo htmlspecialchars($nextPage['slug']); ?>" class="btn continue-btn">
                Continue to <?php echo htmlspecialchars($nextPage['title']); ?> →
            </a>
        </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h4>Navigation</h4>
                    <ul class="footer-links">
                        <?php foreach (array_slice($allPages, 0, 5) as $page): ?>
                        <li>
                            <a href="index.php?page=<?php echo htmlspecialchars($page['slug']); ?>">
                                <?php echo htmlspecialchars($page['title']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="footer-column">
                    <h4>About</h4>
                    <p>This site is designed to help understand disorganized avoidant attachment style, breaking down research into digestible sections with real-world examples.</p>
                </div>

                <div class="footer-column">
                    <h4>Resources</h4>
                    <ul class="footer-links">
                        <li><a href="index.php?page=resources">External Resources</a></li>
                        <li><a href="index.php?page=resources#references">References</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>© <?php echo date('Y'); ?> Understanding Disorganized Attachment. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="assets/js/app.js"></script>
    <script src="assets/js/navigation.js"></script>
    <script src="assets/js/tabs.js"></script>
    <script src="assets/js/examples.js"></script>
    <script src="assets/js/analytics.js"></script>
    <script type="module" src="assets/js/talk.api.js"></script>

    <?php do_action('wp_enqueue_scripts'); ?>
    <?php do_action('wp_footer'); ?>
</body>
</html>