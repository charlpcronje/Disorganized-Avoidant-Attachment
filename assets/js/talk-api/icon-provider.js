/**
 * Icon Provider for TalkAPI
 * Provides SVG icons for the UI
 */

export class IconProvider {
    /**
     * Get speaker icon SVG
     */
    getSpeakerIcon() {
        return '<svg class="talk-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFFFFF"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>';
    }
    
    /**
     * Get play icon SVG
     */
    getPlayIcon() {
        return '<svg class="talk-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFFFFF"><path d="M8 5v14l11-7z"/></svg>';
    }
    
    /**
     * Get pause icon SVG
     */
    getPauseIcon() {
        return '<svg class="talk-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFFFFF"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>';
    }
    
    /**
     * Get loading icon SVG
     */
    getLoadingIcon() {
        return '<svg class="talk-icon talk-loading" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFFFFF"><path d="M12 4V2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2v2c4.42 0 8 3.58 8 8s-3.58 8-8 8-8-3.58-8-8 3.58-8 8-8z"/></svg>';
    }
    
    /**
     * Get error icon SVG
     */
    getErrorIcon() {
        return '<svg class="talk-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFFFFF"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>';
    }
}
