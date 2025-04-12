-- SQL script to fix the event_type column in the events table
-- This script will modify the event_type column to allow longer values

-- First, check the current schema
DESCRIBE events;

-- Modify the event_type column to allow longer values (VARCHAR(20) should be sufficient)
ALTER TABLE events MODIFY COLUMN event_type VARCHAR(20) NOT NULL;

-- Verify the change
DESCRIBE events;

-- If you need to see the current data in the events table
-- SELECT id, session_id, page_id, event_type, LEFT(event_data, 50) AS event_data_preview FROM events LIMIT 10;

-- If you need to clean up any invalid data
-- DELETE FROM events WHERE event_type NOT IN ('pageview', 'scroll', 'click', 'tab_switch', 'continue', 'navigation', 'page_exit');
