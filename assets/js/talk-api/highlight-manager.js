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
