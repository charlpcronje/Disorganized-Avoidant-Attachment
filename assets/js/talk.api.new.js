/**
 * TTS API Client - Version 2.0.0
 *
 * This client provides an interface to the TTS microservice
 * It allows for easy text-to-speech conversion using OpenAI's voices.
 */

class TalkAPI {
    constructor(options = {}) {
        console.log('%c TalkAPI v2.0.0 loaded - COMPLETE REWRITE', 'background: #2e7d32; color: white; padding: 8px; border-radius: 4px; font-weight: bold;');

        // Default configuration
        this.config = {
            apiEndpoint: 'https://talk.api.webally.co.za/speak',  // Use the correct endpoint from original file
            defaultVoice: 'echo',
            exampleVoice: 'alloy',
            domain: window.location.hostname,
            ...options
        };

        console.log('Using API endpoint:', this.config.apiEndpoint);

        // Cache for audio URLs
        this.audioCache = {};

        // Track current playback state
        this.currentAudio = null;
        this.currentButton = null;
        this.highlightedElements = [];

        // Initialize
        this.init();
    }

    /**
     * Initialize the TalkAPI
     */
    init() {
        // Add CSS styles
        this.addStyles();

        // Set up content sections
        this.setupContentSections();

        // Set up example sections
        this.setupExampleSections();

        // Make the API available globally
        window.talkAPI = this;
    }

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

    /**
     * Set up content sections with play buttons
     */
    setupContentSections() {
        // Find all content sections
        const contentSections = document.querySelectorAll('.page-content > p, .page-content > h2, .page-content > h3, .page-content > ul, .page-content > ol');

        // Group consecutive elements
        let currentGroup = [];
        let groupCount = 0;

        contentSections.forEach((section, index) => {
            // Skip if inside an example container or tab content
            if (section.closest('.example-container') || section.closest('.tab-content')) {
                return;
            }

            // Add to current group
            currentGroup.push(section);

            // Create a group when we reach 3 elements or it's the last element
            if (currentGroup.length >= 3 || index === contentSections.length - 1) {
                this.createSectionWithButton(currentGroup, `content-${groupCount}`, this.config.defaultVoice);
                currentGroup = [];
                groupCount++;
            }
        });
    }

    /**
     * Set up example sections with play buttons
     */
    setupExampleSections() {
        // Find all example tabs
        document.querySelectorAll('.tab-content').forEach((tabContent, index) => {
            const tabId = tabContent.getAttribute('data-tab');

            // Get all paragraphs within the tab content
            const paragraphs = tabContent.querySelectorAll('p');
            if (paragraphs.length > 0) {
                // Create a play button for tab content
                this.createTabButton(tabContent, `example-${tabId}-${index}`, this.config.exampleVoice);
            }
        });
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

        // Move elements into the section
        elements.forEach(el => {
            const clone = el.cloneNode(true);
            section.appendChild(clone);
        });

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
        button.innerHTML = this.getSpeakerIcon();

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
                button.innerHTML = this.getPauseIcon();
                console.log('BUTTON STATE: Playing - Button shows PAUSE icon - Color remains #2e7d32 (dark green)');
            } else {
                // Pause playback
                console.log('%c PAUSE: Pausing audio playback', 'background: #ff9800; color: white; padding: 3px; border-radius: 3px;');
                this.currentAudio.pause();
                button.innerHTML = this.getPlayIcon();
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
        button.innerHTML = this.getLoadingIcon();
        button.disabled = true;
        button.style.backgroundColor = '#2e7d32';
        console.log('BUTTON STATE: Loading - Color set to #2e7d32 (dark green)');
        this.currentButton = button;

        // Get text from elements
        const text = this.getTextFromElements(elements);

        // Play the audio
        this.playAudio(text, voice, button, elements);
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
            this.currentButton.innerHTML = this.getSpeakerIcon();
            this.currentButton.disabled = false;
            this.currentButton = null;
        }

        // Clear any highlights
        this.clearHighlights();

        // Remove active class from all sections
        document.querySelectorAll('.talk-section.active').forEach(section => {
            section.classList.remove('active');
        });
    }

    /**
     * Play audio for the given text
     */
    async playAudio(text, voice, button, elements) {
        try {
            // Get audio URL
            const audioUrl = await this.getAudioUrl(text, voice);

            // Create audio element
            const audio = new Audio(audioUrl);
            this.currentAudio = audio;

            // Set up audio events
            audio.oncanplay = () => {
                console.log('%c READY: Audio loaded and ready to play', 'background: #2196f3; color: white; padding: 3px; border-radius: 3px;');
                button.disabled = false;
                button.innerHTML = this.getPauseIcon();
                console.log('BUTTON STATE: Ready - Button shows PAUSE icon - Color is #2e7d32 (dark green)');

                // Highlight the section
                const id = button.getAttribute('data-id');
                const section = document.getElementById(`talk-section-${id}`);
                if (section) {
                    section.classList.add('active');
                }

                // Start sentence highlighting
                this.startSentenceHighlighting(text, elements, audio.duration);
            };

            audio.onended = () => {
                console.log('Audio playback ended');
                button.innerHTML = this.getSpeakerIcon();
                button.disabled = false;
                this.currentAudio = null;
                this.currentButton = null;

                // Clear highlights
                this.clearHighlights();

                // Remove active class from section
                const id = button.getAttribute('data-id');
                const section = document.getElementById(`talk-section-${id}`);
                if (section) {
                    section.classList.remove('active');
                }
            };

            audio.onerror = () => {
                console.error('Audio playback error');
                button.innerHTML = this.getErrorIcon();
                setTimeout(() => {
                    button.innerHTML = this.getSpeakerIcon();
                    button.disabled = false;
                }, 2000);
                this.currentAudio = null;
                this.currentButton = null;
                this.clearHighlights();
            };

            // Play the audio
            audio.play();

        } catch (error) {
            console.error('Error playing audio:', error);
            button.innerHTML = this.getErrorIcon();
            setTimeout(() => {
                button.innerHTML = this.getSpeakerIcon();
                button.disabled = false;
            }, 2000);
            this.currentAudio = null;
            this.currentButton = null;
        }
    }

    /**
     * Get audio URL for the given text and voice
     */
    async getAudioUrl(text, voice) {
        // Check cache first
        const cacheKey = `${voice}_${text}`;
        if (this.audioCache[cacheKey]) {
            return this.audioCache[cacheKey];
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

        // Cache the URL
        this.audioCache[cacheKey] = result.audio_url;

        return result.audio_url;
    }

    /**
     * Get text from elements
     */
    getTextFromElements(elements) {
        return elements.map(el => el.textContent.trim()).join(' ');
    }

    /**
     * Start sentence highlighting
     */
    startSentenceHighlighting(text, elements, duration) {
        // Split text into sentences
        const sentences = this.splitIntoSentences(text);
        if (sentences.length === 0) return;

        // Calculate time per sentence
        const timePerSentence = duration / sentences.length;

        // Highlight sentences one by one
        sentences.forEach((sentence, index) => {
            setTimeout(() => {
                if (!this.currentAudio || this.currentAudio.paused) return;
                this.highlightSentence(elements, sentence);
            }, index * timePerSentence * 1000);
        });
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
                    // Escape special characters in the sentence
                    const escapedSentence = sentence.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    const regex = new RegExp(`(${escapedSentence})`, 'g');

                    // Replace with highlighted version
                    element.innerHTML = element.textContent.replace(
                        regex,
                        '<span class="talk-highlight">$1</span>'
                    );

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
     * Clear all highlights
     */
    clearHighlights() {
        this.highlightedElements.forEach(element => {
            if (element.dataset.originalHtml) {
                element.innerHTML = element.dataset.originalHtml;
                delete element.dataset.originalHtml;
            }
        });

        this.highlightedElements = [];
    }

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

// Initialize the TalkAPI when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new TalkAPI();
});
