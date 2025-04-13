<?php
/**
 * Left Tabs Plugin
 * 
 * Displays images from media/images as tabs on the left side of the screen
 */

return new class {
    private $images = [];
    
    /**
     * Constructor - automatically called when the class is instantiated
     */
    public function __invoke() {
        // Register actions
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_footer', [$this, 'render_tabs']);
        
        // Scan for images
        $this->scan_images();
    }
    
    /**
     * Enqueue CSS and JS assets
     */
    public function enqueue_assets() {
        // In a real WordPress plugin, we would use wp_enqueue_style and wp_enqueue_script
        // For our custom system, we'll output the link and script tags directly in render_tabs
    }
    
    /**
     * Scan the media/images directory for images
     */
    private function scan_images() {
        $image_dir = 'media/images';
        if (!is_dir($image_dir)) {
            return;
        }
        
        // Get all PNG files
        $files = glob($image_dir . '/*.png');
        
        // Sort files by their numeric prefix
        usort($files, function($a, $b) {
            $a_num = (int) preg_replace('/^.*?(\d+)_.*$/', '$1', basename($a));
            $b_num = (int) preg_replace('/^.*?(\d+)_.*$/', '$1', basename($b));
            return $a_num - $b_num;
        });
        
        $this->images = $files;
    }
    
    /**
     * Render the left tabs
     */
    public function render_tabs() {
        if (empty($this->images)) {
            return;
        }
        
        // Include CSS and JS
        echo '<link rel="stylesheet" href="plugins/leftTabs/style.css">';
        echo '<script src="plugins/leftTabs/script.js"></script>';
        
        // Start tabs container
        echo '<div class="left-tabs-container">';
        
        // Output each image as a tab
        foreach ($this->images as $image) {
            $image_url = $image; // Relative path
            $image_name = basename($image);
            
            echo '<div class="left-tab" title="' . htmlspecialchars($image_name) . '">';
            echo '<img src="' . htmlspecialchars($image_url) . '" alt="' . htmlspecialchars($image_name) . '">';
            echo '</div>';
        }
        
        // End tabs container
        echo '</div>';
    }
};
