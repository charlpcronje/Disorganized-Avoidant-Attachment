<?php
// /admin/example-functions.php
// Functions for managing personal examples

// Get all examples for a specific page
function getExamplesByPageId($pageId) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $logger = new Logger();
    
    try {
        $query = "SELECT * FROM examples WHERE page_id = ? ORDER BY section_id ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $pageId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $examples = [];
        while ($row = $result->fetch_assoc()) {
            $examples[$row['section_id']] = $row;
        }
        
        return $examples;
    } catch (Exception $e) {
        $logger->error("Error getting examples for page ID $pageId: " . $e->getMessage());
        return [];
    }
}

// Get a specific example by page ID and section ID
function getExample($pageId, $sectionId) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $logger = new Logger();
    
    try {
        $query = "SELECT * FROM examples WHERE page_id = ? AND section_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $pageId, $sectionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    } catch (Exception $e) {
        $logger->error("Error getting example for page ID $pageId, section ID $sectionId: " . $e->getMessage());
        return null;
    }
}

// Create or update an example
function saveExample($pageId, $sectionId, $personalExample) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $logger = new Logger();
    
    try {
        // Check if example exists
        $existingExample = getExample($pageId, $sectionId);
        
        if ($existingExample) {
            // Update existing example
            $query = "UPDATE examples SET personal_example = ? WHERE page_id = ? AND section_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sis", $personalExample, $pageId, $sectionId);
        } else {
            // Create new example
            $query = "INSERT INTO examples (page_id, section_id, personal_example) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iss", $pageId, $sectionId, $personalExample);
        }
        
        if ($stmt->execute()) {
            $logger->info("Saved example for page ID $pageId, section ID $sectionId");
            return true;
        } else {
            $logger->error("Failed to save example: " . $conn->error);
            return false;
        }
    } catch (Exception $e) {
        $logger->error("Error saving example: " . $e->getMessage());
        return false;
    }
}

// Delete an example
function deleteExample($pageId, $sectionId) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $logger = new Logger();
    
    try {
        $query = "DELETE FROM examples WHERE page_id = ? AND section_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $pageId, $sectionId);
        
        if ($stmt->execute()) {
            $logger->info("Deleted example for page ID $pageId, section ID $sectionId");
            return true;
        } else {
            $logger->error("Failed to delete example: " . $conn->error);
            return false;
        }
    } catch (Exception $e) {
        $logger->error("Error deleting example: " . $e->getMessage());
        return false;
    }
}

// Parse content file to find example sections
function parseContentFile($filePath) {
    $logger = new Logger();
    
    try {
        if (!file_exists($filePath)) {
            $logger->error("Content file not found: $filePath");
            return [];
        }
        
        $content = file_get_contents($filePath);
        $sections = [];
        
        // Match example container divs with their IDs
        preg_match_all('/<div class="example-container" id="([^"]+)"/', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $sectionId = $match[1];
            
            // Find the example title for this section
            $titlePattern = '/<div class="example-container" id="' . preg_quote($sectionId, '/') . '".*?<h3 class="example-title">(.*?)<\/h3>/s';
            preg_match($titlePattern, $content, $titleMatch);
            
            $title = isset($titleMatch[1]) ? trim($titleMatch[1]) : $sectionId;
            
            $sections[] = [
                'id' => $sectionId,
                'title' => $title
            ];
        }
        
        return $sections;
    } catch (Exception $e) {
        $logger->error("Error parsing content file: " . $e->getMessage());
        return [];
    }
}

// Get all example sections from a page with their current values
function getPageExamples($pageSlug) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $logger = new Logger();
    
    try {
        // Get page information
        $query = "SELECT * FROM pages WHERE slug = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $pageSlug);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $logger->error("Page not found: $pageSlug");
            return null;
        }
        
        $page = $result->fetch_assoc();
        $contentFile = '../pages/' . $page['slug'] . '.php';
        
        // Parse the content file to find example sections
        $sections = parseContentFile($contentFile);
        
        // Get existing examples from database
        $examples = getExamplesByPageId($page['id']);
        
        // Merge section info with example content
        foreach ($sections as &$section) {
            $sectionId = $section['id'];
            $section['content'] = isset($examples[$sectionId]) ? $examples[$sectionId]['personal_example'] : '';
        }
        
        return [
            'page' => $page,
            'sections' => $sections
        ];
    } catch (Exception $e) {
        $logger->error("Error getting page examples: " . $e->getMessage());
        return null;
    }
}