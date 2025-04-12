/**
 * UI Manager for TalkAPI
 * Handles UI-related functionality
 */

export class UIManager {
    /**
     * Add required CSS styles
     */
    addStyles() {
        const style = document.createElement('style');
        style.textContent = `
            /* Button styles */
            .talk-button {
                position: absolute;
                top: 5px;
                right: 5px;
                padding: 8px 12px;
                background-color: #2e7d32 !important;
                color: #FFFFFF !important;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                z-index: 100;
                box-shadow: 0 3px 5px rgba(0,0,0,0.3);
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .talk-button:hover {
                background-color: #1b5e20 !important;
            }

            .talk-button:disabled {
                background-color: #2e7d32 !important;
                opacity: 0.7;
                cursor: wait;
            }

            /* Section wrapper styles */
            .talk-section {
                position: relative;
                border: 1px solid rgba(200, 200, 200, 0.3);
                border-radius: 5px;
                padding: 10px 10px 10px 20px; /* Added left padding for progress bar */
                margin-bottom: 15px;
                transition: border 0.3s ease;
            }

            .talk-section.active {
                border: 1px solid rgba(76, 175, 80, 0.7);
            }

            /* Progress bar container */
            .talk-progress-container {
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 10px;
                background-color: rgba(200, 200, 200, 0.3);
                border-top-left-radius: 5px;
                border-bottom-left-radius: 5px;
                overflow: hidden;
                z-index: 10;
            }

            /* Progress bar */
            .talk-progress-bar {
                position: absolute;
                left: 0;
                bottom: 0;
                width: 100%;
                background-color: #5a84c3;
                height: 0%;
                transition: height 0.1s linear;
            }

            /* Content container */
            .talk-content {
                position: relative;
                width: 100%;
                padding-right: 40px; /* Space for button */
            }

            /* Icons */
            .talk-icon {
                width: 18px;
                height: 18px;
                display: inline-block;
                filter: drop-shadow(0 2px 2px rgba(0,0,0,0.5));
                margin-right: 0px;
                color: #FFFFFF !important;
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Create progress bar element
     */
    createProgressBar() {
        const container = document.createElement('div');
        container.className = 'talk-progress-container';

        const bar = document.createElement('div');
        bar.className = 'talk-progress-bar';

        container.appendChild(bar);
        return container;
    }

    /**
     * Update progress bar
     */
    updateProgressBar(section, progress) {
        if (!section) return;

        const progressBar = section.querySelector('.talk-progress-bar');
        if (progressBar) {
            // Set the height as a percentage (0-100%)
            progressBar.style.height = `${Math.min(100, Math.max(0, progress * 100))}%`;
        }
    }
}
