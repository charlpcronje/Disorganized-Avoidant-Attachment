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
     * Highlight text nodes that contain parts of the sentence
     */
    highlightTextNodesWithSentence(element, sentence) {
        // Get all text nodes in the element
        const textNodes = this.getTextNodes(element);

        // Find which text nodes contain parts of the sentence
        const matchingNodes = [];
        let remainingSentence = sentence;

        // First pass: identify nodes that contain parts of the sentence
        for (const node of textNodes) {
            const nodeText = node.nodeValue;
            if (remainingSentence.includes(nodeText) || nodeText.includes(remainingSentence)) {
                matchingNodes.push(node);
                // Remove the matched text from the remaining sentence
                remainingSentence = remainingSentence.replace(nodeText, '');
            }
        }

        // If we didn't find any matching nodes or didn't match the full sentence,
        // fall back to a simpler approach
        if (matchingNodes.length === 0 || remainingSentence.length > 0) {
            // Fallback to simpler approach
            const walker = document.createTreeWalker(
                element,
                NodeFilter.SHOW_TEXT,
                null,
                false
            );

            let node;
            while (node = walker.nextNode()) {
                const text = node.nodeValue;
                if (text.trim() === '') continue;

                // Check if this text node contains any part of the sentence
                if (sentence.includes(text) || text.includes(sentence)) {
                    // Create a span element
                    const span = document.createElement('span');
                    span.className = 'talk-highlight';

                    // Replace the text node with the span
                    const newNode = node.splitText(0);
                    newNode.parentNode.replaceChild(span, newNode);
                    span.appendChild(document.createTextNode(text));
                }
            }
        } else {
            // Highlight each matching node
            for (const node of matchingNodes) {
                // Create a span element
                const span = document.createElement('span');
                span.className = 'talk-highlight';

                // Replace the text node with the span
                const parent = node.parentNode;
                parent.replaceChild(span, node);
                span.appendChild(node);
            }
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
        this.highlightedElements.forEach(element => {
            if (element.dataset.originalHtml) {
                element.innerHTML = element.dataset.originalHtml;
                delete element.dataset.originalHtml;
            }
        });

        this.highlightedElements = [];
    }
}
