-- Table for logging API calls and responses
CREATE TABLE IF NOT EXISTS api_calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    headers TEXT,
    body LONGTEXT,
    query_params TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    response LONGTEXT,
    finished_at TIMESTAMP NULL DEFAULT NULL
);
