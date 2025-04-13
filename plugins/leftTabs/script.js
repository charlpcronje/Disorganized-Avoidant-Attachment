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

    // Get current page number from URL or body data attribute
    let currentPageNum = 1;

    // Try to get page number from URL
    const urlParams = new URLSearchParams(window.location.search);
    const pageParam = urlParams.get('page');
    if (pageParam && !isNaN(parseInt(pageParam))) {
        currentPageNum = parseInt(pageParam);
    }

    // Try to get page number from body data attribute as fallback
    if (document.body.dataset.pageNum) {
        currentPageNum = parseInt(document.body.dataset.pageNum);
    }

    // Add click event to each tab and highlight current page tab
    tabs.forEach((tab, index) => {
        // Create close button for each tab
        const closeBtn = document.createElement('div');
        closeBtn.className = 'left-tab-close';
        closeBtn.innerHTML = '×';
        tab.appendChild(closeBtn);

        // Check if this tab corresponds to the current page
        const tabNum = parseInt(tab.dataset.pageNum || (index + 1));
        if (tabNum === currentPageNum) {
            tab.classList.add('current-page');
        }

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
