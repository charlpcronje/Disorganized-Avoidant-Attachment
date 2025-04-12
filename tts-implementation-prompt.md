# Prompt for Continuing TTS Implementation with Enhanced Link Handling

## Current Implementation Status
- Successfully restored `<talk>` tag functionality in the TalkAPI
- Implemented voice selection via the `voice` attribute
- Added sentence-level highlighting during playback
- Implemented link counting and announcement after text is read

## Next Implementation Goals
1. Add a new attribute to control link handling:
   - `readlinks="true|false"` to determine if links should be included in TTS
   - Default behavior should be to read link text but not URLs
   - When set to false, completely exclude links from TTS

2. Improve tag handling:
   - Properly strip HTML tags before sending to TTS API
   - Preserve the original HTML in the page display
   - Handle nested tags correctly within `<talk>` elements

3. Special handling for reference links:
   - Detect and skip parenthetical reference links
   - Implement smart detection of reference vs. content links

## Technical Details to Preserve
- The TTS API endpoint is: `https://talk.api.webally.co.za/speak`
- Default voice for regular content: `echo`
- Default voice for examples: `alloy`
- The system should announce the number of links after reading text
- Text highlighting should work at the sentence level

## Code Structure
- Main TalkAPI implementation is in modular files under `assets/js/talk-api/`
- The system uses DOM traversal to find and process `<talk>` tags
- Link handling is implemented in the `highlightManager.js` file
- Audio playback and API calls are in `audioManager.js`

## Current Issues to Address
- Need to add link filtering capability
- Need to improve handling of nested HTML tags
- Need to implement special handling for reference links

This prompt should help continue the implementation with a fresh context window while preserving all the important information about the current state and goals.
