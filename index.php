<?php
// /index.php
// Main entry point for the Disorganized Attachment site

// Include required files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/plugin-loader.php';


// Handle name entry (ALWAYS require a valid visitor name before any page logic)
if (!isset($_SESSION['visitor_name']) || empty($_SESSION['visitor_name'])) {
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['visitor_name'], $_POST['password'])) {
        $input_name = strtolower(trim($_POST['visitor_name']));
        $input_password = trim($_POST['password']);
        if (in_array($input_name, ['charl', 'nade'])) {
            if ($input_password === 'nade1234') {
                $_SESSION['visitor_name'] = ucfirst($input_name);
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            } else {
                $error = 'Incorrect password.';
            }
        } else {
            $error = 'Name not recognized.';
        }
    }
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Private Access – Enter Name & Password</title>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<style>
        body { font-family: "Segoe UI", Arial, sans-serif; background: #181a20; margin: 0; padding: 0; display: flex; align-items: center; justify-content: center; height: 100vh; color: #f2f2f2; }
        form { background: #23262f; padding: 2.5em 2.8em; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.4); min-width: 350px; }
        h1 { margin-top: 0; font-size: 1.7em; color: #fff; }
        p.desc { color: #b0b8c1; margin-bottom: 1.5em; font-size: 1.09em; }
        label { font-size: 1.17em; color: #f2f2f2; }
        input[type=text], input[type=password] { font-size: 1.1em; padding: 0.6em; margin-top: 0.6em; margin-bottom: 1.3em; width: 100%; border-radius: 5px; border: 1px solid #444; background: #22242b; color: #f2f2f2; transition: border 0.2s; }
        input[type=text]:focus, input[type=password]:focus { border: 1.5px solid #5e81ff; outline: none; }
        button { padding: 0.7em 2.3em; font-size: 1.08em; border: none; border-radius: 5px; background: linear-gradient(90deg, #5e81ff 0%, #3c60e7 100%); color: #fff; cursor: pointer; font-weight: 600; letter-spacing: 0.02em; transition: background 0.2s; }
        button:hover { background: linear-gradient(90deg, #3c60e7 0%, #5e81ff 100%); }
        p.error { color: #ff4e4e; margin-top: 0.5em; }
        .why { font-size: 0.97em; color: #7c8591; margin-top: 2em; }
    </style>';
    echo '</head><body><form method="post">';
    echo '<h1>Private Website Access</h1>';
    echo '<p class="desc">The website is private. Please enter your name and password to proceed.</p>';
    echo '<label for="visitor_name">Enter your name:</label><br>';
    echo '<input type="text" id="visitor_name" name="visitor_name" autocomplete="off" required><br>';
    echo '<label for="password">Password:</label><br>';
    echo '<input type="password" id="password" name="password" autocomplete="off" required><br>';
    if ($error) echo '<p class="error">' . htmlspecialchars($error) . '</p>';
    echo '<button type="submit">Continue</button>';
    echo '<div class="why">Why is this happening?<br>This website is private and will only grant one person access. Since the name and password are only known to the intended recipients, this is more than enough security without requiring anyone to remember complex passwords.</div>';
    echo '</form></body></html>';
    exit;
}
$visitorName = $_SESSION['visitor_name'];

// Initialize logLoggerger (Keep as is, assuming it works or isn't critical for page loading)
$logger = new ();

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