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
        this.currentPlayButton = null;

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

            // Get all paragraphs within the tab content
            const paragraphs = tabContent.querySelectorAll('p');
            if (paragraphs.length > 0) {
                // Create a play button for each tab content
                this.createSectionButton([tabContent], `example-${tabId}-${index}`, this.config.exampleVoice, true);
            }
        });
    }

    /**
     * Create a play button for a group of elements
     * @param {Array} elements - Array of DOM elements in the group
     * @param {string} groupId - Unique ID for the group
     * @param {string} voice - Voice to use for this group
     * @param {boolean} isTabContent - Whether this is tab content
     */
    createSectionButton(elements, groupId, voice, isTabContent = false) {
        if (elements.length === 0) return;

        // Get the first element to attach the button to
        const firstElement = elements[0];

        // Extract text from all elements
        const text = elements.map(el => el.textContent.trim()).join(' ');
        if (!text) return;

        // Create a wrapper for the entire section
        const sectionWrapper = document.createElement('div');
        sectionWrapper.className = 'talk-section-wrapper';
        sectionWrapper.id = `talk-section-${groupId}`;
        sectionWrapper.style.position = 'relative';
        sectionWrapper.style.border = '1px solid rgba(200, 200, 200, 0.3)';
        sectionWrapper.style.borderRadius = '5px';
        sectionWrapper.style.padding = '10px';
        sectionWrapper.style.marginBottom = '15px';

        // Create play button
        const playButton = document.createElement('button');
        playButton.className = 'talk-play-btn';
        playButton.innerHTML = '<i class="talk-icon talk-icon-speaker"></i>';
        playButton.style.position = 'absolute';
        playButton.style.top = '5px';
        playButton.style.right = '5px';
        playButton.style.padding = '8px 12px';
        playButton.style.backgroundColor = '#2e7d32'; // Darker green
        playButton.style.color = '#ffffff';
        playButton.style.border = 'none';
        playButton.style.borderRadius = '4px';
        playButton.style.cursor = 'pointer';
        playButton.style.fontSize = '14px';
        playButton.style.zIndex = '100';
        playButton.style.boxShadow = '0 3px 5px rgba(0,0,0,0.3)';

        // Store data attributes
        playButton.setAttribute('data-group-id', groupId);
        playButton.setAttribute('data-voice', voice);
        playButton.setAttribute('title', 'Listen to this section');

        // Add click event
        playButton.addEventListener('click', () => {
            // If this button is for currently playing audio, pause it
            if (this.currentAudio && this.currentPlayButton === playButton) {
                console.log('Toggle play/pause for current audio');
                if (this.currentAudio.paused) {
                    // Resume playback
                    this.currentAudio.play();
                    playButton.innerHTML = '<i class="talk-icon talk-icon-pause"></i>'; // Pause symbol
                    playButton.setAttribute('title', 'Pause');
                    console.log('Resumed playback');
                } else {
                    // Pause playback
                    this.currentAudio.pause();
                    playButton.innerHTML = '<i class="talk-icon talk-icon-play"></i>'; // Play symbol
                    playButton.setAttribute('title', 'Resume');
                    console.log('Paused playback');
                }
                return;
            }

            // Stop any currently playing audio
            if (this.currentAudio) {
                console.log('Stopping previous audio');
                this.currentAudio.pause();
                if (this.currentPlayButton) {
                    this.currentPlayButton.innerHTML = '<i class="talk-icon talk-icon-speaker"></i>'; // Reset previous button
                    this.currentPlayButton.setAttribute('title', 'Listen to this section');
                }
                this.currentAudio = null;
                this.currentPlayButton = null;
                this.clearHighlights();
            }

            // Get clean text without HTML tags
            const cleanText = this.getCleanText(elements);

            // Store current button
            this.currentPlayButton = playButton;

            // Update button appearance
            playButton.innerHTML = '<i class="talk-icon talk-icon-loading"></i>'; // Loading spinner
            playButton.setAttribute('title', 'Generating audio...');
            console.log('Starting audio generation for new section');

            // Speak the text
            this.speak(cleanText, voice, playButton, elements);
        });

        // Handle special case for tab content
        if (isTabContent) {
            // For tab content, add the button directly to the tab content
            firstElement.style.position = 'relative';
            firstElement.appendChild(playButton);
            return;
        }

        // For regular content, use the wrapper approach
        // Clone all elements and move them into the wrapper
        const parent = firstElement.parentNode;

        elements.forEach(el => {
            // Clone the element to avoid reference issues
            const clone = el.cloneNode(true);
            sectionWrapper.appendChild(clone);
        });

        // Add the play button to the wrapper
        sectionWrapper.appendChild(playButton);

        // Insert the wrapper before the first element
        parent.insertBefore(sectionWrapper, firstElement);

        // Remove the original elements
        elements.forEach(el => {
            parent.removeChild(el);
        });
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
            buttonElement.innerHTML = '<i class="talk-icon talk-icon-loading"></i>'; // Loading spinner
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
     * Play audio from the given URL with sentence-by-sentence highlighting
     * @param {string} audioUrl - The URL of the audio to play
     * @param {HTMLElement} buttonElement - Button element for UI updates
     * @param {Array} elements - Elements to highlight during playback
     */
    playAudio(audioUrl, buttonElement, elements = []) {
        // Create audio element
        const audio = new Audio(audioUrl);
        this.currentAudio = audio;

        // Store elements and text for highlighting
        this.elementsToHighlight = elements;

        // When audio metadata is loaded, we can get duration
        audio.onloadedmetadata = () => {
            // Prepare sentence highlighting based on audio duration
            this.prepareHighlighting(audio.duration, elements);
        };

        // When audio starts playing
        audio.onplay = () => {
            console.log('Audio playback started');
            if (buttonElement) {
                buttonElement.innerHTML = '<i class="talk-icon talk-icon-pause"></i>'; // Pause symbol
                buttonElement.setAttribute('title', 'Pause');
            }

            // Highlight the section wrapper
            const groupId = buttonElement.getAttribute('data-group-id');
            const sectionWrapper = document.getElementById(`talk-section-${groupId}`);
            if (sectionWrapper) {
                sectionWrapper.style.border = '1px solid rgba(76, 175, 80, 0.7)';
                sectionWrapper.classList.add('active-talk-section');
            }

            // Start the highlighting sequence
            this.startHighlightSequence();
        };

        // Update button and remove highlights when playing ends
        audio.onended = () => {
            console.log('Audio playback ended');
            if (buttonElement) {
                buttonElement.innerHTML = '<i class="talk-icon talk-icon-speaker"></i>'; // Speaker icon
                buttonElement.setAttribute('title', 'Listen to this section');
                buttonElement.disabled = false;
            }
            this.clearHighlights();
            this.stopHighlightSequence();
            this.currentAudio = null;
            this.currentPlayButton = null;

            // Reset section wrapper
            const groupId = buttonElement.getAttribute('data-group-id');
            const sectionWrapper = document.getElementById(`talk-section-${groupId}`);
            if (sectionWrapper) {
                sectionWrapper.style.border = '1px solid rgba(200, 200, 200, 0.3)';
                sectionWrapper.classList.remove('active-talk-section');
            }
        };

        // Handle errors
        audio.onerror = () => {
            console.error('Error playing audio:', audioUrl);
            if (buttonElement) {
                buttonElement.innerHTML = '<i class="talk-icon talk-icon-error"></i>'; // Error symbol
                buttonElement.setAttribute('title', 'Playback Error');
                setTimeout(() => {
                    buttonElement.innerHTML = '<i class="talk-icon talk-icon-play"></i>'; // Play icon
                    buttonElement.setAttribute('title', 'Retry');
                    buttonElement.disabled = false;
                }, 2000);
            }
            this.clearHighlights();
            this.stopHighlightSequence();
            this.currentAudio = null;
            this.currentPlayButton = null;

            // Reset section wrapper
            const groupId = buttonElement.getAttribute('data-group-id');
            const sectionWrapper = document.getElementById(`talk-section-${groupId}`);
            if (sectionWrapper) {
                sectionWrapper.style.border = '1px solid rgba(200, 200, 200, 0.3)';
                sectionWrapper.classList.remove('active-talk-section');
            }
        };

        // Handle audio pausing
        audio.onpause = () => {
            console.log('Audio playback paused');
            this.stopHighlightSequence();
            if (buttonElement) {
                buttonElement.innerHTML = '<i class="talk-icon talk-icon-play"></i>'; // Play symbol
                buttonElement.setAttribute('title', 'Resume');
            }
        };

        // Play the audio
        audio.play().catch(error => {
            console.error('Error playing audio:', error);
            if (buttonElement) {
                buttonElement.innerHTML = '<i class="talk-icon talk-icon-error"></i>'; // Error symbol
                buttonElement.setAttribute('title', 'Playback Error');
                setTimeout(() => {
                    buttonElement.innerHTML = '<i class="talk-icon talk-icon-play"></i>'; // Play icon
                    buttonElement.setAttribute('title', 'Retry');
                    buttonElement.disabled = false;
                }, 2000);
            }
            this.clearHighlights();
            this.stopHighlightSequence();
            this.currentAudio = null;
            this.currentPlayButton = null;
        });
    }

    /**
     * Prepare sentence-by-sentence highlighting based on audio duration
     * @param {number} duration - Audio duration in seconds
     * @param {Array} elements - Elements containing the text
     */
    prepareHighlighting(duration, elements) {
        // Get all text content
        const text = this.getCleanText(elements);

        // Split into sentences (basic split by punctuation)
        const sentences = this.splitIntoSentences(text);

        // Calculate average reading speed (words per second)
        const wordCount = text.split(/\s+/).length;
        const wordsPerSecond = wordCount / duration;

        // Create a timeline of when each sentence should be highlighted
        this.highlightTimeline = [];
        let currentTime = 0;

        sentences.forEach(sentence => {
            // Count words in this sentence
            const sentenceWordCount = sentence.split(/\s+/).length;

            // Calculate how long this sentence should take to read
            const sentenceDuration = sentenceWordCount / wordsPerSecond;

            // Add to timeline
            this.highlightTimeline.push({
                sentence: sentence,
                startTime: currentTime,
                endTime: currentTime + sentenceDuration
            });

            // Update current time
            currentTime += sentenceDuration;
        });

        // Map sentences to DOM elements and their text nodes
        this.mapSentencesToElements(elements, sentences);
    }

    /**
     * Map sentences to their containing DOM elements
     * @param {Array} elements - DOM elements
     * @param {Array} sentences - Sentences to map
     */
    mapSentencesToElements(elements, sentences) {
        // Create a map of sentences to their DOM elements
        this.sentenceMap = [];

        // For each element, find which sentences it contains
        elements.forEach(element => {
            const elementText = element.textContent;

            sentences.forEach(sentence => {
                if (elementText.includes(sentence)) {
                    this.sentenceMap.push({
                        sentence: sentence,
                        element: element
                    });
                }
            });
        });
    }

    /**
     * Split text into sentences
     * @param {string} text - Text to split
     * @returns {Array} - Array of sentences
     */
    splitIntoSentences(text) {
        // Basic sentence splitting (handles periods, question marks, exclamation points)
        const sentenceRegex = /[^.!?]+[.!?]+/g;
        const sentences = text.match(sentenceRegex) || [];

        // Handle any remaining text that doesn't end with punctuation
        const remainingText = text.replace(sentenceRegex, '').trim();
        if (remainingText) {
            sentences.push(remainingText);
        }

        return sentences.map(s => s.trim());
    }

    /**
     * Start the highlighting sequence
     */
    startHighlightSequence() {
        // Clear any existing highlights and timers
        this.clearHighlights();
        this.stopHighlightSequence();

        // Store start time
        this.highlightStartTime = Date.now();

        // Start the highlight update loop
        this.updateHighlighting();
    }

    /**
     * Update the highlighting based on current audio position
     */
    updateHighlighting() {
        if (!this.currentAudio || !this.highlightTimeline) return;

        // Calculate elapsed time
        const elapsed = (Date.now() - this.highlightStartTime) / 1000;

        // Find the current sentence based on elapsed time
        const currentSentence = this.highlightTimeline.find(item =>
            elapsed >= item.startTime && elapsed <= item.endTime
        );

        // Clear any existing sentence highlights first
        this.clearSentenceHighlights();

        // If we found a sentence to highlight
        if (currentSentence) {
            // Find the element containing this sentence
            const sentenceMapping = this.sentenceMap.find(mapping =>
                mapping.sentence === currentSentence.sentence
            );

            if (sentenceMapping) {
                // Highlight just this sentence within the element
                this.highlightSentence(sentenceMapping.element, currentSentence.sentence);
            }
        }

        // Continue updating if audio is still playing
        if (this.currentAudio && !this.currentAudio.paused) {
            this.highlightTimer = requestAnimationFrame(() => this.updateHighlighting());
        }
    }

    /**
     * Stop the highlight sequence
     */
    stopHighlightSequence() {
        if (this.highlightTimer) {
            cancelAnimationFrame(this.highlightTimer);
            this.highlightTimer = null;
        }
    }

    /**
     * Highlight a specific sentence within an element
     * @param {HTMLElement} element - Element containing the sentence
     * @param {string} sentence - The sentence to highlight
     */
    highlightSentence(element, sentence) {
        if (!element || !sentence) return;

        // Get the element's text content
        const text = element.textContent;
        if (!text.includes(sentence)) return;

        // Create a span to wrap the sentence
        const span = document.createElement('span');
        span.className = 'sentence-highlight';
        span.textContent = sentence;

        // Store the original HTML
        if (!element.dataset.originalHtml) {
            element.dataset.originalHtml = element.innerHTML;
        }

        // Replace the sentence with the highlighted version
        // This is a simple approach that works for basic cases
        // A more robust solution would use text ranges and DOM manipulation
        try {
            const escapedSentence = sentence.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const regex = new RegExp(`(${escapedSentence})`, 'g');
            element.innerHTML = text.replace(regex, '<span class="sentence-highlight">$1</span>');

            // Add to highlighted elements array for cleanup
            this.highlightedElements.push(element);
        } catch (error) {
            console.error('Error highlighting sentence:', error);
        }
    }

    /**
     * Clear all highlighted elements
     */
    clearHighlights() {
        if (!this.highlightedElements) this.highlightedElements = [];

        this.highlightedElements.forEach(element => {
            // Restore original background
            element.style.backgroundColor = element.dataset.originalBackground || '';
            element.style.transition = element.dataset.originalTransition || '';

            // Restore original HTML if it was stored
            if (element.dataset.originalHtml) {
                element.innerHTML = element.dataset.originalHtml;
                delete element.dataset.originalHtml;
            }
        });

        this.highlightedElements = [];
    }

    /**
     * Clear only sentence highlights without affecting section highlights
     */
    clearSentenceHighlights() {
        // Remove all sentence-highlight spans
        document.querySelectorAll('.sentence-highlight').forEach(span => {
            // If the span is inside a parent element, replace it with its text content
            if (span.parentNode) {
                const text = span.textContent;
                const textNode = document.createTextNode(text);
                span.parentNode.replaceChild(textNode, span);
            }
        });
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

// Add CSS for highlighted text and section wrappers
const style = document.createElement('style');
style.textContent = `
    .talk-play-btn:hover {
        background-color: #1b5e20 !important; /* Darker green on hover */
    }
    .talk-play-btn:disabled {
        background-color: #757575 !important; /* Darker gray when disabled */
        cursor: not-allowed;
    }
    .talk-section-wrapper {
        transition: background-color 0.3s ease, border 0.3s ease;
    }
    .talk-section-wrapper.active-talk-section {
        border: 1px solid rgba(76, 175, 80, 0.7) !important;
    }
    .sentence-highlight {
        background-color: rgba(76, 175, 80, 0.3);
        border-radius: 3px;
        padding: 2px 0;
        display: inline;
    }

    /* Icon styles */
    .talk-icon {
        display: inline-block;
        width: 16px;
        height: 16px;
        background-size: contain;
        background-repeat: no-repeat;
        background-position: center;
        filter: drop-shadow(0 2px 2px rgba(0,0,0,0.5)); /* Stronger shadow */
    }

    .talk-icon-speaker {
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>');
    }

    .talk-icon-play {
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M8 5v14l11-7z"/></svg>');
    }

    .talk-icon-pause {
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>');
    }

    .talk-icon-loading {
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M12 4V2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2v2c4.42 0 8 3.58 8 8s-3.58 8-8 8-8-3.58-8-8 3.58-8 8-8z"/></svg>');
        animation: spin 1s linear infinite;
    }

    .talk-icon-error {
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>');
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);

// Initialize the TalkAPI when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.talkAPI = new TalkAPI();

    // Add a class to the body to indicate TalkAPI is loaded
    document.body.classList.add('talkapi-loaded');

    // Log version to confirm the latest file is loaded
    console.log('%c TalkAPI v1.2.0 loaded - Latest update: Fixed sentence highlighting, restored speaker icon, improved button colors, added shadows', 'background: #2e7d32; color: white; padding: 5px; border-radius: 3px;');
});
