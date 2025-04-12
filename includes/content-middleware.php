<?php
// /includes/content-middleware.php
// Handle injecting personal examples into page content

class ContentMiddleware {
    private $db;
    private $logger;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }
    
    /**
     * Get examples for a page
     * 
     * @param int $pageId The page ID
     * @return array Examples indexed by section ID
     */
    private function getExamplesForPage($pageId) {
        try {
            $conn = $this->db->getConnection();
            
            $query = "SELECT section_id, personal_example FROM examples WHERE page_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $pageId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $examples = [];
            while ($row = $result->fetch_assoc()) {
                $examples[$row['section_id']] = $row['personal_example'];
            }
            
            return $examples;
        } catch (Exception $e) {
            $this->logger->error("Error getting examples for page $pageId: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Process page content to inject personal examples
     * 
     * @param string $content The page content
     * @param int $pageId The page ID
     * @return string The processed content
     */
    public function processContent($content, $pageId) {
        // Get examples for this page
        $examples = $this->getExamplesForPage($pageId);
        
        if (empty($examples)) {
            return $content;
        }
        
        // Replace each example tab content
        foreach ($examples as $sectionId => $example) {
            // Pattern to match the personal tab content
            $pattern = '/<div class="tab-content" data-tab="personal".*?>(.*?)<\/div>/s';
            
            // Find and replace each occurrence of personal tab in sections
            $content = preg_replace_callback(
                '/<div class="example-container" id="' . preg_quote($sectionId, '/') . '".*?(' . $pattern . ')/s',
                function($matches) use ($example) {
                    // Replace the content inside the personal tab
                    return str_replace(
                        $matches[1], 
                        '<div class="tab-content" data-tab="personal">' . PHP_EOL . $example . PHP_EOL . '</div>', 
                        $matches[0]
                    );
                },
                $content
            );
        }
        
        return $content;
    }
}