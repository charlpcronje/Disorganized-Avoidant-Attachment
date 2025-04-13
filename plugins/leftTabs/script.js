/**
 * Left Tabs Plugin JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {
    // Create overlay element
    const overlay = document.createElement('div');
    overlay.className = 'left-tabs-overlay';
    document.body.appendChild(overlay);
    
    // Get all tabs
    const tabs = document.querySelectorAll('.left-tab');
    
    // Add click event to each tab
    tabs.forEach(tab => {
        // Create close button for each tab
        const closeBtn = document.createElement('div');
        closeBtn.className = 'left-tab-close';
        closeBtn.innerHTML = '×';
        tab.appendChild(closeBtn);
        
        // Tab click handler
        tab.addEventListener('click', function(e) {
            // If close button was clicked, close the tab
            if (e.target === closeBtn) {
                closeTab();
                e.stopPropagation();
                return;
            }
            
            // If this tab is already active, do nothing
            if (tab.classList.contains('active')) {
                return;
            }
            
            // Close any open tab
            closeTab();
            
            // Open this tab
            tab.classList.add('active');
            overlay.classList.add('active');
        });
    });
    
    // Overlay click handler to close active tab
    overlay.addEventListener('click', closeTab);
    
    // Close tab function
    function closeTab() {
        const activeTab = document.querySelector('.left-tab.active');
        if (activeTab) {
            activeTab.classList.remove('active');
        }
        overlay.classList.remove('active');
    }
    
    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeTab();
        }
    });
});
