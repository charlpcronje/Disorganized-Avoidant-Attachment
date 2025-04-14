// /assets/js/examples.js
// Manages example-related functionality (not tabs - those are handled by tabs.js)

class ExamplesManager {
    constructor() {
        this.exampleContainers = document.querySelectorAll('.example-container');
        this.init();
    }

    init() {
        // Tab functionality is now handled by tabs.js
        // This file now only handles other example-related functionality
        this.setupExampleHighlighting();
    }

    setupExampleHighlighting() {
        // Add highlighting functionality for examples
        this.exampleContainers.forEach(container => {
            // Check if this example is targeted by URL hash
            if (window.location.hash && window.location.hash === `#${container.id}`) {
                // Highlight the example briefly
                container.classList.add('highlight');
                setTimeout(() => {
                    container.classList.remove('highlight');
                }, 2000);

                // Scroll to it
                container.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
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

    // Copy Link Button Functionality
    const copyButtons = document.querySelectorAll('.copy-link-btn');
    copyButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const targetId = this.getAttribute('data-target');
            const sectionElement = document.getElementById(targetId);
            if (sectionElement) {
                const url = window.location.href.split('#')[0] + '#' + targetId;
                navigator.clipboard.writeText(url).then(() => {
                    // Provide user feedback
                    const originalText = this.textContent;
                    this.textContent = 'Link Copied!';
                    setTimeout(() => {
                        this.textContent = originalText;
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy link: ', err);
                    // Error feedback
                    const originalText = this.textContent;
                    this.textContent = 'Copy Failed';
                    setTimeout(() => {
                        this.textContent = originalText;
                    }, 2000);
                });
            } else {
                // Handle case where target element doesn't exist
                const originalText = this.textContent;
                this.textContent = 'Target not found!';
                setTimeout(() => {
                    this.textContent = originalText;
                }, 2000);
            }
        });
    });
});