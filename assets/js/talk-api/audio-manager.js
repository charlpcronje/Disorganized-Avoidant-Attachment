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
