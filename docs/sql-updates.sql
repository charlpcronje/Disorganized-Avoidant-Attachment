-- SQL Update Script for events table
-- This script modifies the event_type column to fix the data truncation error
use attachment_site;
-- First, backup the events table (optional but recommended)
CREATE TABLE events_backup LIKE events;
INSERT INTO events_backup SELECT * FROM events;

-- Modify the events table to change event_type from ENUM to VARCHAR
-- This will allow more flexibility with event types
ALTER TABLE events 
MODIFY COLUMN event_type VARCHAR(20) NOT NULL;

-- Add the 'page_exit' event type that was missing from the original ENUM
-- This is needed because the analytics.js file uses this event type

-- Add a comment to explain the change
-- The original definition was:
-- event_type enum('pageview','scroll','click','tab_switch','continue','navigation') NOT NULL

-- Create an index on event_type for better performance (optional)
CREATE INDEX idx_events_type ON events(event_type);

-- Verify the change
DESCRIBE events;

-- Show the distinct event types in the table
SELECT DISTINCT event_type FROM events;

-- If you need to revert the change (keep this commented out unless needed)
-- ALTER TABLE events 
-- MODIFY COLUMN event_type enum('pageview','scroll','click','tab_switch','continue','navigation','page_exit') NOT NULL;

-- If you need to clean up any invalid data
-- DELETE FROM events WHERE event_type NOT IN ('pageview','scroll','click','tab_switch','continue','navigation','page_exit');
