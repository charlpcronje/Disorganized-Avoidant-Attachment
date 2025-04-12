// /assets/js/examples.js
// Manages the example tabs functionality

class ExamplesManager {
    constructor() {
        this.exampleContainers = document.querySelectorAll('.example-container');
        this.init();
    }
    
    init() {
        this.exampleContainers.forEach(container => {
            this.setupTabs(container);
        });
    }
    
    setupTabs(container) {
        const tabsWrapper = container.querySelector('.tabs-wrapper');
        if (!tabsWrapper) return;
        
        const researchTab = tabsWrapper.querySelector('[data-tab="research"]');
        const personalTab = tabsWrapper.querySelector('[data-tab="personal"]');
        const researchContent = container.querySelector('.tab-content[data-tab="research"]');
        const personalContent = container.querySelector('.tab-content[data-tab="personal"]');
        
        if (!researchTab || !personalTab || !researchContent || !personalContent) return;
        
        // Set research tab as active by default
        this.activateTab(container, 'research');
        
        // Add click handlers
        researchTab.addEventListener('click', (e) => {
            e.preventDefault();
            this.activateTab(container, 'research');
        });
        
        personalTab.addEventListener('click', (e) => {
            e.preventDefault();
            this.activateTab(container, 'personal');
        });
    }
    
    activateTab(container, tabId) {
        // Update tab buttons
        const buttons = container.querySelectorAll('.tab-btn');
        buttons.forEach(button => {
            if (button.dataset.tab === tabId) {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }
        });
        
        // Update tab content
        const contents = container.querySelectorAll('.tab-content');
        contents.forEach(content => {
            if (content.dataset.tab === tabId) {
                content.classList.add('active');
            } else {
                content.classList.remove('active');
            }
        });
        
        // Track this tab switch in analytics
        if (window.siteAnalytics) {
            // Analytics tracking is handled by the Analytics class
        }
    }
}

// Initialize examples when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.examplesManager = new ExamplesManager();
    
    // Add smooth scrolling to example links
    document.querySelectorAll('a[href^="#example-"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Highlight the example briefly
                targetElement.classList.add('highlight');
                setTimeout(() => {
                    targetElement.classList.remove('highlight');
                }, 2000);
            }
        });
    });
});