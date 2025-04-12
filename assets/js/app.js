// /assets/js/app.js
// Main application logic for the Disorganized Attachment site

class App {
    constructor() {
        this.initializeFontLoading();
        this.setupScrollToLinks();
        this.setupExpandableText();
        this.setupCopyLinks();
    }
    
    // Load fonts asynchronously
    initializeFontLoading() {
        // Font loading with Web Font Loader
        if (typeof WebFont !== 'undefined') {
            WebFont.load({
                google: {
                    families: ['Open Sans:400,600', 'Montserrat:600,700']
                }
            });
        }
    }
    
    // Setup smooth scrolling for anchor links
    setupScrollToLinks() {
        document.querySelectorAll('a[href^="#"]:not(.tab-btn)').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                
                // Skip if it's a tab button or empty link
                if (targetId === '#' || this.classList.contains('tab-btn')) {
                    return;
                }
                
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    e.preventDefault();
                    
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    // Update URL without reload
                    history.pushState(null, null, targetId);
                }
            });
        });
    }
    
    // Setup expandable text sections
    setupExpandableText() {
        document.querySelectorAll('.expandable-trigger').forEach(trigger => {
            trigger.addEventListener('click', function() {
                const target = document.querySelector(this.dataset.target);
                if (!target) return;
                
                // Toggle expanded state
                const isExpanded = target.classList.contains('expanded');
                
                if (isExpanded) {
                    target.classList.remove('expanded');
                    this.textContent = this.dataset.expandText || 'Read More';
                    
                    // Scroll back to trigger if it's now out of view
                    if (this.getBoundingClientRect().top < 0) {
                        this.scrollIntoView({ behavior: 'smooth' });
                    }
                } else {
                    target.classList.add('expanded');
                    this.textContent = this.dataset.collapseText || 'Read Less';
                }
            });
        });
    }
    
    // Setup copy link buttons
    setupCopyLinks() {
        document.querySelectorAll('.copy-link-btn').forEach(button => {
            button.addEventListener('click', function() {
                const url = window.location.href.split('#')[0] + '#' + this.dataset.target;
                
                // Create a temporary input to copy the URL
                const tempInput = document.createElement('input');
                tempInput.value = url;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                
                // Show success message
                const originalText = this.textContent;
                this.textContent = 'Copied!';
                this.classList.add('copied');
                
                // Reset button after 2 seconds
                setTimeout(() => {
                    this.textContent = originalText;
                    this.classList.remove('copied');
                }, 2000);
            });
        });
    }
}

// Utility function to format dates
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Utility function to generate a unique ID
function generateUniqueId() {
    return 'id-' + Math.random().toString(36).substr(2, 9);
}

// Utility function to debounce function calls
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
}

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.app = new App();
    
    // Check if there's a hash in the URL and scroll to it
    if (window.location.hash) {
        const targetElement = document.querySelector(window.location.hash);
        if (targetElement) {
            setTimeout(() => {
                targetElement.scrollIntoView({ behavior: 'smooth' });
            }, 500); // Small delay to ensure page is fully loaded
        }
    }
});