// /assets/js/talk-api/index.js
/**
 * TTS API Client - Super Simplified Version
 */

// Initialize the TalkAPI when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new TalkAPI();
});

class TalkAPI {
    constructor() {
        this.config = {
            apiEndpoint: 'https://talk.api.webally.co.za/speak',
            defaultVoice: 'echo',
            domain: window.location.hostname
        };
        
        this.audioCache = {};
        this.currentAudio = null;
        this.currentButton = null;
        this.progressInterval = null;
        
        this.setupContentSections();
    }

    setupContentSections() {
        document.querySelectorAll('talk, .talk').forEach((talkElement, index) => {
            const voice = talkElement.getAttribute('voice') || this.config.defaultVoice;
            this.createTalkSection(talkElement, `talk-${index}`, voice);
        });
    }

    createTalkSection(talkElement, id, voice) {
        // Create section wrapper
        const section = document.createElement('div');
        section.className = 'talk-section';
        section.id = `talk-section-${id}`;

        // Create progress bar
        const progressContainer = document.createElement('div');
        progressContainer.className = 'talk-progress-container';
        const progressBar = document.createElement('div');
        progressBar.className = 'talk-progress-bar';
        progressContainer.appendChild(progressBar);

        // Create content container
        const contentContainer = document.createElement('div');
        contentContainer.className = 'talk-content';
        contentContainer.innerHTML = talkElement.innerHTML;

        // Create button
        const button = document.createElement('button');
        button.className = 'talk-button';
        button.setAttribute('data-id', id);
        button.setAttribute('data-voice', voice);
        button.setAttribute('title', 'Listen to this section');
        button.innerHTML = this.getSpeakerIcon();
        button.addEventListener('click', () => this.handleButtonClick(button));

        // Assemble section
        section.appendChild(progressContainer);
        section.appendChild(contentContainer);
        section.appendChild(button);

        // Replace the talk element
        talkElement.parentNode.replaceChild(section, talkElement);
    }

    handleButtonClick(button) {
        // Toggle play/pause if this is the current button
        if (this.currentButton === button && this.currentAudio) {
            if (this.currentAudio.paused) {
                this.currentAudio.play();
                button.innerHTML = this.getPauseIcon();
            } else {
                this.currentAudio.pause();
                button.innerHTML = this.getPlayIcon();
            }
            return;
        }

        // Stop any currently playing audio
        this.stopCurrentAudio();

        // Get section
        const id = button.getAttribute('data-id');
        const voice = button.getAttribute('data-voice');
        const section = document.getElementById(`talk-section-${id}`);
        
        // Update button state
        button.innerHTML = this.getLoadingIcon();
        button.disabled = true;
        this.currentButton = button;

        // Get text from section
        const text = this.getTextFromElement(section);

        // Play audio
        this.playAudio(text, voice, button, section);
    }

    stopCurrentAudio() {
        if (this.currentAudio) {
            this.currentAudio.pause();
            this.currentAudio = null;
        }

        if (this.currentButton) {
            this.currentButton.innerHTML = this.getSpeakerIcon();
            this.currentButton.disabled = false;
            this.currentButton = null;
        }

        // Reset all sections
        document.querySelectorAll('.talk-section.active').forEach(section => {
            section.classList.remove('active');
            this.updateProgressBar(section, 0);
        });
        
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
            this.progressInterval = null;
        }
    }

    async playAudio(text, voice, button, section) {
    try {
        // Get audioUrl and also extract mp3 filename/hash for analytics
        const audioUrl = await this.getAudioUrl(text, voice);
        const audio = new Audio(audioUrl);
        this.currentAudio = audio;

        // Extract mp3 filename as unique talkId
        let talkId = null;
        try {
            const urlParts = audioUrl.split('/');
            talkId = urlParts[urlParts.length - 1]; // e.g. nova_6304fe29f5...adc.mp3
        } catch (e) {
            talkId = null;
        }

        // Save talkId on the audio element for later reference
        audio._talkId = talkId;
        audio._voice = voice;
        audio._text = text;
        audio._audioUrl = audioUrl;

        // --- Analytics: Play event ---
        if (window.siteAnalytics && talkId) {
            window.siteAnalytics.pendingEvents.push({
                type: 'tts_play',
                pageId: window.siteAnalytics.pageId,
                timestamp: Date.now(),
                data: {
                    talkId,
                    voice,
                    text,
                    audioUrl
                }
            });
            window.siteAnalytics.saveEvents();
        }

        // Set up events
        audio.oncanplay = () => {
            button.disabled = false;
            button.innerHTML = this.getPauseIcon();
            section.classList.add('active');
            this.startProgressTracking(audio, section);
        };

        // --- Analytics: Ended event ---
        audio.onended = () => {
            // Calculate percent played
            let percentPlayed = 100;
            let duration = audio.duration || null;
            if (window.siteAnalytics && talkId) {
                window.siteAnalytics.pendingEvents.push({
                    type: 'tts_end',
                    pageId: window.siteAnalytics.pageId,
                    timestamp: Date.now(),
                    data: {
                        talkId,
                        voice,
                        text,
                        audioUrl,
                        duration,
                        percentPlayed
                    }
                });
                window.siteAnalytics.saveEvents();
            }
            const linkCount = this.countLinks(section);
            if (linkCount > 0) {
                setTimeout(() => {
                    this.announceLinks(linkCount, voice, () => {
                        this.resetUI(button, section);
                    });
                }, 500);
            } else {
                this.resetUI(button, section);
            }
        };

        // --- Analytics: Pause event ---
        audio.onpause = () => {
            // Only log pause if not ended
            if (!audio.ended && window.siteAnalytics && talkId) {
                let duration = audio.duration || null;
                let currentTime = audio.currentTime;
                let percentPlayed = duration ? Math.round((currentTime / duration) * 100) : null;
                window.siteAnalytics.pendingEvents.push({
                    type: 'tts_pause',
                    pageId: window.siteAnalytics.pageId,
                    timestamp: Date.now(),
                    data: {
                        talkId,
                        voice,
                        text,
                        audioUrl,
                        duration,
                        currentTime,
                        percentPlayed
                    }
                });
                window.siteAnalytics.saveEvents();
            }
        };

        audio.onerror = () => {
            button.innerHTML = this.getErrorIcon();
            setTimeout(() => {
                button.innerHTML = this.getSpeakerIcon();
                button.disabled = false;
            }, 2000);
            this.currentAudio = null;
            this.currentButton = null;
            this.updateProgressBar(section, 0);
        };

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
        try {
            const audioUrl = await this.getAudioUrl(text, voice);
            const audio = new Audio(audioUrl);
            this.currentAudio = audio;

            // Set up events
            audio.oncanplay = () => {
                button.disabled = false;
                button.innerHTML = this.getPauseIcon();
                section.classList.add('active');
                this.startProgressTracking(audio, section);
            };

            audio.onended = () => {
                const linkCount = this.countLinks(section);
                
                if (linkCount > 0) {
                    setTimeout(() => {
                        this.announceLinks(linkCount, voice, () => {
                            this.resetUI(button, section);
                        });
                    }, 500);
                } else {
                    this.resetUI(button, section);
                }
            };

            audio.onerror = () => {
                button.innerHTML = this.getErrorIcon();
                setTimeout(() => {
                    button.innerHTML = this.getSpeakerIcon();
                    button.disabled = false;
                }, 2000);
                this.currentAudio = null;
                this.currentButton = null;
                this.updateProgressBar(section, 0);
            };

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

    async getAudioUrl(text, voice) {
        // Check cache
        const cacheKey = `${voice}_${text}`;
        if (this.audioCache[cacheKey]) return this.audioCache[cacheKey];
        
        // Make API request
        const response = await fetch(this.config.apiEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                text: text,
                voice: voice,
                domain: this.config.domain
            })
        });
        
        if (!response.ok) throw new Error(`API error: ${response.status}`);
        
        const result = await response.json();
        let audioUrl = result.audio_url;
        
        // Ensure absolute URL
        if (!audioUrl.startsWith('http')) {
            audioUrl = 'https://talk.api.webally.co.za' + (audioUrl.startsWith('/') ? '' : '/') + audioUrl;
        }
        
        // Cache and return
        this.audioCache[cacheKey] = audioUrl;
        return audioUrl;
    }

    getTextFromElement(element) {
        if (!element) return '';
        
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = element.querySelector('.talk-content').innerHTML;
        
        // Remove sup elements
        tempDiv.querySelectorAll('sup').forEach(sup => sup.remove());
        
        return tempDiv.textContent.trim().replace(/\s+/g, ' ');
    }

    countLinks(section) {
        return section ? section.querySelectorAll('a').length : 0;
    }
    
    async announceLinks(linkCount, voice, callback) {
        try {
            const text = `There ${linkCount === 1 ? 'was' : 'were'} ${linkCount} link${linkCount === 1 ? '' : 's'} in the text that was just read to you.`;
            const audioUrl = await this.getAudioUrl(text, voice);
            const audio = new Audio(audioUrl);
            audio.onended = callback;
            audio.play();
        } catch (error) {
            console.error('Error announcing links:', error);
            if (callback) callback();
        }
    }

    resetUI(button, section) {
        if (button) {
            button.innerHTML = this.getSpeakerIcon();
            button.disabled = false;
        }
        
        this.currentAudio = null;
        this.currentButton = null;

        if (section) {
            section.classList.remove('active');
            this.updateProgressBar(section, 0);
        }
        
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
            this.progressInterval = null;
        }
    }

    startProgressTracking(audio, section) {
        if (!audio || !section) return;

        if (this.progressInterval) {
            clearInterval(this.progressInterval);
        }

        this.progressInterval = setInterval(() => {
            if (audio.paused || audio.ended) {
                if (audio.ended) {
                    this.updateProgressBar(section, 1);
                }
                return;
            }

            this.updateProgressBar(section, audio.currentTime / audio.duration);
        }, 100);
    }

    updateProgressBar(section, progress) {
        if (!section) return;
        const progressBar = section.querySelector('.talk-progress-bar');
        if (progressBar) {
            progressBar.style.height = `${Math.min(100, Math.max(0, progress * 100))}%`;
        }
    }

    // Icon SVGs
    getSpeakerIcon() {
        return '<svg class="talk-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFFFFF"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>';
    }
    
    getPlayIcon() {
        return '<svg class="talk-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFFFFF"><path d="M8 5v14l11-7z"/></svg>';
    }
    
    getPauseIcon() {
        return '<svg class="talk-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFFFFF"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>';
    }
    
    getLoadingIcon() {
        return '<svg class="talk-icon talk-loading" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFFFFF"><path d="M12 4V2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2v2c4.42 0 8 3.58 8 8s-3.58 8-8 8-8-3.58-8-8 3.58-8 8-8z"/></svg>';
    }
    
    getErrorIcon() {
        return '<svg class="talk-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFFFFF"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>';
    }
}