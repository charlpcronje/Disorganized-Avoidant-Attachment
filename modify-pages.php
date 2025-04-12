<?php
// /modify-pages.php
// This script will:
// 1. Create the examples table if it doesn't exist
// 2. Extract personal examples from all pages and save them to the database
// 3. Replace personal examples in all page files with a placeholder
// 4. Modify index.php to load examples from the database

// Include required files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Initialize logger
$logger = new Logger();

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Output header
echo "<html><head><title>Modify Pages</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
    h1, h2, h3 { color: #4a6fa5; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    .code { font-family: monospace; background: #f5f5f5; padding: 2px 4px; }
</style>";
echo "</head><body>";
echo "<h1>Modifying Pages to Use Database for Personal Examples</h1>";

// STEP 1: Create the examples table
echo "<h2>Step 1: Creating Database Table</h2>";
$tableExists = false;
try {
    $result = $conn->query("SHOW TABLES LIKE 'examples'");
    $tableExists = $result->num_rows > 0;
    
    if (!$tableExists) {
        $createTableSQL = "
        CREATE TABLE examples (
            id INT AUTO_INCREMENT PRIMARY KEY,
            page_id INT NOT NULL,
            section_id VARCHAR(100) NOT NULL,
            personal_example TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
        )";
        
        if ($conn->query($createTableSQL)) {
            echo "<p class='success'>Created examples table successfully.</p>";
            $logger->info("Created examples table successfully");
        } else {
            echo "<p class='error'>Error creating examples table: " . $conn->error . "</p>";
            $logger->error("Error creating examples table: " . $conn->error);
        }
    } else {
        echo "<p class='warning'>Examples table already exists.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error checking/creating table: " . $e->getMessage() . "</p>";
    $logger->error("Error checking/creating table: " . $e->getMessage());
}

// STEP 2: Process all pages
echo "<h2>Step 2: Processing Pages</h2>";

// Get all pages from database
$pagesQuery = "SELECT id, slug, title FROM pages ORDER BY order_num";
$result = $conn->query($pagesQuery);

if (!$result) {
    echo "<p class='error'>Error fetching pages: " . $conn->error . "</p>";
    $logger->error("Error fetching pages: " . $conn->error);
    exit;
}

$pages = [];
while ($row = $result->fetch_assoc()) {
    $pages[] = $row;
}

// Process each page
foreach ($pages as $page) {
    $pageId = $page['id'];
    $pageSlug = $page['slug'];
    $pageTitle = $page['title'];
    
    echo "<h3>Processing page: {$pageTitle} (ID: {$pageId}, Slug: {$pageSlug})</h3>";
    
    $filePath = "pages/{$pageSlug}.php";
    
    if (!file_exists($filePath)) {
        echo "<p class='error'>File not found: {$filePath}</p>";
        $logger->error("File not found: {$filePath}");
        continue;
    }
    
    // Read file content
    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "<p class='error'>Could not read file: {$filePath}</p>";
        $logger->error("Could not read file: {$filePath}");
        continue;
    }
    
    // Find all example containers and extract section IDs
    preg_match_all('/<div class="example-container" id="([^"]+)"/', $content, $matches);
    $sectionIds = $matches[1];
    
    if (empty($sectionIds)) {
        echo "<p class='warning'>No example sections found in this page.</p>";
        continue;
    }
    
    echo "<p>Found " . count($sectionIds) . " example sections.</p>";
    
    // Process each section
    $modifiedContent = $content;
    $totalExamples = 0;
    
    foreach ($sectionIds as $sectionId) {
        // Extract personal example content
        $pattern = '/<div class="example-container" id="' . preg_quote($sectionId, '/') . '".*?' .
                  '<div class="tab-content" data-tab="personal".*?>(.*?)<\/div>/s';
        
        if (preg_match($pattern, $content, $matches)) {
            $personalContent = trim($matches[1]);
            
            // Skip if already a placeholder
            if (strpos($personalContent, '[Personal example content') !== false) {
                echo "<p>Section {$sectionId} already contains a placeholder.</p>";
                continue;
            }
            
            // Insert into database
            try {
                // Check if example already exists
                $checkStmt = $conn->prepare("SELECT id FROM examples WHERE page_id = ? AND section_id = ?");
                $checkStmt->bind_param("is", $pageId, $sectionId);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    // Update existing
                    $updateStmt = $conn->prepare("UPDATE examples SET personal_example = ? WHERE page_id = ? AND section_id = ?");
                    $updateStmt->bind_param("sis", $personalContent, $pageId, $sectionId);
                    
                    if ($updateStmt->execute()) {
                        echo "<p class='success'>Updated example for section {$sectionId} in database.</p>";
                        $logger->info("Updated example for page {$pageId}, section {$sectionId}");
                        $totalExamples++;
                    } else {
                        echo "<p class='error'>Error updating example: " . $conn->error . "</p>";
                        $logger->error("Error updating example: " . $conn->error);
                    }
                } else {
                    // Insert new
                    $insertStmt = $conn->prepare("INSERT INTO examples (page_id, section_id, personal_example) VALUES (?, ?, ?)");
                    $insertStmt->bind_param("iss", $pageId, $sectionId, $personalContent);
                    
                    if ($insertStmt->execute()) {
                        echo "<p class='success'>Inserted example for section {$sectionId} into database.</p>";
                        $logger->info("Inserted example for page {$pageId}, section {$sectionId}");
                        $totalExamples++;
                    } else {
                        echo "<p class='error'>Error inserting example: " . $conn->error . "</p>";
                        $logger->error("Error inserting example: " . $conn->error);
                    }
                }
            } catch (Exception $e) {
                echo "<p class='error'>Database error for section {$sectionId}: " . $e->getMessage() . "</p>";
                $logger->error("Database error for section {$sectionId}: " . $e->getMessage());
            }
            
            // Replace with placeholder in content
            $replacement = '<div class="tab-content" data-tab="personal" style="display: none;">' . PHP_EOL . 
                          '            <?php echo isset($personalExamples["' . $sectionId . '"]) ? $personalExamples["' . $sectionId . '"] : "[Personal example content loaded from database]"; ?>' . PHP_EOL . 
                          '        </div>';
            
            $modifiedContent = str_replace($matches[0], 
                str_replace($matches[1], $replacement, $matches[0]), 
                $modifiedContent);
        }
    }
    
    // Write modified content back to file if changes were made
    if ($modifiedContent !== $content) {
        // Create backup
        $backupPath = $filePath . '.bak';
        if (!file_exists($backupPath)) {
            if (copy($filePath, $backupPath)) {
                echo "<p class='success'>Created backup: {$backupPath}</p>";
            } else {
                echo "<p class='error'>Failed to create backup: {$backupPath}</p>";
            }
        }
        
        // Write modified content
        if (file_put_contents($filePath, $modifiedContent)) {
            echo "<p class='success'>Updated file with placeholders: {$filePath}</p>";
            $logger->info("Updated file with placeholders: {$filePath}");
        } else {
            echo "<p class='error'>Failed to write to file: {$filePath}</p>";
            $logger->error("Failed to write to file: {$filePath}");
        }
    } else {
        echo "<p class='warning'>No changes needed for: {$filePath}</p>";
    }
    
    echo "<p>Processed {$totalExamples} examples for this page.</p>";
}

// STEP 3: Update index.php to load examples
echo "<h2>Step 3: Updating index.php</h2>";

$indexPath = "index.php";
$indexBackupPath = "index.php.bak";

// Create backup of index.php if not exists
if (!file_exists($indexBackupPath)) {
    if (copy($indexPath, $indexBackupPath)) {
        echo "<p class='success'>Created backup of index.php</p>";
    } else {
        echo "<p class='error'>Failed to create backup of index.php</p>";
    }
}

$indexContent = file_get_contents($indexPath);
if ($indexContent === false) {
    echo "<p class='error'>Could not read index.php</p>";
    $logger->error("Could not read index.php");
} else {
    // Check if examples loading code already exists
    if (strpos($indexContent, '$personalExamples') !== false) {
        echo "<p class='warning'>Example loading code already exists in index.php</p>";
    } else {
        // Find the position to insert code (right after getting next/prev pages)
        $insertPos = strpos($indexContent, '// Get all pages for navigation');
        
        if ($insertPos !== false) {
            // Code to load personal examples
            $examplesCode = <<<'EOT'

// Load personal examples for the current page
$personalExamples = [];
if (isset($pageInfo['id'])) {
    $examplesQuery = "SELECT section_id, personal_example FROM examples WHERE page_id = ?";
    $stmt = $conn->prepare($examplesQuery);
    $stmt->bind_param("i", $pageInfo['id']);
    $stmt->execute();
    $examplesResult = $stmt->get_result();
    
    while ($example = $examplesResult->fetch_assoc()) {
        $personalExamples[$example['section_id']] = $example['personal_example'];
    }
}

EOT;
            
            // Insert the code
            $updatedIndexContent = substr($indexContent, 0, $insertPos) . $examplesCode . substr($indexContent, $insertPos);
            
            // Write the updated content
            if (file_put_contents($indexPath, $updatedIndexContent)) {
                echo "<p class='success'>Updated index.php to load personal examples</p>";
                $logger->info("Updated index.php to load personal examples");
            } else {
                echo "<p class='error'>Failed to write to index.php</p>";
                $logger->error("Failed to write to index.php");
            }
        } else {
            echo "<p class='error'>Could not find insertion point in index.php</p>";
            $logger->error("Could not find insertion point in index.php");
        }
    }
}

// STEP 4: Final instructions
echo "<h2>Step 4: Final Steps</h2>";
echo "<p>The script has completed all tasks. Here's what was done:</p>";
echo "<ol>";
echo "<li>Created the examples table in the database (if it didn't exist)</li>";
echo "<li>Extracted all personal examples from the page files and saved them to the database</li>";
echo "<li>Replaced the personal examples in the page files with PHP placeholders</li>";
echo "<li>Modified index.php to load personal examples for each page</li>";
echo "</ol>";

echo "<p>To complete the implementation:</p>";
echo "<ol>";
echo "<li>If there were any errors above, address them manually</li>";
echo "<li>Test the website to ensure everything is working properly</li>";
echo "<li>The original files have been backed up with a .bak extension if you need to revert</li>";
echo "</ol>";

echo "<h3>Example of Modified Page Structure:</h3>";
echo "<pre>&lt;div class=\"example-container\" id=\"example-section-id\"&gt;
    &lt;div class=\"example-header\"&gt;
        &lt;h3 class=\"example-title\"&gt;Example Title&lt;/h3&gt;
    &lt;/div&gt;
    
    &lt;div class=\"tabs-wrapper\"&gt;
        &lt;button class=\"tab-btn active\" data-tab=\"research\"&gt;Research Example&lt;/button&gt;
        &lt;button class=\"tab-btn\" data-tab=\"personal\"&gt;Personal Example&lt;/button&gt;
    &lt;/div&gt;
    
    &lt;div class=\"tab-content\" data-tab=\"research\" style=\"display: block;\"&gt;
        Research content...
    &lt;/div&gt;
    
    &lt;div class=\"tab-content\" data-tab=\"personal\" style=\"display: none;\"&gt;
        &lt;?php echo isset(\$personalExamples[\"example-section-id\"]) ? \$personalExamples[\"example-section-id\"] : \"[Personal example content loaded from database]\"; ?&gt;
    &lt;/div&gt;
&lt;/div&gt;</pre>";

echo "<p>You can now manage the personal examples through your admin interface.</p>";

echo "</body></html>";