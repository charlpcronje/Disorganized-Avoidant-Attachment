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
                padding: 10px;
                margin-bottom: 15px;
                transition: border 0.3s ease;
            }
            
            .talk-section.active {
                border: 1px solid rgba(76, 175, 80, 0.7);
            }
            
            /* Sentence highlight */
            .talk-highlight {
                background-color: rgba(76, 175, 80, 0.3);
                border-radius: 3px;
                padding: 2px 0;
                display: inline;
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
}
