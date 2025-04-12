-- /database/schema.sql
-- Database schema for Disorganized Attachment website

-- Create database
CREATE DATABASE IF NOT EXISTS attachment_site;
USE attachment_site;

-- Create users table for admin access
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create sessions table to track unique visits
CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    visitor_id VARCHAR(255),
    user_agent TEXT,
    ip_address VARCHAR(45),
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    total_duration INT DEFAULT 0,
    referrer TEXT,
    is_mobile BOOLEAN DEFAULT FALSE
);

-- Create pages table for page metadata
CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(100) NOT NULL,
    section VARCHAR(50) NOT NULL,
    subsection VARCHAR(50),
    order_num INT NOT NULL
);

-- Create events table for all user interactions
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    page_id INT NOT NULL,
    event_type ENUM('pageview', 'scroll', 'click', 'tab_switch', 'continue', 'navigation') NOT NULL,
    event_data JSON NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
);

-- Insert admin user (default password: admin123)
INSERT INTO users (username, password, email) VALUES 
('admin', '$2y$10$1QR5S0unbd9KUHVzYTBTG.evbr6rPlCCHgTkDxF74by8e3NWsHN1i', 'admin@example.com');

-- Insert page data
INSERT INTO pages (slug, title, section, subsection, order_num) VALUES
('introduction', 'Introduction', 'main', NULL, 1),
('what-is', 'What is Disorganized Attachment?', 'main', NULL, 2),
('push-pull', 'The Push-Pull Cycle', 'main', NULL, 3),
('lies-deflection', 'Lies, Deflection, and Blame', 'main', NULL, 4),
('sabotage', 'Sabotage Through Cheating and Rebounds', 'main', NULL, 5),
('toxic-preference', 'Preference for Toxic Relationships', 'main', NULL, 6),
('trauma-responses', 'Trauma Responses, Not Malice', 'main', NULL, 7),
('vicious-cycle', 'The Vicious Cycle', 'main', NULL, 8),
('secure-vs', 'Secure vs. Disorganized Attachment', 'main', NULL, 9),
('healing', 'Healing and Recovery', 'main', NULL, 10),
('resources', 'Resources', 'main', NULL, 11);

-- Create indexes for performance
CREATE INDEX idx_events_session ON events(session_id);
CREATE INDEX idx_events_page ON events(page_id);
CREATE INDEX idx_events_timestamp ON events(timestamp);
CREATE INDEX idx_sessions_visitor ON sessions(visitor_id);