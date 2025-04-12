/**
 * TTS API Client - Version 2.1.0
 * Main entry point for the TalkAPI
 */

import { TalkAPI } from './talk-api.js';

// Initialize the TalkAPI when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('%c TalkAPI v2.1.0 loaded - MODULAR VERSION', 'background: #2e7d32; color: white; padding: 8px; border-radius: 4px; font-weight: bold;');
    new TalkAPI();
});

// Add CSS for loading animation
const loadingStyle = document.createElement('style');
loadingStyle.textContent = `
    .talk-loading {
        animation: talk-spin 1s linear infinite;
    }
    
    @keyframes talk-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;
document.head.appendChild(loadingStyle);
