/**
 * TTS API Client
 *
 * This client provides an interface to the TTS microservice described in docs/tts.md
 * It allows for easy text-to-speech conversion using OpenAI's voices.
 */

class TalkAPI {
    constructor(options = {}) {
        // Default configuration
        this.config = {
            apiEndpoint: 'https://talk.api.webally.co.za/speak',  // Point to the Nginx reverse proxy
            defaultVoice: 'echo',  // Default voice for regular content
            exampleVoice: 'alloy', // Default voice for examples
            domain: window.location.hostname,
            ...options
        };

        // Cache for audio elements
        this.audioCache = {};

        // Currently playing audio
        this.currentAudio = null;

        // Currently highlighted elements
        this.highlightedElements = [];

        // Initialize by scanning for sections
        this.init();
    }

    /**
     * Initialize the TalkAPI by scanning for content sections
     */
    init() {
        // Process main content sections
        this.setupContentSections();

        // Process example sections separately
        this.setupExampleSections();

        // Make the API available globally
        window.talkAPI = this;
    }

    /**
     * Set up content sections with play buttons
     */
    setupContentSections() {
        // Find all content sections (paragraphs, headers, list items)
        const contentSections = document.querySelectorAll('.page-content > p, .page-content > h2, .page-content > h3, .page-content > ul, .page-content > ol');

        // Group consecutive elements for a better user experience
        let currentGroup = [];
        let groupCount = 0;

        contentSections.forEach((section, index) => {
            // Skip if inside an example container
            if (section.closest('.example-container') || section.closest('.tab-content')) {
                return;
            }

            // Add to current group
            currentGroup.push(section);

            // Create a group when we reach 3 elements or it's the last element
            if (currentGroup.length >= 3 || index === contentSections.length - 1) {
                this.createSectionButton(currentGroup, `content-group-${groupCount}`, this.config.defaultVoice);
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
            // Create a play button for each tab content
            const tabId = tabContent.getAttribute('data-tab');
            this.createSectionButton([tabContent], `example-${tabId}-${index}`, this.config.exampleVoice);
        });
    }

    /**
     * Create a play button for a group of elements
     * @param {Array} elements - Array of DOM elements in the group
     * @param {string} groupId - Unique ID for the group
     * @param {string} voice - Voice to use for this group
     */
    createSectionButton(elements, groupId, voice) {
        if (elements.length === 0) return;

        // Get the first element to attach the button to
        const firstElement = elements[0];

        // Extract text from all elements
        const text = elements.map(el => el.textContent.trim()).join(' ');
        if (!text) return;

        // Create container for the button
        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'talk-button-container';
        buttonContainer.style.textAlign = 'right';
        buttonContainer.style.marginBottom = '15px';

        // Create play button
        const playButton = document.createElement('button');
        playButton.className = 'talk-play-btn';
        playButton.innerHTML = '🔊 Listen';
        playButton.style.padding = '5px 10px';
        playButton.style.backgroundColor = '#4CAF50';
        playButton.style.color = 'white';
        playButton.style.border = 'none';
        playButton.style.borderRadius = '4px';
        playButton.style.cursor = 'pointer';
        playButton.style.fontSize = '14px';

        // Store data attributes
        playButton.setAttribute('data-group-id', groupId);
        playButton.setAttribute('data-voice', voice);

        // Add click event
        playButton.addEventListener('click', () => {
            // Stop any currently playing audio
            if (this.currentAudio) {
                this.currentAudio.pause();
                this.currentAudio = null;
                this.clearHighlights();
            }

            // Get clean text without HTML tags
            const cleanText = this.getCleanText(elements);

            // Speak the text
            this.speak(cleanText, voice, playButton, elements);
        });

        // Add button to container
        buttonContainer.appendChild(playButton);

        // Insert button container after the first element
        firstElement.parentNode.insertBefore(buttonContainer, firstElement.nextSibling);
    }

    /**
     * Get clean text from elements without HTML tags
     * @param {Array} elements - Array of DOM elements
     * @returns {string} - Clean text
     */
    getCleanText(elements) {
        // Create a temporary div to extract text
        const tempDiv = document.createElement('div');

        // Clone each element and append to temp div
        elements.forEach(el => {
            const clone = el.cloneNode(true);
            tempDiv.appendChild(clone);
        });

        // Get text content and clean it
        let text = tempDiv.textContent.trim();

        // Remove extra whitespace
        text = text.replace(/\s+/g, ' ');

        return text;
    }

    /**
     * Generate speech for the given text and voice
     * @param {string} text - The text to convert to speech
     * @param {string} voice - The voice to use
     * @param {HTMLElement} buttonElement - Button element for UI updates
     * @param {Array} elements - Elements to highlight during playback
     * @returns {Promise<string>} - Promise resolving to the audio URL
     */
    async speak(text, voice, buttonElement, elements = []) {
        // Update button UI
        if (buttonElement) {
            buttonElement.innerHTML = '⏳ Generating...';
            buttonElement.disabled = true;
        }

        try {
            // Check cache first
            const cacheKey = `${voice}_${text}`;
            if (this.audioCache[cacheKey]) {
                this.playAudio(this.audioCache[cacheKey], buttonElement, elements);
                return this.audioCache[cacheKey];
            }

            // Call the TTS API
            const response = await fetch(this.config.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    text: text,
                    voice: voice,
                    domain: this.config.domain
                })
            });

            const data = await response.json();

            if (data.error) {
                throw new Error(data.error);
            }

            if (data.audio_url) {
                // Convert relative URL to absolute URL using the API base URL
                const apiBaseUrl = this.config.apiEndpoint.split('/').slice(0, -1).join('/');
                const absoluteAudioUrl = apiBaseUrl + data.audio_url;

                // Cache the absolute audio URL
                this.audioCache[cacheKey] = absoluteAudioUrl;

                // Play the audio
                this.playAudio(absoluteAudioUrl, buttonElement, elements);

                return absoluteAudioUrl;
            } else {
                throw new Error('No audio URL returned');
            }
        } catch (error) {
            console.error('TTS API Error:', error);

            // Update button UI on error
            if (buttonElement) {
                buttonElement.innerHTML = '❌ Error';
                setTimeout(() => {
                    buttonElement.innerHTML = '🔊 Retry';
                    buttonElement.disabled = false;
                }, 2000);
            }

            throw error;
        }
    }

    /**
     * Play audio from the given URL
     * @param {string} audioUrl - The URL of the audio to play
     * @param {HTMLElement} buttonElement - Button element for UI updates
     * @param {Array} elements - Elements to highlight during playback
     */
    playAudio(audioUrl, buttonElement, elements = []) {
        // Create audio element
        const audio = new Audio(audioUrl);
        this.currentAudio = audio;

        // Highlight elements when playing starts
        audio.onplay = () => {
            if (buttonElement) {
                buttonElement.innerHTML = '🔈 Playing...';
            }
            this.highlightElements(elements);
        };

        // Update button and remove highlights when playing ends
        audio.onended = () => {
            if (buttonElement) {
                buttonElement.innerHTML = '🔊 Listen';
                buttonElement.disabled = false;
            }
            this.clearHighlights();
            this.currentAudio = null;
        };

        // Handle errors
        audio.onerror = () => {
            console.error('Error playing audio:', audioUrl);
            if (buttonElement) {
                buttonElement.innerHTML = '❌ Playback Error';
                setTimeout(() => {
                    buttonElement.innerHTML = '🔊 Retry';
                    buttonElement.disabled = false;
                }, 2000);
            }
            this.clearHighlights();
            this.currentAudio = null;
        };

        // Play the audio
        audio.play().catch(error => {
            console.error('Error playing audio:', error);
            this.clearHighlights();
            this.currentAudio = null;
        });
    }

    /**
     * Highlight elements during audio playback
     * @param {Array} elements - Elements to highlight
     */
    highlightElements(elements) {
        this.clearHighlights();

        elements.forEach(element => {
            // Store original background color
            element.dataset.originalBackground = element.style.backgroundColor || '';
            element.dataset.originalTransition = element.style.transition || '';

            // Apply highlight
            element.style.transition = 'background-color 0.5s ease';
            element.style.backgroundColor = '#f0f8ff'; // Light blue highlight

            // Add to highlighted elements array
            this.highlightedElements.push(element);
        });
    }

    /**
     * Clear all highlighted elements
     */
    clearHighlights() {
        this.highlightedElements.forEach(element => {
            // Restore original background
            element.style.backgroundColor = element.dataset.originalBackground || '';
            element.style.transition = element.dataset.originalTransition || '';
        });

        this.highlightedElements = [];
    }

    /**
     * Get all available voices
     * @returns {Array} - Array of voice objects with id and description
     */
    getVoices() {
        return [
            { id: "alloy", description: "Balanced and smooth. Neutral tone. Great for general-purpose TTS" },
            { id: "ash", description: "Crisp and slightly assertive. Good for formal or confident delivery" },
            { id: "ballad", description: "Slow, poetic tone. Designed for gentle narration or lyrical content" },
            { id: "coral", description: "Rich and textured. Works well for expressive storytelling" },
            { id: "echo", description: "Soft-spoken and ethereal. Ideal for reflective tones or meditative text" },
            { id: "fable", description: "Warm and narrative-driven. Best for longform audio and immersive stories" },
            { id: "onyx", description: "Deep, clear male voice. Calm and grounded. Great for authoritative delivery" },
            { id: "nova", description: "Bright and emotionally rich. Excellent for engaging conversational style" },
            { id: "sage", description: "Soothing and wise-sounding. Suitable for calm explanation or support" },
            { id: "shimmer", description: "Playful, animated energy. Great for light content or interactive narration" }
        ];
    }
}

// Add CSS for highlighted text
const style = document.createElement('style');
style.textContent = `
    .talk-play-btn:hover {
        background-color: #45a049 !important;
    }
    .talk-play-btn:disabled {
        background-color: #cccccc !important;
        cursor: not-allowed;
    }
`;
document.head.appendChild(style);

// Initialize the TalkAPI when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.talkAPI = new TalkAPI();
});
