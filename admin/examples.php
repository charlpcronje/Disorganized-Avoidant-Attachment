<?php
// /admin/examples.php
// Admin interface for managing personal examples

// Start session
session_start();

// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'admin-functions.php';
require_once 'example-functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page
    header('Location: login.php');
    exit;
}

// Initialize logger
$logger = new Logger();

// Initialize messages
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_example'])) {
    $pageId = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
    $sectionId = isset($_POST['section_id']) ? $_POST['section_id'] : '';
    $personalExample = isset($_POST['personal_example']) ? $_POST['personal_example'] : '';
    
    if ($pageId > 0 && !empty($sectionId)) {
        if (saveExample($pageId, $sectionId, $personalExample)) {
            $success = "Example saved successfully!";
        } else {
            $error = "Failed to save example. Please try again.";
        }
    } else {
        $error = "Invalid page or section ID.";
    }
}

// Get current page slug from URL
$pageSlug = isset($_GET['page']) ? $_GET['page'] : '';

// Get all pages for navigation
$db = Database::getInstance();
$conn = $db->getConnection();
$allPages = getAllPages();

// Get current page examples
$pageExamples = !empty($pageSlug) ? getPageExamples($pageSlug) : null;

// Get section to edit
$editSectionId = isset($_GET['edit']) ? $_GET['edit'] : '';
$editSection = null;

if ($pageExamples && $editSectionId) {
    foreach ($pageExamples['sections'] as $section) {
        if ($section['id'] === $editSectionId) {
            $editSection = $section;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Examples - <?php echo SITE_NAME; ?></title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../assets/css/normalize.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    
    <!-- SimpleMDE Markdown Editor -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css">
    <script src="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js"></script>
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="container">
            <div class="header-content">
                <h1 class="site-title">Manage Personal Examples</h1>
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
            <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <div class="examples-container">
                <div class="page-selector">
                    <h2>Select a Page</h2>
                    <div class="page-grid">
                        <?php foreach ($allPages as $page): ?>
                        <a href="examples.php?page=<?php echo $page['slug']; ?>" class="page-card <?php echo ($pageSlug === $page['slug']) ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($page['title']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php if ($pageExamples): ?>
                <div class="page-examples">
                    <div class="section-header">
                        <h2>Examples for: <?php echo htmlspecialchars($pageExamples['page']['title']); ?></h2>
                        <?php if ($editSection): ?>
                        <a href="examples.php?page=<?php echo $pageSlug; ?>" class="btn">Back to Examples</a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($editSection): ?>
                    <!-- Edit Example Form -->
                    <div class="edit-example">
                        <h3>Edit: <?php echo htmlspecialchars($editSection['title']); ?></h3>
                        
                        <form action="examples.php?page=<?php echo $pageSlug; ?>" method="post">
                            <input type="hidden" name="page_id" value="<?php echo $pageExamples['page']['id']; ?>">
                            <input type="hidden" name="section_id" value="<?php echo $editSection['id']; ?>">
                            
                            <div class="form-group">
                                <label for="personal_example">Personal Example (Markdown)</label>
                                <textarea id="personal_example" name="personal_example" rows="10"><?php echo htmlspecialchars($editSection['content']); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" name="save_example" class="btn btn-primary">Save Example</button>
                                <a href="examples.php?page=<?php echo $pageSlug; ?>" class="btn">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <?php else: ?>
                    <!-- List Examples -->
                    <?php if (empty($pageExamples['sections'])): ?>
                    <div class="no-examples">
                        <p>No example sections found for this page.</p>
                    </div>
                    <?php else: ?>
                    <div class="examples-list">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Section</th>
                                    <th>Content Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pageExamples['sections'] as $section): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($section['title']); ?></td>
                                    <td>
                                        <?php if (empty($section['content'])): ?>
                                        <span class="status-empty">No content</span>
                                        <?php else: ?>
                                        <span class="status-has-content">Has content</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="examples.php?page=<?php echo $pageSlug; ?>&edit=<?php echo $section['id']; ?>" class="action-btn">Edit</a>
                                        <a href="../index.php?page=<?php echo $pageSlug; ?>#<?php echo $section['id']; ?>" class="action-btn" target="_blank">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="select-prompt">
                    <h3>Please select a page from the list above.</h3>
                    <p>You can manage personal examples for each section of the selected page.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="admin-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Admin Dashboard</p>
        </div>
    </footer>
    
    <?php if ($editSection): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var simplemde = new SimpleMDE({ 
                element: document.getElementById("personal_example"),
                spellChecker: false,
                autofocus: true,
                placeholder: "Enter your personal example content here...",
                toolbar: ["bold", "italic", "heading", "|", "quote", "unordered-list", "ordered-list", "|", "link", "image", "|", "preview", "guide"]
            });
        });
    </script>
    <?php endif; ?>
    
    <style>
        /* Additional styles for examples management */
        .page-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .page-card {
            display: block;
            background-color: var(--background-alt);
            padding: 15px;
            border-radius: var(--border-radius);
            text-align: center;
            color: var(--text);
            text-decoration: none;
            border: 1px solid var(--border);
            transition: all 0.2s ease;
        }
        
        .page-card:hover {
            background-color: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .page-card.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .select-prompt {
            text-align: center;
            padding: 40px 0;
            background-color: var(--background-alt);
            border-radius: var(--border-radius);
            margin-top: 20px;
        }
        
        .edit-example {
            background-color: white;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .editor-toolbar {
            border-top-left-radius: var(--border-radius);
            border-top-right-radius: var(--border-radius);
        }
        
        .CodeMirror {
            border-bottom-left-radius: var(--border-radius);
            border-bottom-right-radius: var(--border-radius);
            height: 300px;
        }
        
        .status-empty {
            color: var(--danger);
            font-weight: bold;
        }
        
        .status-has-content {
            color: var(--success);
            font-weight: bold;
        }
        
        .alert {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</body>
</html>