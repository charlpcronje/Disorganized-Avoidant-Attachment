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

        // Set up example sections
        this.setupExampleSections();

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

        // Clone the talk element content into the section
        const content = talkElement.innerHTML;
        section.innerHTML = content;

        // Add button to section
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
    async playAudio(text, voice, button, elements) {
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

                // Start sentence highlighting
                this.highlightManager.startSentenceHighlighting(text, elements, audio.duration, this.currentAudio);
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
                this.highlightManager.clearHighlights();
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
        return elements.map(el => el.textContent.trim()).join(' ');
    }

    /**
     * Reset UI after playback
     */
    resetUIAfterPlayback(button) {
        button.innerHTML = this.iconProvider.getSpeakerIcon();
        button.disabled = false;
        this.currentAudio = null;
        this.currentButton = null;

        // Clear highlights
        this.highlightManager.clearHighlights();

        // Remove active class from section
        const id = button.getAttribute('data-id');
        const section = document.getElementById(`talk-section-${id}`);
        if (section) {
            section.classList.remove('active');
        }
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
