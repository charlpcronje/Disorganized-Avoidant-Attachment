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
            defaultVoice: 'nova',
            domain: window.location.hostname,
            ...options
        };

        // Cache for audio elements
        this.audioCache = {};

        // Initialize by scanning for <talk> elements
        this.init();
    }

    /**
     * Initialize the TalkAPI by scanning for <talk> elements
     */
    init() {
        // Find all <talk> elements and add play buttons
        document.querySelectorAll('talk').forEach(element => {
            this.setupTalkElement(element);
        });

        // Make the API available globally
        window.talkAPI = this;
    }

    /**
     * Set up a <talk> element with play functionality
     * @param {HTMLElement} element - The <talk> element to set up
     */
    setupTalkElement(element) {
        // Get voice from attribute or use default
        const voice = element.getAttribute('voice') || this.config.defaultVoice;
        const text = element.textContent.trim();

        // Create play button
        const playButton = document.createElement('button');
        playButton.className = 'talk-play-btn';
        playButton.innerHTML = '🔊 Listen';
        playButton.setAttribute('data-voice', voice);
        playButton.setAttribute('data-text', text);

        // Add click event
        playButton.addEventListener('click', () => {
            this.speak(text, voice, playButton);
        });

        // Add button after the element
        element.parentNode.insertBefore(playButton, element.nextSibling);
    }

    /**
     * Generate speech for the given text and voice
     * @param {string} text - The text to convert to speech
     * @param {string} voice - The voice to use
     * @param {HTMLElement} [buttonElement] - Optional button element for UI updates
     * @returns {Promise<string>} - Promise resolving to the audio URL
     */
    async speak(text, voice = this.config.defaultVoice, buttonElement = null) {
        // Update button UI if provided
        if (buttonElement) {
            buttonElement.innerHTML = '⏳ Generating...';
            buttonElement.disabled = true;
        }

        try {
            // Check cache first
            const cacheKey = `${voice}_${text}`;
            if (this.audioCache[cacheKey]) {
                this.playAudio(this.audioCache[cacheKey], buttonElement);
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
                this.playAudio(absoluteAudioUrl, buttonElement);

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
     * @param {HTMLElement} [buttonElement] - Optional button element for UI updates
     */
    playAudio(audioUrl, buttonElement = null) {
        // Create audio element
        const audio = new Audio(audioUrl);

        // Update button when playing starts
        audio.onplay = () => {
            if (buttonElement) {
                buttonElement.innerHTML = '🔈 Playing...';
            }
        };

        // Update button when playing ends
        audio.onended = () => {
            if (buttonElement) {
                buttonElement.innerHTML = '🔊 Listen';
                buttonElement.disabled = false;
            }
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
        };

        // Play the audio
        audio.play().catch(error => {
            console.error('Error playing audio:', error);
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

// Initialize the TalkAPI when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.talkAPI = new TalkAPI();
});
