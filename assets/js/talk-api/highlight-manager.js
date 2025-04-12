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
        
        // Calculate time per sentence
        const timePerSentence = duration / sentences.length;
        
        // Highlight sentences one by one
        sentences.forEach((sentence, index) => {
            setTimeout(() => {
                if (!currentAudio || currentAudio.paused) return;
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
}
