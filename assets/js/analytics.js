// /assets/js/analytics.js
// Detailed user behavior tracking system

class Analytics {
    getVisitorName() {
        return window.VISITOR_NAME || '';
    }
    constructor() {
        // Configuration
        this.syncInterval = window.SYNC_INTERVAL || 20000; // milliseconds
        this.scrollDebounce = window.SCROLL_DEBOUNCE || 500; // milliseconds
        this.storageKey = 'attachment_site_analytics';
        // Fix for CORS issue - use relative path instead of absolute URL
        // Check if we're on the production domain
        if (window.location.hostname === 'info.nade.webally.co.za') {
            this.apiEndpoint = '/api/sync.php';
        } else {
            // Fallback to the BASE_URL if defined, or use a relative path
            this.apiEndpoint = (window.BASE_URL || '/') + 'api/sync.php';
        }

        // Log the API endpoint for debugging
        console.log('Analytics API endpoint:', this.apiEndpoint);

        // Add no-cors mode to fetch requests to handle CORS issues
        this.fetchMode = 'no-cors';
        this.pageId = document.body.dataset.pageId || null;
        this.sessionId = document.body.dataset.sessionId || null;

        // Store events that haven't been synced yet
        this.pendingEvents = this.getStoredEvents();

        // Tracking state
        this.lastScrollPosition = 0;
        this.lastScrollTime = Date.now();
        this.isScrolling = false;
        this.scrollPositions = [];
        this.pageLoadTime = Date.now();

        // Initialize
        this.bindEvents();
        this.startSyncInterval();
        this.recordPageView();
    }

    // Get events from local storage
    getStoredEvents() {
        try {
            const stored = localStorage.getItem(this.storageKey);
            return stored ? JSON.parse(stored) : [];
        } catch (e) {
            console.error('Failed to retrieve stored events:', e);
            return [];
        }
    }

    // Save events to local storage
    saveEvents() {
        try {
            localStorage.setItem(this.storageKey, JSON.stringify(this.pendingEvents));
        } catch (e) {
            console.error('Failed to store events:', e);
        }
    }

    // Bind event listeners
    bindEvents() {
        // Track scrolling (debounced)
        let scrollTimer;
        window.addEventListener('scroll', () => {
            if (!this.isScrolling) {
                this.isScrolling = true;
                this.recordScrollStart();
            }

            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(() => {
                this.isScrolling = false;
                this.recordScrollEnd();
            }, this.scrollDebounce);

            // Record position periodically during scroll
            const now = Date.now();
            if (now - this.lastScrollTime >= this.scrollDebounce) {
                this.lastScrollTime = now;
                this.recordScrollPosition();
            }
        });

        // Track clicks on navigation, buttons, and tabs
        document.addEventListener('click', (e) => {
            // Navigation clicks
            if (e.target.closest('nav a') || e.target.closest('.sub-nav a')) {
                this.recordNavigationClick(e.target.closest('a').href, e.target.textContent.trim(), e);
            }

            // Continue button clicks
            if (e.target.closest('.continue-btn')) {
                this.recordContinueClick(e);
            }

            // Tab switches
            if (e.target.closest('.tab-btn')) {
                const tabBtn = e.target.closest('.tab-btn');
                const tabId = tabBtn.dataset.tab;
                const tabLabel = tabBtn.textContent.trim();
                this.recordTabSwitch(tabId, tabLabel, e);
            }
        });

        // Track page unload to sync events
        window.addEventListener('beforeunload', () => {
            this.recordPageExit();
            this.syncEvents(true); // Force immediate sync
        });
    }

    // Start periodic synchronization
    startSyncInterval() {
        setInterval(() => this.syncEvents(), this.syncInterval);
    }

    // Record page view event
    recordPageView() {
        if (!this.pageId) return;

        // Prevent double-counting: Only log one pageview per page load
        const pvKey = `pageview_${this.pageId}`;
        if (sessionStorage.getItem(pvKey)) return;
        sessionStorage.setItem(pvKey, '1');

        this.pendingEvents.push({
            type: 'pageview',
            pageId: this.pageId,
            timestamp: Date.now(),
            data: {
                url: window.location.href,
                title: document.title,
                viewport: {
                    width: window.innerWidth,
                    height: window.innerHeight
                }
            }
        });

        this.saveEvents();
    }

    // Record scroll start
    recordScrollStart() {
        if (!this.pageId) return;

        this.lastScrollPosition = window.scrollY;
        this.scrollPositions = [{
            position: window.scrollY,
            timestamp: Date.now()
        }];
    }

    // Record scroll position
    recordScrollPosition() {
        if (!this.pageId) return;

        this.scrollPositions.push({
            position: window.scrollY,
            timestamp: Date.now()
        });
    }

    // Record scroll end
    recordScrollEnd() {
        if (!this.pageId || this.scrollPositions.length < 2) return;

        const startPosition = this.scrollPositions[0].position;
        const endPosition = this.scrollPositions[this.scrollPositions.length - 1].position;
        const scrollDistance = Math.abs(endPosition - startPosition);

        if (scrollDistance < 10) return; // Ignore tiny scrolls

        // Calculate document height percentage
        const docHeight = Math.max(
            document.body.scrollHeight,
            document.body.offsetHeight,
            document.documentElement.clientHeight,
            document.documentElement.scrollHeight,
            document.documentElement.offsetHeight
        );

        const viewportHeight = window.innerHeight;
        const scrollableHeight = docHeight - viewportHeight;
        const scrollPercentage = Math.round((endPosition / scrollableHeight) * 100);

        this.pendingEvents.push({
            type: 'scroll',
            pageId: this.pageId,
            timestamp: Date.now(),
            data: {
                startPosition,
                endPosition,
                scrollDistance,
                scrollPercentage,
                duration: this.scrollPositions[this.scrollPositions.length - 1].timestamp - this.scrollPositions[0].timestamp,
                path: this.scrollPositions.map(p => ({
                    y: p.position,
                    t: p.timestamp - this.scrollPositions[0].timestamp // Relative time
                })),
                visitorName: this.getVisitorName()
            }
        });

        this.saveEvents();
    }

    // Record navigation click
    recordNavigationClick(url, label, e) {
        if (!this.pageId) return;

        // Get click position from the event or use defaults
        const position = e ? { x: e.clientX, y: e.clientY } : { x: 0, y: 0 };

        this.pendingEvents.push({
            type: 'navigation',
            pageId: this.pageId,
            timestamp: Date.now(),
            data: {
                url,
                label,
                position: position,
                visitorName: this.getVisitorName()
            }
        });

        this.saveEvents();
    }

    // Record continue button click
    recordContinueClick(e) {
        if (!this.pageId) return;

        const nextPageUrl = document.querySelector('.continue-btn').getAttribute('href');

        // Get click position from the event or use defaults
        const position = e ? { x: e.clientX, y: e.clientY } : { x: 0, y: 0 };

        this.pendingEvents.push({
            type: 'continue',
            pageId: this.pageId,
            timestamp: Date.now(),
            data: {
                nextPageUrl,
                position: position,
                visitorName: this.getVisitorName()
            }
        });

        this.saveEvents();
    }

    // Record tab switch
    recordTabSwitch(tabId, tabLabel, e) {
        if (!this.pageId) return;

        // Get click position from the event or use defaults
        const position = e ? { x: e.clientX, y: e.clientY } : { x: 0, y: 0 };

        this.pendingEvents.push({
            type: 'tab_switch',
            pageId: this.pageId,
            timestamp: Date.now(),
            data: {
                tabId,
                tabLabel,
                position: position,
                visitorName: this.getVisitorName()
            }
        });

        this.saveEvents();
    }

    // Record page exit
    recordPageExit() {
        if (!this.pageId) return;

        const duration = Date.now() - this.pageLoadTime;

        this.pendingEvents.push({
            type: 'page_exit',
            pageId: this.pageId,
            timestamp: Date.now(),
            data: {
                duration,
                scrollPosition: window.scrollY,
                visitorName: this.getVisitorName()
            }
        });

        this.saveEvents();
    }

    // Sync events with server
    syncEvents(forceSync = false) {
        if (this.pendingEvents.length === 0) return;

        // Don't sync small batches unless forced
        if (this.pendingEvents.length < 5 && !forceSync) return;

        const events = [...this.pendingEvents];

        fetch(this.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                sessionId: this.sessionId,
                events: events,
                visitorName: this.getVisitorName()
            }),
            keepalive: forceSync, // Ensure data is sent even on page close
            mode: this.fetchMode // Use no-cors mode to handle CORS issues
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove synced events from pending list
                this.pendingEvents = this.pendingEvents.filter(
                    event => !events.some(e => e.timestamp === event.timestamp && e.type === event.type)
                );
                this.saveEvents();
                console.log('Analytics events synced successfully:', data);
            } else {
                // Log server-side errors
                console.error('Server error syncing analytics events:', data.message);

                // If there's a data truncation error, fix the event types
                if (data.message && data.message.includes('Data truncated for column')) {
                    console.warn('Fixing truncated event types...');
                    this.fixTruncatedEventTypes();
                }
            }
        })
        .catch(error => {
            console.error('Failed to sync analytics events:', error);
        });
    }

    /**
     * Fix truncated event types in pending events
     * This method is called when a data truncation error is detected
     */
    fixTruncatedEventTypes() {
        // The valid event types according to the server
        const validTypes = ['pageview', 'scroll', 'click', 'tab_switch', 'continue', 'navigation', 'page_exit'];

        // Check each pending event and truncate the type if needed
        this.pendingEvents.forEach(event => {
            // If the event type is not in the valid types list, truncate it
            if (!validTypes.includes(event.type)) {
                console.warn(`Truncating event type: ${event.type}`);

                // Try to match with a valid type
                const matchedType = validTypes.find(type => event.type.startsWith(type));
                if (matchedType) {
                    event.type = matchedType;
                } else {
                    // If no match, use a generic type
                    event.type = 'click';
                }
            }
        });

        // Save the fixed events
        this.saveEvents();
        console.log('Event types fixed, will retry on next sync interval');
    }
}

// Initialize analytics when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.siteAnalytics = new Analytics();
});