// /assets/js/navigation.js
// Handles site navigation and progress tracking

class Navigation {
    constructor() {
        this.mainNav = document.querySelector('.main-nav');
        this.subNav = document.querySelector('.sub-nav');
        this.currentPage = document.body.dataset.page || '';
        this.lastVisitedKey = 'last_visited_section';
        
        this.init();
    }
    
    init() {
        this.highlightCurrentPage();
        this.saveLastVisited();
        this.setupNavToggle();
        this.setupOutsideClickHandler();
        this.setupNavLinkClicks();
    }
    
    // Highlight current page in navigation
    highlightCurrentPage() {
        if (!this.currentPage) return;
        
        // Highlight main navigation
        if (this.mainNav) {
            const activeMainLink = this.mainNav.querySelector(`[data-page="${this.currentPage}"]`);
            if (activeMainLink) {
                activeMainLink.classList.add('active');
                
                // If it's in a dropdown, also highlight parent
                const parentDropdown = activeMainLink.closest('.nav-dropdown');
                if (parentDropdown) {
                    parentDropdown.classList.add('active');
                }
            }
        }
        
        // Highlight sub navigation
        if (this.subNav) {
            const activeSubLink = this.subNav.querySelector(`[data-page="${this.currentPage}"]`);
            if (activeSubLink) {
                activeSubLink.classList.add('active');
            }
        }
    }
    
    // Save the current page as last visited
    saveLastVisited() {
        if (this.currentPage) {
            localStorage.setItem(this.lastVisitedKey, this.currentPage);
        }
    }
    
    // Setup mobile navigation toggle
    setupNavToggle() {
        const navToggle = document.querySelector('.nav-toggle');
        if (navToggle) {
            navToggle.addEventListener('click', () => {
                this.mainNav.classList.toggle('open');
                navToggle.classList.toggle('open');
            });
        }
    }
    
    // Close navigation when clicking outside (for mobile)
    setupOutsideClickHandler() {
        document.addEventListener('click', (event) => {
            if (window.innerWidth <= 768 && 
                this.mainNav && 
                this.mainNav.classList.contains('open') && 
                !event.target.closest('.main-nav') && 
                !event.target.closest('.nav-toggle')) {
                
                this.mainNav.classList.remove('open');
                const navToggle = document.querySelector('.nav-toggle');
                if (navToggle) {
                    navToggle.classList.remove('open');
                }
            }
        });
    }
    
    // Close navigation when clicking a link (for mobile)
    setupNavLinkClicks() {
        if (!this.mainNav) return;
        
        const navLinks = this.mainNav.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    this.mainNav.classList.remove('open');
                    const navToggle = document.querySelector('.nav-toggle');
                    if (navToggle) {
                        navToggle.classList.remove('open');
                    }
                }
            });
        });
    }
    
    // Get last visited page (static method for use outside class)
    static getLastVisited() {
        return localStorage.getItem('last_visited_section') || '';
    }
}

// Setup tab navigation for examples
class ExampleTabs {
    constructor() {
        this.tabButtons = document.querySelectorAll('.tab-btn');
        this.tabContents = document.querySelectorAll('.tab-content');
        
        this.init();
    }
    
    init() {
        if (this.tabButtons.length === 0) return;
        
        // Add click handlers to tab buttons
        this.tabButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.activateTab(button.dataset.tab);
            });
        });
        
        // Activate first tab by default
        this.activateTab(this.tabButtons[0].dataset.tab);
    }
    
    // Activate a specific tab
    activateTab(tabId) {
        // Update active state on buttons
        this.tabButtons.forEach(button => {
            if (button.dataset.tab === tabId) {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }
        });
        
        // Show/hide tab content
        this.tabContents.forEach(content => {
            if (content.dataset.tab === tabId) {
                content.classList.add('active');
            } else {
                content.classList.remove('active');
            }
        });
    }
}

// Initialize navigation when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.siteNavigation = new Navigation();
    
    // Initialize example tabs if they exist on the page
    if (document.querySelector('.tab-btn')) {
        window.exampleTabs = new ExampleTabs();
    }
    
    // Setup "continue" buttons to save progress
    const continueButtons = document.querySelectorAll('.continue-btn');
    continueButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Additional logic for continue button if needed
            // Currently handled by Analytics class
        });
    });
    
    // Setup "last visited" button
    const lastVisitedBtn = document.querySelector('.last-visited-btn');
    if (lastVisitedBtn) {
        const lastPage = Navigation.getLastVisited();
        if (lastPage) {
            lastVisitedBtn.href = `index.php?page=${lastPage}`;
            lastVisitedBtn.classList.remove('hidden');
        }
    }
});