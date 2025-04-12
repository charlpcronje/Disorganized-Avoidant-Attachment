# Combined Markdown Export

Generated: 2025-04-12T23:05:29.620588


## Index

- `audio-manager.js` — ~239 tokens
- `highlight-manager.js` — ~691 tokens
- `icon-provider.js` — ~232 tokens
- `index.js` — ~122 tokens
- `talk-api.js` — ~1834 tokens
- `ui-manager.js` — ~395 tokens

**Total tokens: ~3513**

---

### `audio-manager.js`

```js
/**
 * Audio Manager for TalkAPI
 * Handles audio-related functionality
 */

export class AudioManager {
    constructor(config) {
        this.config = config;
    }
    
    /**
     * Get audio URL for the given text and voice
     */
    async getAudioUrl(text, voice, audioCache) {
        // Check cache first
        const cacheKey = `${voice}_${text}`;
        if (audioCache[cacheKey]) {
            return audioCache[cacheKey];
        }
        
        // Prepare request data
        const data = {
            text: text,
            voice: voice,
            domain: this.config.domain
        };
        
        // Make API request
        const response = await fetch(this.config.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            throw new Error(`API error: ${response.status}`);
        }
        
        const result = await response.json();
        
        // Make sure we use the full URL from the API, not a relative path
        let audioUrl = result.audio_url;
        
        // If the URL doesn't start with http/https, it's a relative URL
        // In that case, make sure we use the talk.api domain, not the current domain
        if (!audioUrl.startsWith('http')) {
            audioUrl = 'https://talk.api.webally.co.za' + (audioUrl.startsWith('/') ? '' : '/') + audioUrl;
            console.log('Converted relative URL to absolute URL:', audioUrl);
        }
        
        // Cache the URL
        audioCache[cacheKey] = audioUrl;
        
        return audioUrl;
    }
}
```

### `highlight-manager.js`

```js
/**
 * Highlight Manager for TalkAPI
 * Handles text highlighting functionality
 */

export class HighlightManager {
    constructor() {
        this.highlightedElements = [];
    }

    /**
     * Start sentence highlighting
     */
    startSentenceHighlighting(text, elements, duration, currentAudio) {
        // Split text into sentences
        const sentences = this.splitIntoSentences(text);
        if (sentences.length === 0) return;

        // Count links in the text for later announcement
        const linkCount = this.countLinks(elements);

        // Calculate time per sentence
        const timePerSentence = duration / sentences.length;

        // Highlight sentences one by one
        sentences.forEach((sentence, index) => {
            setTimeout(() => {
                if (!currentAudio || currentAudio.paused) return;
                this.highlightSentence(elements, sentence);

                // If this is the last sentence and we have links, prepare to announce them
                if (index === sentences.length - 1 && linkCount > 0) {
                    // Store link count for announcement after audio ends
                    currentAudio.dataset.linkCount = linkCount;
                }
            }, index * timePerSentence * 1000);
        });
    }

    /**
     * Count links in elements
     */
    countLinks(elements) {
        let linkCount = 0;

        elements.forEach(element => {
            const links = element.querySelectorAll('a');
            linkCount += links.length;
        });

        console.log(`Found ${linkCount} links in the text`);
        return linkCount;
    }

    /**
     * Split text into sentences
     */
    splitIntoSentences(text) {
        // Simple sentence splitting - can be improved
        return text.match(/[^.!?]+[.!?]+/g) || [text];
    }

    /**
     * Highlight a sentence within elements
     */
    highlightSentence(elements, sentence) {
        // Clear previous highlights
        this.clearHighlights();

        console.log('%c HIGHLIGHT: Highlighting sentence', 'background: #9c27b0; color: white; padding: 3px; border-radius: 3px;');
        console.log('Sentence to highlight:', sentence);

        // Find the element containing this sentence
        for (const element of elements) {
            if (element.textContent.includes(sentence)) {
                console.log('Found element containing sentence:', element);

                // Store original HTML
                if (!element.dataset.originalHtml) {
                    element.dataset.originalHtml = element.innerHTML;
                }

                try {
                    // Instead of replacing the entire innerHTML, we'll use DOM traversal
                    // to preserve HTML structure and only highlight the text nodes
                    this.highlightTextNodesWithSentence(element, sentence);

                    console.log('Successfully highlighted sentence');

                    // Add to highlighted elements
                    this.highlightedElements.push(element);
                    break;
                } catch (error) {
                    console.error('Error highlighting sentence:', error);
                }
            }
        }
    }

    /**
     * Improved highlighting that works with child elements
     */
    highlightTextNodesWithSentence(element, sentence) {
        try {
            // Clear previous highlights within this element
            const existingHighlights = element.querySelectorAll('.talk-highlight-sentence');
            existingHighlights.forEach(highlight => {
                highlight.classList.remove('talk-highlight-sentence');
            });

            // Store original HTML if not already stored
            if (!element.dataset.originalHtml) {
                element.dataset.originalHtml = element.innerHTML;
            }

            // Find child elements that might contain the sentence
            const childElements = element.children;
            let foundMatch = false;

            // First try to find exact matches in child elements
            for (let i = 0; i < childElements.length; i++) {
                const child = childElements[i];
                if (child.textContent.includes(sentence)) {
                    child.classList.add('talk-highlight-sentence');
                    foundMatch = true;
                    break;
                }
            }

            // If no child element contains the full sentence, highlight the parent
            if (!foundMatch) {
                element.classList.add('talk-highlight-container');
            }

            console.log('Applied improved highlighting');
        } catch (error) {
            console.error('Error in highlighting:', error);
        }
    }

    /**
     * Get all text nodes in an element
     */
    getTextNodes(element) {
        const textNodes = [];
        const walker = document.createTreeWalker(
            element,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );

        let node;
        while (node = walker.nextNode()) {
            if (node.nodeValue.trim() !== '') {
                textNodes.push(node);
            }
        }

        return textNodes;
    }

    /**
     * Clear all highlights
     */
    clearHighlights() {
        // Remove highlight container classes
        const highlightContainers = document.querySelectorAll('.talk-highlight-container');
        highlightContainers.forEach(container => {
            container.classList.remove('talk-highlight-container');
        });

        // Remove sentence highlights
        const sentenceHighlights = document.querySelectorAll('.talk-highlight-sentence');
        sentenceHighlights.forEach(highlight => {
            highlight.classList.remove('talk-highlight-sentence');
        });

        // Clear the tracked elements
        this.highlightedElements = [];
    }
}
```

### `icon-provider.js`

```js
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
```

### `index.js`

```js
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
```

### `talk-api.js`

```js
/**
 * Main TalkAPI class
 */

import { UIManager } from './ui-manager.js';
import { AudioManager } from './audio-manager.js';
import { HighlightManager } from './highlight-manager.js';
import { IconProvider } from './icon-provider.js';

export class TalkAPI {
    constructor(options = {}) {
        // Default configuration
        this.config = {
            apiEndpoint: 'https://talk.api.webally.co.za/speak',
            defaultVoice: 'echo',
            exampleVoice: 'alloy',
            domain: window.location.hostname,
            ...options
        };

        console.log('Using API endpoint:', this.config.apiEndpoint);

        // Initialize managers
        this.uiManager = new UIManager();
        this.audioManager = new AudioManager(this.config);
        this.highlightManager = new HighlightManager();
        this.iconProvider = new IconProvider();

        // Cache for audio URLs
        this.audioCache = {};

        // Track current playback state
        this.currentAudio = null;
        this.currentButton = null;
        this.progressInterval = null;

        // Initialize
        this.init();
    }

    /**
     * Initialize the TalkAPI
     */
    init() {
        // Add CSS styles
        this.uiManager.addStyles();

        // Set up content sections
        this.setupContentSections();

        // Make the API available globally
        window.talkAPI = this;
    }

    /**
     * Set up content sections with play buttons
     */
    setupContentSections() {
        // Find all talk tags
        const talkElements = document.querySelectorAll('talk, .talk');
        console.log(`Found ${talkElements.length} <talk> tags in the document`);

        // Process each talk tag
        talkElements.forEach((talkElement, index) => {
            // Get the voice attribute or use default
            const voice = talkElement.getAttribute('voice') || this.config.defaultVoice;
            console.log(`Talk tag ${index+1} using voice: ${voice}`);

            // Create a section for this talk element
            this.createTalkSection(talkElement, `talk-${index}`, voice);
        });
    }

    /**
     * Create a section for a talk element
     */
    createTalkSection(talkElement, id, voice) {
        // Create section wrapper
        const section = document.createElement('div');
        section.className = 'talk-section';
        section.id = `talk-section-${id}`;

        // Create play button with speaker icon
        const button = this.createButton(id, voice);

        // Create a content container for the talk content
        const contentContainer = document.createElement('div');
        contentContainer.className = 'talk-content';
        contentContainer.innerHTML = talkElement.innerHTML;

        // Add progress bar
        const progressBar = this.uiManager.createProgressBar();

        // Add elements to section in correct order
        section.appendChild(progressBar);
        section.appendChild(contentContainer);
        section.appendChild(button);

        // Replace the talk element with our section
        talkElement.parentNode.replaceChild(section, talkElement);
    }

    /**
     * Create a section wrapper with a play button for a group of elements
     */
    createSectionWithButton(elements, id, voice) {
        if (elements.length === 0) return;

        const firstElement = elements[0];
        const parent = firstElement.parentNode;

        // Create section wrapper
        const section = document.createElement('div');
        section.className = 'talk-section';
        section.id = `talk-section-${id}`;

        // Create play button with speaker icon
        const button = this.createButton(id, voice);

        // Add progress bar
        const progressBar = this.uiManager.createProgressBar();
        section.appendChild(progressBar);

        // Create a content container
        const contentContainer = document.createElement('div');
        contentContainer.className = 'talk-content';

        // Move elements into the content container
        elements.forEach(el => {
            const clone = el.cloneNode(true);
            contentContainer.appendChild(clone);
        });

        // Add content container to section
        section.appendChild(contentContainer);

        // Add button to section
        section.appendChild(button);

        // Insert section before first element
        parent.insertBefore(section, firstElement);

        // Remove original elements
        elements.forEach(el => {
            parent.removeChild(el);
        });
    }

    /**
     * Create a play button for tab content
     */
    createTabButton(tabContent, id, voice) {
        // Create button
        const button = this.createButton(id, voice);

        // Position the button within the tab content
        tabContent.style.position = 'relative';
        tabContent.appendChild(button);
    }

    /**
     * Create a play button with proper event handling
     */
    createButton(id, voice) {
        // Create button element
        const button = document.createElement('button');
        button.className = 'talk-button';
        button.setAttribute('data-id', id);
        button.setAttribute('data-voice', voice);
        button.setAttribute('title', 'Listen to this section');

        // Set initial icon only
        button.innerHTML = this.iconProvider.getSpeakerIcon();

        // Add click event
        button.addEventListener('click', () => this.handleButtonClick(button));

        return button;
    }

    /**
     * Handle button click - play, pause, or resume audio
     */
    handleButtonClick(button) {
        console.log('Button clicked:', button.getAttribute('data-id'));

        // If this is the current button, toggle play/pause
        if (this.currentButton === button && this.currentAudio) {
            if (this.currentAudio.paused) {
                // Resume playback
                console.log('%c PLAY: Resuming audio playback', 'background: #2e7d32; color: white; padding: 3px; border-radius: 3px;');
                this.currentAudio.play();
                button.innerHTML = this.iconProvider.getPauseIcon();
                console.log('BUTTON STATE: Playing - Button shows PAUSE icon - Color remains #2e7d32 (dark green)');
            } else {
                // Pause playback
                console.log('%c PAUSE: Pausing audio playback', 'background: #ff9800; color: white; padding: 3px; border-radius: 3px;');
                this.currentAudio.pause();
                button.innerHTML = this.iconProvider.getPlayIcon();
                console.log('BUTTON STATE: Paused - Button shows PLAY icon - Color remains #2e7d32 (dark green)');
            }
            return;
        }

        // Stop any currently playing audio
        this.stopCurrentAudio();

        // Get section elements
        const id = button.getAttribute('data-id');
        const voice = button.getAttribute('data-voice');
        const section = document.getElementById(`talk-section-${id}`);
        const elements = section ? Array.from(section.children).filter(el => el !== button) : [];

        if (elements.length === 0 && button.parentNode.classList.contains('tab-content')) {
            // For tab content, use the parent as the element
            elements.push(button.parentNode);
        }

        // Update button state
        button.innerHTML = this.iconProvider.getLoadingIcon();
        button.disabled = true;
        button.style.backgroundColor = '#2e7d32';
        console.log('BUTTON STATE: Loading - Color set to #2e7d32 (dark green)');
        this.currentButton = button;

        // Get text from elements
        const text = this.getTextFromElements(elements);

        // Play the audio
        this.playAudio(text, voice, button);
    }

    /**
     * Stop currently playing audio
     */
    stopCurrentAudio() {
        if (this.currentAudio) {
            console.log('Stopping current audio');
            this.currentAudio.pause();
            this.currentAudio = null;
        }

        if (this.currentButton) {
            this.currentButton.innerHTML = this.iconProvider.getSpeakerIcon();
            this.currentButton.disabled = false;
            this.currentButton = null;
        }

        // Clear any highlights
        this.highlightManager.clearHighlights();

        // Remove active class from all sections
        document.querySelectorAll('.talk-section.active').forEach(section => {
            section.classList.remove('active');
        });
    }

    /**
     * Play audio for the given text
     */
    async playAudio(text, voice, button) {
        try {
            // Get audio URL
            const audioUrl = await this.audioManager.getAudioUrl(text, voice, this.audioCache);

            // Create audio element
            const audio = new Audio(audioUrl);
            this.currentAudio = audio;

            // Set up audio events
            audio.oncanplay = () => {
                console.log('%c READY: Audio loaded and ready to play', 'background: #2196f3; color: white; padding: 3px; border-radius: 3px;');
                button.disabled = false;
                button.innerHTML = this.iconProvider.getPauseIcon();
                console.log('BUTTON STATE: Ready - Button shows PAUSE icon - Color is #2e7d32 (dark green)');

                // Highlight the section
                const id = button.getAttribute('data-id');
                const section = document.getElementById(`talk-section-${id}`);
                if (section) {
                    section.classList.add('active');
                }

                // Set up progress tracking
                this.startProgressTracking(audio, section);
            };

            audio.onended = () => {
                console.log('Audio playback ended');

                // Check if we need to announce links
                if (audio.dataset.linkCount && parseInt(audio.dataset.linkCount) > 0) {
                    const linkCount = parseInt(audio.dataset.linkCount);
                    console.log(`Announcing ${linkCount} links`);

                    // Announce links after a short delay
                    setTimeout(() => {
                        this.announceLinks(linkCount, voice);
                    }, 500);
                } else {
                    // If no links to announce, reset UI immediately
                    this.resetUIAfterPlayback(button);
                }
            };

            // Store a reference to the reset function for use in the link announcement
            audio.resetUI = () => this.resetUIAfterPlayback(button);

            audio.onerror = () => {
                console.error('Audio playback error');
                button.innerHTML = this.iconProvider.getErrorIcon();
                setTimeout(() => {
                    button.innerHTML = this.iconProvider.getSpeakerIcon();
                    button.disabled = false;
                }, 2000);
                this.currentAudio = null;
                this.currentButton = null;
                // Reset progress bar
                this.resetProgressBar(section);
            };

            // Play the audio
            audio.play();

        } catch (error) {
            console.error('Error playing audio:', error);
            button.innerHTML = this.iconProvider.getErrorIcon();
            setTimeout(() => {
                button.innerHTML = this.iconProvider.getSpeakerIcon();
                button.disabled = false;
            }, 2000);
            this.currentAudio = null;
            this.currentButton = null;
        }
    }

    /**
     * Get text from elements
     */
    getTextFromElements(elements) {
        // Create a temporary div to work with
        const tempDiv = document.createElement('div');

        // Clone each element and append to temp div
        elements.forEach(el => {
            const clone = el.cloneNode(true);
            tempDiv.appendChild(clone);
        });

        // Remove all <sup> elements from the temporary div
        const supElements = tempDiv.querySelectorAll('sup');
        supElements.forEach(sup => sup.remove());

        // Get text content and clean it
        return tempDiv.textContent.trim().replace(/\s+/g, ' ');
    }

    /**
     * Reset UI after playback
     */
    resetUIAfterPlayback(button) {
        button.innerHTML = this.iconProvider.getSpeakerIcon();
        button.disabled = false;
        this.currentAudio = null;
        this.currentButton = null;

        // Remove active class from section
        const id = button.getAttribute('data-id');
        const section = document.getElementById(`talk-section-${id}`);
        if (section) {
            section.classList.remove('active');
            this.resetProgressBar(section);
        }
    }

    /**
     * Start tracking progress for audio playback
     */
    startProgressTracking(audio, section) {
        if (!audio || !section) return;

        // Clear any existing interval
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
        }

        // Update progress every 100ms
        this.progressInterval = setInterval(() => {
            if (audio.paused || audio.ended) {
                if (audio.ended) {
                    // Set to 100% when ended
                    this.uiManager.updateProgressBar(section, 1);
                }
                return;
            }

            // Calculate progress (0-1)
            const progress = audio.currentTime / audio.duration;

            // Update the progress bar
            this.uiManager.updateProgressBar(section, progress);
        }, 100);

        // Clear interval when audio ends
        audio.addEventListener('ended', () => {
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
                this.progressInterval = null;
            }
        });
    }

    /**
     * Reset progress bar
     */
    resetProgressBar(section) {
        if (!section) return;

        // Clear any existing interval
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
            this.progressInterval = null;
        }

        // Reset progress to 0
        this.uiManager.updateProgressBar(section, 0);
    }

    /**
     * Announce links found in the text
     */
    async announceLinks(linkCount, voice) {
        try {
            // Create announcement text
            const announcementText = `There ${linkCount === 1 ? 'was' : 'were'} ${linkCount} link${linkCount === 1 ? '' : 's'} in the text that was just read to you.`;

            console.log('Announcing links:', announcementText);

            // Get audio URL for announcement
            const audioUrl = await this.audioManager.getAudioUrl(announcementText, voice, this.audioCache);

            // Create and play audio
            const audio = new Audio(audioUrl);

            // When announcement ends, reset UI
            audio.onended = () => {
                if (this.currentAudio && this.currentAudio.resetUI) {
                    this.currentAudio.resetUI();
                }
            };

            // Play announcement
            audio.play();
        } catch (error) {
            console.error('Error announcing links:', error);

            // Reset UI even if announcement fails
            if (this.currentAudio && this.currentAudio.resetUI) {
                this.currentAudio.resetUI();
            }
        }
    }
}
```

### `ui-manager.js`

```js
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
                top: 0;
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
```
