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
            };
            
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
}
